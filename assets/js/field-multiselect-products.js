jQuery(document).ready(function($) {

    console.log('RC Field Multiselect Products init');

    "use strict";

    if (typeof rc_multiselect_params === 'undefined') {
        console.error('rc_multiselect_params is undefined. Check if wp_localize_script() is properly set.');
    }

    $('.wc-enhanced-select').select2({
        ajax: {
            url: rc_multiselect_params.ajax_url, // Use the localized AJAX URL
            dataType: 'json',
            delay: 250,
            language: 'fr',
            minimumInputLength: 2,
            data: function (params) {

                // Find the service ID from the data attribute of the select field
                let serviceId = $(this).data('service-id') || '';

                console.log('Sending AJAX request with:', {
                    action: 'get_wc_products',
                    search: params.term,
                    nonce: rc_multiselect_params.nonce,
                    service_id: serviceId
                });

                return {
                    action: 'get_wc_products',
                    search: params.term, // Dynamic search
                    nonce: rc_multiselect_params.nonce, // Pass the security nonce
                    service_id: serviceId
                };
            },
            processResults: function (data) {
                return {
                    results: Object.keys(data).map(function (key) {
                        return { id: key, text: data[key] };
                    })
                };
            }
        },
        minimumInputLength: 2 // Only search after 2 characters
    });
});