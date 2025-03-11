jQuery(document).ready(function ($) {

    console.log('🚀 RC Choose Options init - FSE Checkout');

    "use strict";

    if (typeof rc_choose_options_h === 'undefined' || typeof rc_choose_options_hp === 'undefined') {
        console.error('❌ rc_choose_options_h or rc_choose_options_hp is undefined. Check if wp_localize_script() is properly set.');
        return;
    }

    /**
     * Detect the selected shipping method
     */
    function getSelectedShippingMethod() {
        let selectedMethod = null;

        $('input[type="radio"][name="radio-control-0"]:checked').each(function () {
            if ($(this).val() === 'wc_rc_shipping_method_home') {
                selectedMethod = 'home';
            } else if ($(this).val() === 'wc_rc_shipping_method_homeplus') {
                selectedMethod = 'homeplus';
            } else if ($(this).val() === 'wc_rc_shipping_method_relay') {
                selectedMethod = 'relay';
            }
        });

        console.log("Selected method is "+selectedMethod);
        return selectedMethod;
    }

    /**
     * Get AJAX params depending on selected mode
     */
    function getAjaxParams() {
        const selectedMethod = getSelectedShippingMethod();
        return selectedMethod === 'home'
            ? { ajax_url: rc_choose_options_h.ajax_url, nonce: rc_choose_options_h.nonce, html: rc_choose_options_h.html, div_id: rc_choose_options_h.div_id }
            : { ajax_url: rc_choose_options_hp.ajax_url, nonce: rc_choose_options_hp.nonce, html: rc_choose_options_hp.html, div_id: rc_choose_options_hp.div_id };
    }

    /**
     * Affiche/Masque les options de livraison en fonction du choix de l'utilisateur
     */
    function checkRCFseShippingMethod(force = false) {

        const selectedMethod = getSelectedShippingMethod();

        if (selectedMethod === 'home') {

            if (!$('#'+rc_choose_options_h.div_id).length || force) {

                console.log("✅ Adding block Relais Colis...");
                $('.wc-block-components-shipping-rates-control').after(rc_choose_options_h.html);
            }
            $('#'+rc_choose_options_h.div_id).show();
            $('#'+rc_choose_options_hp.div_id).hide();

        } else if (selectedMethod === 'homeplus') {

            if (!$('#'+rc_choose_options_hp.div_id).length || force) {

                console.log("✅ Adding block Relais Colis...");
                $('.wc-block-components-shipping-rates-control').after(rc_choose_options_hp.html);
            }
            $('#'+rc_choose_options_hp.div_id).show();
            $('#'+rc_choose_options_h.div_id).hide();
        } else {

            $('#'+rc_choose_options_hp.div_id).hide();
            $('#'+rc_choose_options_h.div_id).hide();

            // Forcer un reset AJAX des services et frais
            //resetRCOldSelectedServices();
        }
    }

    /**
     * Update WooCommerce with selected services
     */
    function updateRCFseSelectedServices() {
        const params = getAjaxParams();
        const selectedMethod = getSelectedShippingMethod();
        let selectedServiceFees = []; // To send rc_services_* in li.service-fee
        let selectedServiceInfos = {}; // To send rc_services_* in li.service-info

        // Sélection du bon container selon la méthode de livraison
        let containerId;
        if ( selectedMethod === 'home' ) { containerId = '#'+rc_choose_options_h.div_id }
        else if ( selectedMethod === 'homeplus' ) { containerId = '#'+rc_choose_options_hp.div_id }
        else if ( selectedMethod === 'relay' ) { containerId = '#relais-colis-block' }
        let container = $(containerId);

        if (!container.length) {
            console.warn(`⚠️ Aucun container trouvé pour la méthode ${selectedMethod}`);
            return;
        }

        // Gestion des différents types de champs rc_services_* in li.service-fee
        container.find('li.service-fee input[name^="rc_service_"]').each(function () {
            let fieldType = $(this).attr('type');

            if (fieldType === 'checkbox' && $(this).is(':checked')) {
                selectedServiceFees.push($(this).attr('id'));
            }
        });

        // Gestion des différents types de champs rc_services_* in li.service-info
        container.find('li.service-info input[name^="rc_service_"], li.service-info select[name^="rc_service_"], li.service-info textarea[name^="rc_service_"]').each(function () {
            let fieldType = $(this).attr('type');
            let fieldId = $(this).attr('id');
            let fieldValue = $(this).val().trim();

            if (fieldType === 'checkbox') {
                selectedServiceInfos[fieldId] = $(this).is(':checked') ? '1' : '0';
            } else if (fieldValue) {
                selectedServiceInfos[fieldId] = fieldValue;
            }
        });

        console.log(`✅ Services sélectionnés pour ${selectedMethod}:`, selectedServiceFees);
        console.log(`✅ Services infos sélectionnés pour ${selectedMethod}:`, selectedServiceInfos);

        // Envoi AJAX à WooCommerce
        $.ajax({
            url: params.ajax_url,
            dataType: 'json',
            method: 'POST',
            data: {
                action: 'update_rc_options',
                nonce: params.nonce,
                rc_services: selectedServiceFees,
                rc_service_infos: selectedServiceInfos,
            },
            success: function () {
                console.log("✅ Options mises à jour, rafraîchissement du checkout.");
                forceRCFseWooCommerceRefresh();
            },
            error: function (xhr) {
                console.error("⚠️ Erreur mise à jour options :", xhr.responseText);
            }
        });
    }

    /**
     * Réinitialise les options lors du changement de mode de livraison
     */
    function resetRCFseSelectedServices() {
        console.log("🔄 Réinitialisation des services...");
        const params = getAjaxParams();

        $('li.service-fee input[name^="rc_service_"]').prop('checked', false);
        $('li.service-info input[name^="rc_service_"], li.service-info select[name^="rc_service_"], li.service-info textarea[name^="rc_service_"]').each(function () {
            let fieldType = $(this).attr('type');
            if (fieldType === 'checkbox') {
                $(this).prop('checked', false);
            } else {
                $(this).val('');
            }
        });
/*
        // Reset via AJAX
        $.ajax({
            url: params.ajax_url,
            dataType: 'json',
            method: 'POST',
            data: {
                action: 'reset_rc_infos',
                nonce: params.nonce
            },
            success: function () {
                console.log("✅ Reset AJAX réussi.");
            },
            error: function (xhr) {
                console.error("⚠️ Erreur reset options :", xhr.responseText);
            }
        });*/
    }

    /**
     * Force WooCommerce Blocks à rafraîchir le panier
     */
    function forceRCFseWooCommerceRefresh() {
        console.log("🔄 Rafraîchissement WooCommerce Blocks...");

        if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined') {
            setTimeout(() => {
                const cartStore = wp.data.dispatch('wc/store/cart');
                cartStore.invalidateResolution('getCartData');
                cartStore.invalidateResolution('getCartTotals');
                cartStore.invalidateResolution('getShippingRates');
                cartStore.invalidateResolution('getPaymentMethods');
            }, 500);
        } else {
            console.warn("⚠️ WooCommerce Blocks Redux non disponible !");
        }
    }

    /**
     * Initialisation
     ***/
    setTimeout(() => {
        console.log("⏳ Vérification post-chargement du mode de livraison...");
        checkRCFseShippingMethod(true);
        resetRCFseSelectedServices();
        updateRCFseSelectedServices();
    }, 400);

    /**
     * Ajout des écouteurs d'événements
     */
    $(document.body).on('wc-blocks-checkout-update wc-blocks-order-review-update', function () {
        console.log("🔄 WooCommerce Blocks mise à jour détectée.");
        checkRCFseShippingMethod();
    });

    // Écouteurs d'événements
    $(document).on('change', 'input[type="radio"][name="radio-control-0"]', function () {
        checkRCFseShippingMethod();
        setTimeout(() => {
            console.log("⏳ Vérification post-chargement du mode de livraison...");
            resetRCFseSelectedServices();
            updateRCFseSelectedServices();
        }, 400);
    });

    $(document).on('change', 'input[name^="rc_service_"], select[name^="rc_service_"], textarea[name^="rc_service_"]', function () {
        $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
            if (options.url.indexOf('wc-ajax=update_order_review') !== -1) {
                options.async = false;  // Force la mise à jour à être synchrone
            }
        });

        updateRCFseSelectedServices();
    });
});