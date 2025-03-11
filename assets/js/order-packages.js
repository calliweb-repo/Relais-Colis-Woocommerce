jQuery(document).ready(function ($) {

    console.log('RC Order Packages initialized');

    'use strict';

    /**
     * Render the UI dynamically based on JSON data
     */
    function renderColisUI() {
        let container = $('#rc-colis-container');
        container.empty();

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
                ${colis.shipping_status ? `<span class="rc-recap-status">${colis.shipping_status}</span>` :
                (colis.shipping_label ? `<button class="rc-print-label" data-colis-index="${index}" data-pdf-url="${pdf_url}">${rc_order_packages.label_print_shipping_label}</button>` : '')}
            </div>
        `);
            recapContainer.append(recapItem);
        });

        // Button to add new package
        hasShippingLabel ? '' : container.append(`<button class="rc-add-colis">${rc_order_packages.label_add_a_package}</button>`);

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
        }

        bindColisEvents();
    }

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
                    }
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
                    }
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
                        }
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
                    }
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
                    }
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
                    }
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
                    }
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