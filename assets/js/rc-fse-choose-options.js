function getSelectedShippingMethod() {

    let selectedMethod = null;
    let selectedValue = null;

    // Cas WooCommerce FSE Checkout (nouveau système)
    let radioSelected = jQuery('input[type="radio"][name^="radio-control-"]:checked');

    if (radioSelected.length > 0) {
        // Plusieurs choix : on prend la valeur du radio sélectionné
        selectedValue = radioSelected.val();
    } else {
        // Cas où il y a un seul mode de livraison, qui n'est pas forcément "checked"
        let singleMethod = jQuery('input[type="radio"][name^="radio-control-"]');

        if (singleMethod.length === 1) {
            selectedValue = singleMethod.val();
        }
    }

    // Mapping des valeurs WooCommerce vers nos identifiants internes
    switch (selectedValue) {
        case 'wc_rc_shipping_method_home':
            selectedMethod = 'home';
            break;
        case 'wc_rc_shipping_method_homeplus':
            selectedMethod = 'homeplus';
            break;
        case 'wc_rc_shipping_method_relay':
            selectedMethod = 'relay';
            break;
        default:
            break;
    }

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
 * HTML code for Checkout button
 * @returns {string}
 */
function getRelayColisHtml() {
    return `
        <div id="relais-colis-block">
            <button id="btnChooseRelay" class="rc-btn rc-btn-primary">
                Choisir un point relais colis de destination
            </button>
            <div id="selected-relay-info">
                <strong>Relais sélectionné :</strong>
                <p id="selected-relay-name"></p>
                <p id="selected-relay-address"></p>
                <p id="selected-relay-zip-city"></p>
            </div>
        </div>
    `;
}

/**
 * Affiche/Masque les options de livraison en fonction du choix de l'utilisateur
 */
function checkRCFseShippingMethod(force = false) {

    const selectedMethod = getSelectedShippingMethod();
    if (selectedMethod === null) {
        setTimeout(function() {
            checkRCFseShippingMethod();
            resetRCFseSelectedServices();
        }, 500);
    }
    console.log('checkRCFseShippingMethod');
    console.log('selectedMethod', selectedMethod);
    if (selectedMethod === 'home') {
        if (!jQuery('#' + rc_choose_options_h.div_id).length || force) {
            jQuery('.wc-block-components-shipping-rates-control').after(rc_choose_options_h.html);
        }
        jQuery('#' + rc_choose_options_h.div_id).show();
        jQuery('#' + rc_choose_options_hp.div_id).hide();
        jQuery('#relais-colis-block').remove();

    } else if (selectedMethod === 'homeplus') {
        if (!jQuery('#' + rc_choose_options_hp.div_id).length || force) {
            jQuery('.wc-block-components-shipping-rates-control').after(rc_choose_options_hp.html);
        }
        jQuery('#' + rc_choose_options_hp.div_id).show();
        jQuery('#' + rc_choose_options_h.div_id).hide();
        jQuery('#relais-colis-block').remove();

    } else if (selectedMethod === 'relay') {
        console.log('relay');
        jQuery('#' + rc_choose_options_hp.div_id).hide();
        jQuery('#' + rc_choose_options_h.div_id).hide();

        if (!jQuery('#relais-colis-block').length || force) {
            jQuery('.wc-block-components-shipping-rates-control').after(getRelayColisHtml());
        }
        jQuery('#relais-colis-block').show();
    } else {
        // Cas d'un mode de livraison autre (Colissimo, retrait magasin, etc.)
        jQuery('#' + rc_choose_options_h.div_id).hide();
        jQuery('#' + rc_choose_options_hp.div_id).hide();
        jQuery('#relais-colis-block').remove();
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
    let container = jQuery(containerId);

    if (!container.length) {
        return;
    }

    // Gestion des différents types de champs rc_services_* in li.service-fee
    container.find('li.service-fee input[name^="rc_service_"]').each(function () {
        let fieldType = jQuery(this).attr('type');

        if (fieldType === 'checkbox' && jQuery(this).is(':checked')) {
            selectedServiceFees.push(jQuery(this).attr('id'));
        }
    });

    // Gestion des différents types de champs rc_services_* in li.service-info
    container.find('li.service-info input[name^="rc_service_"], li.service-info select[name^="rc_service_"], li.service-info textarea[name^="rc_service_"]').each(function () {
        let fieldType = jQuery(this).attr('type');
        let fieldId = jQuery(this).attr('id');
        let fieldValue = jQuery(this).val().trim();

        if (fieldType === 'checkbox') {
            selectedServiceInfos[fieldId] = jQuery(this).is(':checked') ? '1' : '0';
        } else if (fieldValue) {
            selectedServiceInfos[fieldId] = fieldValue;
        }
    });

    // Sauvegarde temporaire en localStorage -> Not in FSE mode
    //localStorage.setItem('rc_selected_service_fees', JSON.stringify(selectedServiceFees));
    //localStorage.setItem('rc_selected_service_infos', JSON.stringify(selectedServiceInfos));

    // Envoi AJAX à WooCommerce
    jQuery.ajax({
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
            lastUpdateShippingRequest = selectedMethod;
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

    // Local storage reset -> not in FSE
    //localStorage.setItem('rc_selected_service_fees', JSON.stringify([]));
    //localStorage.setItem('rc_selected_service_infos', JSON.stringify({}));

    jQuery('li.service-fee input[name^="rc_service_"]').prop('checked', false);
    jQuery('li.service-info input[name^="rc_service_"], li.service-info select[name^="rc_service_"], li.service-info textarea[name^="rc_service_"]').each(function () {
        let fieldType = jQuery(this).attr('type');
        if (fieldType === 'checkbox') {
            jQuery(this).prop('checked', false);
        } else {
            jQuery(this).val('');
        }
    });
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

jQuery(window).on("load", function () {
    console.log("✅ window.load triggered");
   // checkRCFseShippingMethod();
   // resetRCFseSelectedServices();

    let lastUpdateShippingRequest = null; // Stocke la dernière méthode de livraison sélectionnée

    /**
     * Interception de la requête WooCommerce select-shipping-rate
     * Cette requete est effectuée en amont du refresh cart et auto par WooCOmmerce...
     */
    const originalFetch = window.fetch;
    window.fetch = async function (...args) {
        console.log("🕵️ Interception fetch:", args[0]); // URL de la requête
        let url = typeof args[0] === 'string' ? args[0] : args[0].url;

        // Vérifie si c'est la requête de sélection de mode de livraison
        if (typeof url === "string" && url.includes('/wp-json/wc/store/v1/cart/select-shipping-rate')) {
            console.log("🚀 Interception de la requête select-shipping-rate:", url);

            let currentShippingMethod = getSelectedShippingMethod();

            // Vérifie si la méthode a changé pour ajouter rc_reset_infos
            if (typeof window.lastUpdateShippingRequest === "undefined" || window.lastUpdateShippingRequest !== currentShippingMethod) {
                console.log("🔄 La méthode de livraison a changé, ajout de rc_reset_infos=1.");
                let separator = url.includes('?') ? '&' : '?';
                url += separator + 'rc_reset_infos=1';
            }

            // Remplace l'URL par la nouvelle version modifiée
            args[0] = url;
        }

        // Blocage si validation sans relais sélectionné
        // 🕵️ Interception fetch:"https://calliweb.sukellos.fr/wp-json/wc/store/v1/checkout?_locale=site"
        if ( (typeof url === "string" && url.includes('/wp-json/wc/store/v1/checkout')) || (url.includes('/wp-json/wc/store/v1/checkout')) ) {
            console.log("🛑 Tentative de validation de commande...");

            const selectedMethod = getSelectedShippingMethod();
            const relayId = document.querySelector('#selected-relay-name')?.textContent.trim();

            console.log('Mode de livraison:', selectedMethod);
            console.log('Relay ID:', relayId);

            if (selectedMethod === 'relay' && (!relayId || relayId === '')) {
                console.warn("❌ Validation bloquée : aucun point relais sélectionné.");

                // Simuler une réponse rejetée avec message d’erreur WooCommerce
                return Promise.resolve(new Response(JSON.stringify({
                    code: "no_relay_selected",
                    message: rc_choose_options.label_please_select_relay,
                    data: {
                        status: 400
                    }
                }), {
                    status: 400,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }));
            }
        }

        return originalFetch.apply(this, args);
    };


});

jQuery(document).ready(function ($) {

    console.log('🚀 RC Choose Options init - FSE Checkout');

    "use strict";

    if (typeof rc_choose_options_h === 'undefined' || typeof rc_choose_options_hp === 'undefined') {
        console.error('❌ rc_choose_options_h or rc_choose_options_hp is undefined. Check if wp_localize_script() is properly set.');
        return;
    }

    // Exécution initiale après le chargement du DOM
    checkRCFseShippingMethod();
    resetRCFseSelectedServices();

    $(document.body).on('wc-blocks-checkout-update wc-blocks-order-review-update', function () {
        console.log("🔄 WooCommerce Blocks mise à jour détectée.");
        checkRCFseShippingMethod();
    });

    // Écouteurs d'événements
    $(document).on('change', 'input[type="radio"][name="radio-control-0"]', function () {
        console.log("Modification du mode de livraison...");
        checkRCFseShippingMethod();
        resetRCFseSelectedServices();
    });

    $(document).on('change', 'input[name^="rc_service_"], select[name^="rc_service_"], textarea[name^="rc_service_"]', function () {
        console.log("Choix d'un service...");

        $.ajaxPrefilter(function (options, originalOptions, jqXHR) {
            if (options.url.indexOf('wc-ajax=update_order_review') !== -1) {
                options.async = false;  // Force la mise à jour à être synchrone
            }
        });

        updateRCFseSelectedServices();
    });
});