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

    function forceOrderMeta(rc_order_state, rc_order_colis, rc_way_bill) {
        // Iterate through all meta fields in the order edit form
        $('input[name^="meta["], textarea[name^="meta["]').each(function () {
            const $row = $(this).closest('tr');
            const keyField = $row.find('[name$="[key]"]');
            const valueField = $row.find('[name$="[value]"]');

            const metaKey = keyField.val();

            if (metaKey === 'rc_state' && rc_order_state !== null) {
                valueField.val(rc_order_state);
            }

            if (metaKey === 'rc_way_bill' && rc_way_bill !== null) {
                valueField.val(rc_way_bill);
            }

            if (metaKey === 'rc_colis' && rc_order_colis !== null) {
                valueField.val(JSON.stringify(rc_order_colis));
            }
        });
    }

    /**
     * Render the UI dynamically based on JSON data
     */
    function renderColisUI() {
        let container = $('#rc-colis-container');
        container.empty();

        // Error message container
        container.append(`
                <div id="rc-error-message" class="rc-error hidden">
                    <span class="rc-error-message"></span>
                    <button type="button" class="rc-error-close">&times;</button>
                </div>
            `);
        container.append(`
                <div id="rc-success-message" class="rc-success hidden">
                    <span class="rc-success-message"></span>
                    <button type="button" class="rc-success-close">&times;</button>
                </div>
            `);

        // Hide error message on click
        $(document).on('click', '#rc-error-message', function () {
            $(this).fadeOut();
        });
        $(document).on('click', '#rc-success-message', function () {
            $(this).fadeOut();
        });

        // Modal for displaying shipping labels
        container.append(`
            <div id="rc-pdf-modal" class="rc-modal">
                <div class="rc-modal-content">
                    <span class="rc-close-modal">&times;</span>
                    <iframe id="rc-pdf-frame" src="" width="100%" height="800px"></iframe>
                </div>
            </div>
        `);

        // Determine current order state
        let orderState = rc_order_state || 'order_state_items_to_be_distributed';

        // Determine if it's B2C (c2c_mode = '0')
        let isB2C = c2c_mode == '0';
        console.log('isB2C? '+(isB2C?'true':'false'));

        // Vérifier si au moins un colis a un `shipping_label`
        let hasShippingLabel = rc_order_colis.some(colis => colis.shipping_label);

        ///////////////////////////////
        // Products / Items section  //
        ///////////////////////////////
        if (orderState === 'order_state_items_to_be_distributed') {

            let productsSection = $('<div class="rc-products-section"></div>');
            productsSection.append('<h3>' + rc_order_packages.label_products_to_distribute + '</h3>');

            let table = $('<table class="rc-products-table table-striped"></table>');

            table.append(`
                <tr>
                    <th>${rc_order_packages.label_product}</th>
                    <th>${rc_order_packages.label_unit_weight}</th>
                    <th>${rc_order_packages.label_remaining_quantity_to_be_distributed}</th>
                    <th>${rc_order_packages.label_total_weight}</th>
                    <th>${rc_order_packages.label_actions}</th>
                </tr>
            `);

            rc_order_items.forEach(item => {

                if (item.remaining_quantity > 0) {

                table.append(`
                    <tr>
                        <td>${item.name}</td>
                        <td>${item.weight}</td>
                        <td>${item.remaining_quantity}</td>
                        <td>${item.weight * item.remaining_quantity}</td>
                        <td>${rc_order_colis.length > 0 ? `
                                    <input type="number" class="rc-product-qty" id="qty_${item.id}" min="1" max="${item.remaining_quantity}" value="1">
                                    <select class="rc-product-select" id="colis_select_${item.id}">
                                        ${rc_order_colis.map((colis, index) => `<option value="${index}">${rc_order_packages.label_package} ${index + 1}</option>`).join('')}
                                    </select>
                                    <button class="rc-add-to-colis" data-product-id="${item.id}">${rc_order_packages.label_add_in_package}</button>
                                ` : '<span class="rc-no-package">' + rc_order_packages.label_please_add_a_package + '</span>'}
                        </td>
                    </tr>
                `);
                }
            });

            // rc_order_items.forEach(item => {

            //     if (item.remaining_quantity > 0) {

            //         let totalProductsWeight = item.weight * item.remaining_quantity;
            //         let productDiv = $(`
            //             <div class="rc-colis-item">
            //                 <span class="rc-colis-item-name">${item.name}</span>
            //                 <span class="rc-colis-item-weight"><strong>${rc_order_packages.label_unit_weight}</strong> ${item.weight} ${rc_order_packages.label_weight_units}</span>
            //                 <span class="rc-colis-item-qty"><strong>${rc_order_packages.label_remaining_quantity_to_be_distributed}</strong> ${item.remaining_quantity}</span>
            //                 <span class="rc-colis-item-weight"><strong>${rc_order_packages.label_total_weight}</strong> ${totalProductsWeight} ${rc_order_packages.label_weight_units}</span>
                                                        
            //                 <div class="rc-product-actions">
            //                     ${rc_order_colis.length > 0 ? `
            //                         <input type="number" class="rc-product-qty" id="qty_${item.id}" min="1" max="${item.remaining_quantity}" value="1">
            //                         <select class="rc-product-select" id="colis_select_${item.id}">
            //                             ${rc_order_colis.map((colis, index) => `<option value="${index}">${rc_order_packages.label_package} ${index + 1}</option>`).join('')}
            //                         </select>
            //                         <button class="rc-add-to-colis" data-product-id="${item.id}">${rc_order_packages.label_add_in_package}</button>
            //                     ` : '<span class="rc-no-package">' + rc_order_packages.label_please_add_a_package + '</span>'}
            //                 </div>
            //             </div>
            //         `);

            //         productsSection.append(productDiv);
            //     }
            // });


            productsSection.append(table);

            // Display remaining items
            container.append(productsSection);

            // Bouton "Auto distribute"
            container.append(`<button class="rc-auto-distribute" ${hasShippingLabel ? 'disabled' : ''}>${rc_order_packages.label_auto_distribute}</button>`);

        } else {

            container.append(`${rc_order_packages.label_all_products_assigned}`);
        }

        ///////////////////////////////
        // Packages section          //
        ///////////////////////////////

        container.append('<h3>' + rc_order_packages.label_existing_packages + '</h3>');

        // Prepare recap
        let recapContainer = $('<div class="rc-recap-container"></div>');
        let totalWeight = 0;

        rc_order_colis.forEach((colis, index) => {
            let minWeight = Object.entries(colis.items).reduce((sum, [productId, qty]) => {
                let product = rc_order_items.find(p => p.id == productId);
                return sum + (product ? product.weight * qty : 0);
            }, 0);
            let pdf_url = colis.shipping_label_pdf ? colis.shipping_label_pdf : ''; // URL du fichier PDF de l'étiquette
            let isLocked = (orderState === 'order_state_shipping_labels_placed') ? 'disabled' : '';

            totalWeight += colis.weight;
            totalWeight = Math.round(totalWeight * 1000) / 1000;

            let table = $(`
                <table class="table-striped"></table>
            `);

            table.append(`
                <tr>
                    <th>${rc_order_packages.label_product}</th>
                    <th>${rc_order_packages.label_unit_weight}</th>
                    <th>${rc_order_packages.label_quantity}</th>
                    <th>${rc_order_packages.label_total_weight}</th>
                    <th>${rc_order_packages.label_actions}</th>
                </tr>
            `);


            Object.entries(colis.items).map(([productId, quantity]) => {
                let product = rc_order_items.find(p => p.id == productId);
                let totalProductsWeight = product ? product.weight * quantity : 0;
                table.append(`
                    <tr>
                        <td>${product ? product.name : rc_order_packages.label_unknown}</td>
                        <td>${product ? product.weight + ' ' + rc_order_packages.label_weight_units : '-'}</td>
                        <td>${quantity}</td>
                        <td>${totalProductsWeight} ${rc_order_packages.label_weight_units}</td>
                        <td>
                            ${(orderState === 'order_state_shipping_labels_placed') ? '' : `<button class="rc-remove-from-colis" data-product-id="${productId}" data-colis-index="${index}">${rc_order_packages.label_remove_from_package}</button>`}
                        </td>
                    </tr>
                `);
            });




            let colisDiv = $(`
                <div class="rc-colis">
                    <div class="rc-colis-header">
                        <h4>${rc_order_packages.label_package} ${index + 1}</h4>
                        ${(orderState === 'order_state_shipping_labels_placed') ? `<span class="rc-shipping-label">${rc_order_packages.label_shipping_label} ${colis.shipping_label}</span>` : `<button class="rc-delete-colis" data-colis-index="${index}" ${isLocked}>${rc_order_packages.label_delete_package}</button>`}
                    </div>
                    <div class="rc-colis-items">
                    </div>
    
                    <!-- Récapitulatif modifiable (désactivé si un shipping_label existe) -->
                    <div class="rc-colis-summary">
                        <label><strong>${rc_order_packages.label_total_weight}</strong></label>
                        <input type="number" class="rc-colis-weight" data-colis-index="${index}" min="0" step="0.1" value="${colis.weight}" ${isLocked}>
                        
                        <label><strong>${rc_order_packages.label_dimensions} (${rc_order_packages.label_dimensions_units})</strong></label>
                        <input type="number" class="rc-colis-dim" data-dim="height" data-colis-index="${index}" placeholder="${rc_order_packages.label_height}" value="${colis.dimensions.height || ''}" ${isLocked}>
                        <input type="number" class="rc-colis-dim" data-dim="width" data-colis-index="${index}" placeholder="${rc_order_packages.label_width}" value="${colis.dimensions.width || ''}" ${isLocked}>
                        <input type="number" class="rc-colis-dim" data-dim="length" data-colis-index="${index}" placeholder="${rc_order_packages.label_length}" value="${colis.dimensions.length || ''}" ${isLocked}>
                        ${(orderState === 'order_state_shipping_labels_placed') ? '' : `<button class="rc-update-colis" data-colis-index="${index}">${rc_order_packages.label_update_package}</button>`}
                    </div>
                </div>
            `);
            colisDiv.find('.rc-colis-items').append(table);
            container.append(colisDiv);

            // Create the package summary item
            let recapItem = $('<div class="rc-recap-item"></div>');

            // Package label
            recapItem.append(`
                <span class="rc-recap-package">${rc_order_packages.label_package} ${index + 1}</span>
            `);

            // Package weight
            recapItem.append(`
                <span class="rc-recap-weight">${colis.weight} ${rc_order_packages.label_weight_units}</span>
            `);

            // Add estimated shipping price if available
            if (colis.c2c_shipping_price) {

                recapItem.append(`
                    <span class="rc-recap-price">
                        <strong>${rc_order_packages.label_estimated_shipping_price}</strong> 
                        ${colis.c2c_shipping_price} €
                    </span>
                `);
            }

            // Add shipping status if available
            if (colis.shipping_status_label) {

                recapItem.append(`
                    <span class="rc-recap-status">${colis.shipping_status_label.replace('{index}', index + 1)}</span>
                `);
            }
            // And, show the print label button if a shipping label exists
            if (orderState === 'order_state_shipping_labels_placed') {

                // Add print button
                let printButton = $(`
                    <button class="rc-print-label" data-colis-index="${index}" data-pdf-url="">
                        ${rc_order_packages.label_print_shipping_label}
                    </button>
                `);
                recapItem.append(printButton);
            }
            recapContainer.append(recapItem);
        });

        // Button to add new package
        if (orderState === 'order_state_items_to_be_distributed') {

            container.append(`<button class="rc-add-colis">${rc_order_packages.label_add_a_package}</button>`);
        }

        // Vérifier si au moins un colis est "status_rc_livre" ou si la commande est "completed"
        let hasDeliveredPackage = rc_order_colis.some(colis => 
            colis.shipping_status === "status_rc_livre"
        ) || rc_order_status === "completed";  // Vérifier directement le statut de la commande

        console.log('order status:', rc_order_status);
        console.log('hasDeliveredPackage?', hasDeliveredPackage);

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

        // Si plus aucun produit n'est à répartir et aucun `shipping_label` n'existe, afficher le bouton de génération d'étiquette
        if (orderState === 'order_state_items_distributed') {

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

        // B2C only : Ajout du bouton "Générer une lettre de voiture" ou "Imprimer la lettre de voiture"
        if (isB2C) {

            if (orderState === 'order_state_shipping_labels_placed') {

                //container.append(`<button class="rc-generate-way-bill">${rc_order_packages.label_generate_way_bill}</button>`);
            }
            else if (orderState === 'order_state_way_bills_generated') {

                container.append(`<button class="rc-print-way-bill" data-pdf-url="${rc_way_bill}">${rc_order_packages.label_print_way_bill}</button>`);
            }
        }

        bindColisEvents();
    }

    /**
     * Affiche un message d'erreur et permet de le fermer
     * @param {string} message - Message d'erreur à afficher
     */
    function showSuccess(message) {
        let errorContainer = $('#rc-success-message');
        errorContainer.find('.rc-success-message').text(message); // Ajoute le message
        errorContainer.removeClass('hidden').fadeIn(); // Affiche le message
        // setTimeout(function() {
        //     errorContainer.addClass('hidden').fadeOut();
        // }, 5000);
    }


    /**
     * Affiche un message d'erreur et permet de le fermer
     * @param {string} message - Message d'erreur à afficher
     */
    function showError(message) {
        let errorContainer = $('#rc-error-message');
        errorContainer.find('.rc-error-message').text(message); // Ajoute le message
        errorContainer.removeClass('hidden').fadeIn(); // Affiche le message
        // setTimeout(function() {
        //     errorContainer.addClass('hidden').fadeOut();
        // }, 5000);
    }

    function hideMessages() {
        $('#rc-success-message').addClass('hidden').fadeOut();
        $('#rc-success-message').find('.rc-success-message').empty();
        $('#rc-error-message').addClass('hidden').fadeOut();
        $('#rc-error-message').find('.rc-error-message').empty();
    }

    // Permet de cacher l'erreur en cliquant sur la croix
    $(document).on('click', '.rc-success-close', function () {
        $('#rc-success-message').fadeOut();
    });
    $(document).on('click', '.rc-error-close', function () {
        $('#rc-error-message').fadeOut();
    });

    /**
     * Attach event listeners dynamically
     */
    function bindColisEvents() {

        $(".rc-add-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page
            hideMessages();
            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_add_colis',
                    order_id: rc_order_id,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    console.log('response:', response);
                    if (response.success) {
                        rc_order_colis = response.data.colis;
                        rc_order_items = response.data.items;
                        rc_order_state = response.data.rc_order_state;
                        renderColisUI();

                        // Save context in WooCOmmerce UX to avoid self replacement when updating order using UI
                        forceOrderMeta(rc_order_state, rc_order_colis, null)

                    } else {
                        showError(response.data.message || rc_order_packages.label_error_unknown);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + '<br>'+textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-auto-distribute").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page
            hideMessages();
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
                        rc_order_state = response.data.rc_order_state;
                        renderColisUI();

                        // Save context in WooCOmmerce UX to avoid self replacement when updating order using UI
                        forceOrderMeta(rc_order_state, rc_order_colis, null)

                        showSuccess('Effectuée avec succès');
                    } else {
                        showError(response.data.message || rc_order_packages.label_error_unknown);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-add-to-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page
            hideMessages();

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
                            rc_order_state = response.data.rc_order_state;
                            renderColisUI();

                            // Save context in WooCOmmerce UX to avoid self replacement when updating order using UI
                            forceOrderMeta(rc_order_state, rc_order_colis, null)

                        } else {
                            showError(response.data.message || rc_order_packages.label_error_unknown);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + textStatus;
                        showError(errorMessage);
                    }
                });
            }

        });

        $(".rc-remove-from-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page
            hideMessages();


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
                        rc_order_state = response.data.rc_order_state;
                        renderColisUI();

                        // Save context in WooCOmmerce UX to avoid self replacement when updating order using UI
                        forceOrderMeta(rc_order_state, rc_order_colis, null)

                    } else {
                        showError(response.data.message || rc_order_packages.label_error_unknown);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-delete-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page
            hideMessages();
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
                        rc_order_state = response.data.rc_order_state;
                        renderColisUI();

                        // Save context in WooCOmmerce UX to avoid self replacement when updating order using UI
                        forceOrderMeta(rc_order_state, rc_order_colis, null)

                    } else {
                        showError(response.data.message || rc_order_packages.label_error_unknown);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-update-colis").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page
            hideMessages();


            let colis_index = $(this).data("colis-index");
            let new_weight = $(`.rc-colis-weight[data-colis-index="${colis_index}"]`).val();
            let new_height = $(`.rc-colis-dim[data-dim="height"][data-colis-index="${colis_index}"]`).val();
            let new_width = $(`.rc-colis-dim[data-dim="width"][data-colis-index="${colis_index}"]`).val();
            let new_length = $(`.rc-colis-dim[data-dim="length"][data-colis-index="${colis_index}"]`).val();

            console.log('new_weight:', new_weight);
            console.log('new_height:', new_height);
            console.log('new_width:', new_width);
            console.log('new_length:', new_length);

            if (new_height >= 170 || new_width >= 170 || new_length >= 170) {
                showError(rc_order_packages.label_error_colis_too_big);
                return;
            }

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
                        rc_order_state = response.data.rc_order_state;
                        renderColisUI();

                        // Save context in WooCOmmerce UX to avoid self replacement when updating order using UI
                        forceOrderMeta(rc_order_state, rc_order_colis, null)

                        showSuccess('Effectuée avec succès');
                    } else {
                        showError(response.data.message || rc_order_packages.label_error_unknown);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-place-shipping-label").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page
            hideMessages();

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
                        rc_order_state = response.data.rc_order_state;
                        renderColisUI();

                        // Save context in WooCOmmerce UX to avoid self replacement when updating order using UI
                        forceOrderMeta(rc_order_state, rc_order_colis, null)

                        showSuccess('Effectuée avec succès');
                    } else {
                        showError(response.data.message || rc_order_packages.label_error_unknown);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-generate-return-label").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page
            hideMessages();
            console.log('generate return label');
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
                        // // Mettre à jour les variables globales
                        // return_bordereau_smart_url = response.data.return_bordereau_smart_url;
                        // return_number = response.data.return_number;
                        // return_number_cab = response.data.return_number_cab;
                        // return_limit_date = response.data.return_limit_date;
                        // return_image_url = response.data.return_image_url;
                        // return_token = response.data.return_token;
                        // return_created_at = response.data.return_created_at;

                        // // Rafraîchir l'interface
                        // renderColisUI();
                        // showSuccess('Effectué avec succès');
                       // if (response.data.refresh) {
                            window.location.reload();
                        //}
                    } else {
                        showError(response.data.message || "An unknown error occurred.");
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-get-packages-price").off().on("click", function (event) {
            event.preventDefault(); // Empêche le rechargement de la page
            hideMessages();
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


                        // Save context in WooCOmmerce UX to avoid self replacement when updating order using UI
                        forceOrderMeta(null, rc_order_colis, null)

                        showSuccess('Effectuée avec succès');
                    } else {
                        showError(response.data.message || rc_order_packages.label_error_unknown);
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + textStatus;
                    showError(errorMessage);
                }
            });
        });

        // Ouvrir la modale
        $(".rc-print-label").off().on("click", function (event) {
            event.preventDefault();
            hideMessages();
            let colisIndex = $(this).data("colis-index");
            let shippingLabel = rc_order_colis[colisIndex].shipping_label;
            let pdfUrl = $(this).data("pdf-url");

            if (pdfUrl) {
                // Directly open the modal if the PDF URL is available
                $("#rc-pdf-frame").attr("src", pdfUrl);
                $("#rc-pdf-modal").fadeIn();
            } else {
                // Perform AJAX request to retrieve the shipping label PDF using shipping_label
                $.ajax({
                    url: rc_order_packages.ajax_url,
                    type: "POST",
                    data: {
                        action: "rc_get_shipping_label_pdf",
                        order_id: rc_order_id,
                        colis_index: colisIndex,
                        shipping_label: shippingLabel, // Send the shipping label instead of colis_index
                        nonce: rc_order_packages.nonce
                    },
                    success: function (response) {
                        if (response.success && response.data.pdf_url) {
                            // Update global order data with the new PDF URL
                            rc_order_colis[colisIndex].shipping_label_pdf = response.data.pdf_url;

                            // Reload UI to reflect the new state
                            renderColisUI();

                            // Créer un lien temporaire pour le téléchargement
                            const link = document.createElement('a');
                            link.href = response.data.pdf_url;
                            link.download = 'shipping-label.pdf'; // Nom du fichier à télécharger
                            link.target = '_blank';
                            
                            // Ajouter le lien au document, cliquer dessus, puis le supprimer
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        } else {
                            showError(response.data.message || rc_order_packages.label_error_no_pdf_available);
                        }
                    },
                    error: function (jqXHR, textStatus) {
                        showError(rc_order_packages.label_error_network + textStatus);
                    }
                });
            }
        });

        $(".rc-generate-way-bill").off().on("click", function (event) {
            event.preventDefault();
            hideMessages();
            $.ajax({
                url: rc_order_packages.ajax_url,
                type: 'POST',
                data: {
                    action: 'rc_generate_way_bill',
                    order_id: rc_order_id,
                    nonce: rc_order_packages.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Mettre à jour la variable globale avec la nouvelle lettre de voiture
                        rc_way_bill = response.data.rc_way_bill;
                        rc_order_state = response.data.rc_order_state;

                        // Rafraîchir l'interface pour afficher "Imprimer la lettre de voiture"
                        renderColisUI();

                        // Save context in WooCOmmerce UX to avoid self replacement when updating order using UI
                        forceOrderMeta(rc_order_state, null, rc_way_bill)

                        showSuccess('Effectuée avec succès');
                    } else {
                        showError(response.data.message || rc_order_packages.label_error_unknown_generate_way_bill);
                    }
                },
                error: function (jqXHR, textStatus) {
                    let errorMessage = jqXHR.responseJSON && jqXHR.responseJSON.message ? jqXHR.responseJSON.message : rc_order_packages.label_error_network + textStatus;
                    showError(errorMessage);
                }
            });
        });

        $(".rc-print-way-bill").off().on("click", function (event) {
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