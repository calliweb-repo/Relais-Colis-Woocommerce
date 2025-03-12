/**
 * WooCommerce Order Packages Management
 * --------------------------------------
 *
 * This JavaScript file is responsible for dynamically managing the package distribution (colis)
 * within WooCommerce orders in the admin panel. It handles UI rendering, AJAX-based interactions,
 * and order tracking through shipping labels.
 *
 * ## Key Features:
 * - **Dynamic UI Rendering**: Generates package distribution UI based on WooCommerce order metadata.
 * - **AJAX-Based Operations**: Supports adding, removing, and updating packages without page refresh.
 * - **Shipping Label Management**: Assigns, updates, and displays shipping labels per package.
 * - **State-Based UI Updates**: Adjusts available actions based on the shipping status of each package.
 * - **Internationalization Support**: Uses `wp_localize_script` for translated labels.
 *
 * ## State Management Strategy:
 * 1. **Order Initialization**:
 *    - Loads package (`colis`) data from WooCommerce metadata.
 *    - Determines whether the order has any existing shipping labels.
 *
 * 2. **Package Management States**:
 *    - **Pending Packages** (No shipping label yet):
 *      - Allows adding/removing items to/from packages.
 *      - Allows modifying weight and dimensions.
 *      - Enables "Auto Distribute" button.
 *    - **Label Generated** (Package has `shipping_label`):
 *      - Disables package modifications (no adding/removing).
 *      - Enables "Print Label" button.
 *    - **Tracking in Progress** (Package has `shipping_status`):
 *      - Displays tracking status instead of "Print Label" button.
 *      - Prevents modifications or deletions.
 *
 * 3. **User Interactions**:
 *    - **Add Package**: Creates a new package and updates the UI.
 *    - **Assign Items**: Moves items from the available list into a package.
 *    - **Remove Items**: Unassigns items from a package back to the available list.
 *    - **Auto Distribute**: Algorithmically distributes items across packages.
 *    - **Generate Shipping Labels**: Requests a shipping label for each package.
 *    - **Print Label**: Opens a modal displaying the shipping label PDF.
 *
 * 4. **Event Listeners & AJAX Handling**:
 *    - Each user action triggers an AJAX request to update WooCommerce metadata.
 *    - Responses update the UI dynamically without requiring a full page reload.
 *
 * ## JSON Data Structure (rc_order_colis Example)
 * ```json
 * [
 *   {
 *     "items": { "83": 2 },
 *     "weight": 240,
 *     "dimensions": { "height": 0, "width": 0, "length": 0 },
 *     "shipping_label": "4H013000008101",
 *     "shipping_label_pdf": "<PDF URL>",
 *     "shipping_status": "status_rc_depose_en_relais"
 *   }
 * ]
 * ```
 *
 * @package   RelaisColisWoocommerce
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
jQuery(document).ready(function ($) {

    console.log('RC Order Packages initialized');

    'use strict';

    /**
     * Render the UI dynamically based on JSON data
     */
    function renderColisUI() {
        let container = $('#rc-colis-container');
        container.empty();

        // Ajout du conteneur pour les erreurs
        container.append(`
                <div id="rc-error-message" class="rc-error hidden">
                    <span class="rc-error-message"></span>
                    <button class="rc-error-close">&times;</button>
                </div>
            `);

        // Permet de cacher l'erreur en cliquant dessus
        $(document).on('click', '#rc-error-message', function () {
            $(this).fadeOut();
        });

        // Modale Shipping label PDF
        container.append(`
            <div id="rc-pdf-modal" class="rc-modal">
                <div class="rc-modal-content">
                    <span class="rc-close-modal">&times;</span>
                    <iframe id="rc-pdf-frame" src="" width="100%" height="800px"></iframe>
                </div>
            </div>
        `);

        // Vérifier si au moins un colis a un `shipping_label`
        let hasShippingLabel = rc_order_colis.some(colis => colis.shipping_label);

        // Products Section
        let productsSection = $('<div class="rc-products-section"></div>');
        productsSection.append('<h3>' + rc_order_packages.label_products_to_distribute + '</h3>');

        rc_order_items.forEach(item => {
            if (item.remaining_quantity > 0) {
                let totalProductsWeight = item.weight * item.remaining_quantity;
                let productDiv = $(`
                <div class="rc-colis-item">
                    <span class="rc-colis-item-name">${item.name}</span>
                    <span class="rc-colis-item-weight"><strong>${rc_order_packages.label_unit_weight}</strong> ${item.weight} ${rc_order_packages.label_weight_units}</span>
                    <span class="rc-colis-item-qty"><strong>${rc_order_packages.label_remaining_quantity_to_be_distributed}</strong> ${item.remaining_quantity}</span>
                    <span class="rc-colis-item-weight"><strong>${rc_order_packages.label_total_weight}</strong> ${totalProductsWeight} ${rc_order_packages.label_weight_units}</span>
                    
                    ${hasShippingLabel ? '' :
                    `<div class="rc-product-actions">
                        ${rc_order_colis.length > 0 ? `
                            <input type="number" class="rc-product-qty" id="qty_${item.id}" min="1" max="${item.remaining_quantity}" value="1">
                            <select class="rc-product-select" id="colis_select_${item.id}">
                                ${rc_order_colis.map((colis, index) => `<option value="${index}">${rc_order_packages.label_package} ${index + 1}</option>`).join('')}
                            </select>
                            <button class="rc-add-to-colis" data-product-id="${item.id}">${rc_order_packages.label_add_in_package}</button>
                        ` : '<span class="rc-no-package">' + rc_order_packages.label_please_add_a_package + '</span>'}
                    </div>`}
                </div>
            `);

                productsSection.append(productDiv);
            }
        });

        if (productsSection.children('.rc-colis-item').length > 0) {

            // Display remaining items
            container.append(productsSection);

            // Bouton "Répartition Automatique"
            container.append(`<button class="rc-auto-distribute" ${hasShippingLabel ? 'disabled' : ''}>${rc_order_packages.label_auto_distribute}</button>`);
        } else {

            container.append(`${rc_order_packages.label_all_products_assigned}`);
        }

        // Packages Section
        container.append('<h3>' + rc_order_packages.label_existing_packages + '</h3>');

        // Prepare recap
        let totalWeight = 0;
        let recapContainer = $('<div class="rc-recap-container"></div>');

        rc_order_colis.forEach((colis, index) => {
            let minWeight = Object.entries(colis.items).reduce((sum, [productId, qty]) => {
                let product = rc_order_items.find(p => p.id == productId);
                return sum + (product ? product.weight * qty : 0);
            }, 0);
            let pdf_url = colis.shipping_label_pdf ? colis.shipping_label_pdf : ''; // URL du fichier PDF de l'étiquette
            let isLocked = colis.shipping_label ? 'disabled' : '';

            totalWeight += colis.weight;

            let colisDiv = $(`
            <div class="rc-colis">
                <div class="rc-colis-header">
                    <h4>${rc_order_packages.label_package} ${index + 1}</h4>
                    ${colis.shipping_label ? `<span class="rc-shipping-label">${rc_order_packages.label_shipping_label} ${colis.shipping_label}</span>` : `<button class="rc-delete-colis" data-colis-index="${index}" ${isLocked}>${rc_order_packages.label_delete_package}</button>`}
                </div>
                <div class="rc-colis-items">
                    ${Object.entries(colis.items).map(([productId, quantity]) => {
                let product = rc_order_items.find(p => p.id == productId);
                let totalProductsWeight = product ? product.weight * quantity : 0;
                return `
                                    <div class="rc-colis-item">
                                        <span class="rc-colis-item-name">${product ? product.name : rc_order_packages.label_unknown}</span>
                                        <span class="rc-colis-item-weight"><strong>${rc_order_packages.label_unit_weight}</strong> ${product ? product.weight + ' ' + rc_order_packages.label_weight_units : '-'}</span>
                                        <span class="rc-colis-item-qty"><strong>${rc_order_packages.label_quantity}</strong> ${quantity}</span>
                                        <span class="rc-colis-item-weight"><strong>${rc_order_packages.label_total_weight}</strong> ${totalProductsWeight} ${rc_order_packages.label_weight_units}</span>
                                        ${colis.shipping_label ? '' : `<button class="rc-remove-from-colis" data-product-id="${productId}" data-colis-index="${index}">${rc_order_packages.label_remove_from_package}</button>`}
                                    </div>
                                `;
            }).join('')}
                </div>

                <!-- Récapitulatif modifiable (désactivé si un shipping_label existe) -->
                <div class="rc-colis-summary">
                    <label><strong>${rc_order_packages.label_total_weight}</strong></label>
                    <input type="number" class="rc-colis-weight" data-colis-index="${index}" min="0" step="0.1" value="${colis.weight}" ${isLocked}>
                    
                    <label><strong>${rc_order_packages.label_dimensions} (${rc_order_packages.label_dimensions_units})</strong></label>
                    <input type="number" class="rc-colis-dim" data-dim="height" data-colis-index="${index}" placeholder="${rc_order_packages.label_height}" value="${colis.dimensions.height || ''}" ${isLocked}>
                    <input type="number" class="rc-colis-dim" data-dim="width" data-colis-index="${index}" placeholder="${rc_order_packages.label_width}" value="${colis.dimensions.width || ''}" ${isLocked}>
                    <input type="number" class="rc-colis-dim" data-dim="length" data-colis-index="${index}" placeholder="${rc_order_packages.label_length}" value="${colis.dimensions.length || ''}" ${isLocked}>
                    ${colis.shipping_label ? '' : `<button class="rc-update-colis" data-colis-index="${index}">${rc_order_packages.label_update_package}</button>`}
                </div>
            </div>
        `);
            container.append(colisDiv);

            // Ajouter une ligne au récapitulatif
            let recapItem = $(`
                <div class="rc-recap-item">
                    <span class="rc-recap-package">${rc_order_packages.label_package} ${index + 1}</span>
                    <span class="rc-recap-weight">${colis.weight} ${rc_order_packages.label_weight_units}</span>
                    ${colis.c2c_shipping_price ? `<span class="rc-recap-price"><strong>${rc_order_packages.label_estimated_shipping_price}</strong> ${colis.c2c_shipping_price} €</span>` : ''}
                    ${colis.shipping_status_label ? `<span class="rc-recap-status">${colis.shipping_status_label}</span>` :
                (colis.shipping_label ? `<button class="rc-print-label" data-colis-index="${index}" data-pdf-url="${pdf_url}">${rc_order_packages.label_print_shipping_label}</button>` : '')}
                </div>
            `);

            recapContainer.append(recapItem);
        });

        // Button to add new package
        hasShippingLabel ? '' : container.append(`<button class="rc-add-colis">${rc_order_packages.label_add_a_package}</button>`);

        // Vérifier si au moins un colis est "status_rc_livre" pour afficher le bouton de retour
        let hasDeliveredPackage = rc_order_colis.some(colis => colis.shipping_status === "status_rc_livre");
        console.log('hasDeliveredPackage? ' + hasDeliveredPackage);
        console.log('c2c_mode? ' + c2c_mode);

        // Ajouter le total au récapitulatif
        let recapTotal = $(`
            <div class="rc-recap-item rc-recap-total">
                <span class="rc-recap-package"><strong>${rc_order_packages.label_total_weight}</strong></span>
                <span class="rc-recap-weight"><strong>${totalWeight} ${rc_order_packages.label_weight_units}</strong></span>
            </div>
        `);
        recapContainer.append(recapTotal);

        container.append('<h3>' + rc_order_packages.label_recap + '</h3>');
        container.append(recapContainer);

        // Vérifier si tous les produits ont été répartis
        let allProductsAssigned = rc_order_items.every(item => item.remaining_quantity === 0);

        // Si plus aucun produit n'est à répartir et aucun `shipping_label` n'existe, afficher le bouton de génération d'étiquette
        if (allProductsAssigned && !hasShippingLabel) {
            container.append(`<button class="rc-place-shipping-label">${rc_order_packages.label_place_shipping_label}</button>`);
            if (c2c_mode == '1') {
                container.append(`<button class="rc-get-packages-price">${rc_order_packages.label_get_packages_price}</button>`);
            }
        }

        // Vérifier si une étiquette de retour existe
        let hasReturnLabel = return_bordereau_smart_url && return_bordereau_smart_url.trim() !== "";

        // Afficher l'étiquette de retour si disponible, sinon afficher le bouton
        if (hasDeliveredPackage && (c2c_mode == '0')) {
            if (hasReturnLabel) {
                container.append(`
            <div class="rc-return-info">
                <h3>${rc_order_packages.label_return_information}</h3>
                <p><strong>${rc_order_packages.label_return_number}:</strong> ${return_number}</p>
                <p><strong>${rc_order_packages.label_return_number_cab}:</strong> ${return_number_cab}</p>
                <p><strong>${rc_order_packages.label_return_limit_date}:</strong> ${return_limit_date}</p>
                <p><a href="${return_bordereau_smart_url}" target="_blank">${rc_order_packages.label_view_return_label}</a></p>
                ${return_image_url ? `<img src="${return_image_url}" alt="Return Label Image" style="max-width: 200px;">` : ''}
            </div>
        `);
            } else {
                container.append(`<button class="rc-generate-return-label">${rc_order_packages.label_generate_return_label}</button>`);
            }
        }

        bindColisEvents();
    }

    /**
     * Affiche un message d'erreur et permet de le fermer
     * @param {string} message - Message d'erreur à afficher
     */
    function showError(message) {
        let errorContainer = $('#rc-error-message');
        errorContainer.find('.rc-error-message').text(message); // Ajoute le message
        errorContainer.removeClass('hidden').fadeIn(); // Affiche le message
    }

    // Permet de cacher l'erreur en cliquant sur la croix
    $(document).on('click', '.rc-error-close', function () {
        $('#rc-error-message').fadeOut();
    });

    /**
     * Attach event listeners dynamically
     */
    function bindColisEvents() {

        $(".rc-add-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page

            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_add_colis',
                    order_id: rc_order_id,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    if (response.success) {
                        rc_order_colis = response.data.colis;
                        rc_order_items = response.data.items;
                        renderColisUI();
                    } else {
                        showError(response.data.message || "Erreur inconnue.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : "A network error occurred: " + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-auto-distribute").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page

            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_auto_distribute',
                    order_id: rc_order_id,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    if (response.success) {
                        rc_order_colis = response.data.colis;
                        rc_order_items = response.data.items;
                        renderColisUI();
                    } else {
                        showError(response.data.message || "Erreur inconnue.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : "A network error occurred: " + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-add-to-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page

            let product_id = $(this).data("product-id");
            let quantity = $("#qty_" + product_id).val();
            let colis_index = $("#colis_select_" + product_id).val();

            if (quantity > 0) {

                $.ajax({
                    url: rc_order_packages.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'rc_add_to_colis',
                        order_id: rc_order_id,
                        product_id: product_id,
                        quantity: quantity,
                        colis_index: colis_index,
                        nonce: rc_order_packages.nonce
                    },
                    success: function (response) {
                        if (response.success) {
                            rc_order_colis = response.data.colis;
                            rc_order_items = response.data.items;
                            renderColisUI();
                        } else {
                            showError(response.data.message || "Erreur inconnue.");
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : "A network error occurred: " + textStatus;
                        showError(errorMessage);
                    }
                });
            }

        });

        $(".rc-remove-from-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page

            let product_id = $(this).data("product-id");
            let colis_index = $(this).data("colis-index");

            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_remove_from_colis',
                    order_id: rc_order_id,
                    product_id: product_id,
                    colis_index: colis_index,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    if (response.success) {
                        rc_order_colis = response.data.colis;
                        rc_order_items = response.data.items;
                        renderColisUI();
                    } else {
                        showError(response.data.message || "Erreur inconnue.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : "A network error occurred: " + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-delete-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page

            let colis_index = $(this).data("colis-index");

            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_delete_colis',
                    order_id: rc_order_id,
                    colis_index: colis_index,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    if (response.success) {
                        rc_order_colis = response.data.colis;
                        rc_order_items = response.data.items;
                        renderColisUI();
                    } else {
                        showError(response.data.message || "Erreur inconnue.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : "A network error occurred: " + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-update-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page

            let colis_index = $(this).data("colis-index");
            let new_weight = $(`.rc-colis-weight[data-colis-index="${colis_index}"]`).val();
            let new_height = $(`.rc-colis-dim[data-dim="height"][data-colis-index="${colis_index}"]`).val();
            let new_width = $(`.rc-colis-dim[data-dim="width"][data-colis-index="${colis_index}"]`).val();
            let new_length = $(`.rc-colis-dim[data-dim="length"][data-colis-index="${colis_index}"]`).val();

            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_update_colis',
                    order_id: rc_order_id,
                    colis_index: colis_index,
                    weight: new_weight,
                    height: new_height,
                    width: new_width,
                    length: new_length,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    if (response.success) {
                        rc_order_colis = response.data.colis;
                        renderColisUI();
                    } else {
                        showError(response.data.message || "Erreur inconnue.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : "A network error occurred: " + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-place-shipping-label").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page

            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_place_shipping_label',
                    order_id: rc_order_id,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    if (response.success) {
                        rc_order_colis = response.data.colis;
                        rc_order_items = response.data.items;
                        renderColisUI();
                    } else {
                        showError(response.data.message || "Erreur inconnue.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : "A network error occurred: " + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-generate-return-label").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page

            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_generate_return_label',
                    order_id: rc_order_id,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Mettre à jour les variables globales
                        return_bordereau_smart_url = response.data.return_bordereau_smart_url;
                        return_number = response.data.return_number;
                        return_number_cab = response.data.return_number_cab;
                        return_limit_date = response.data.return_limit_date;
                        return_image_url = response.data.return_image_url;
                        return_token = response.data.return_token;
                        return_created_at = response.data.return_created_at;

                        // Rafraîchir l'interface
                        renderColisUI();
                    } else {
                        showError(response.data.message || "An unknown error occurred.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : "A network error occurred: " + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-get-packages-price").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page

            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_get_packages_price',
                    order_id: rc_order_id,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    if (response.success) {
                        rc_order_colis = response.data.colis;
                        rc_order_items = response.data.items;
                        renderColisUI();
                    } else {
                        showError(response.data.message || "Erreur inconnue.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : "A network error occurred: " + textStatus;
                    showError(errorMessage);
                }
            });
        });

        // Ouvrir la modale
        $(".rc-print-label").off().on("click", function (event) {
            event.preventDefault();

            let pdf_url = $(this).data("pdf-url");

            if (pdf_url) {
                $("#rc-pdf-frame").attr("src", pdf_url);
                $("#rc-pdf-modal").fadeIn();
            } else {
                alert(rc_order_packages.label_no_shipping_label_pdf);
            }
        });

        // Fermer la modale
        $(".rc-close-modal").off().on("click", function () {
            $("#rc-pdf-modal").fadeOut();
        });
    }

    // Initial rendering
    renderColisUI();
});