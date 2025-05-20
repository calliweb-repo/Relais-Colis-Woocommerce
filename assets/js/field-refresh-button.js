jQuery(document).ready(function ($) {
    console.log('RC Field Refresh Button initialized');

    'use strict';

    if (typeof rc_refresh_button_params === 'undefined') {
        console.error('rc_refresh_button_params is undefined. Check if wp_localize_script() is properly set.');
    }

    // Event listener for the refresh button click
    $(document).on('click', '.rc_refresh_button', function () {
        console.log('Sending AJAX request to refresh information');

        $.ajax({
            url: rc_refresh_button_params.ajax_url, // AJAX URL passed via wp_localize_script
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'refresh_infos', // Action identifier for WordPress
                nonce: rc_refresh_button_params.nonce // Security nonce
            },
            beforeSend: function () {
                console.log('AJAX request initiated...');
            },
            success: function (response) {
                console.log('✅ AJAX request successful:', response);

                if (response.success) {
                    console.log('🔄 Reloading the page...');
                    location.reload(); // Reload the page upon success
                } else {
                    console.warn('⚠️ Server responded with an error:', response.data);
                }
            },
            error: function (xhr, status, error) {
                console.error('❌ AJAX request failed:', status, error);
            }
        });
    });
});