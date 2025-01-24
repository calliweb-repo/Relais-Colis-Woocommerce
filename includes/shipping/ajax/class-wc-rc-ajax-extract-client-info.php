<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * WooCommerce Shipping AJAX Handler for RC Extract client infos
 *
 * Used to register all WC_Shipping_Method
 *
 * @since     1.0.0
 */
class WC_RC_Ajax_Extract_Client_Info {

    // Use Trait Singleton
    use Singleton;

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        add_action( 'wp_ajax_rc_extract_client_info', array( $this, 'extract_client_info' ), 10 );
        add_action( 'wp_ajax_nopriv_rc_extract_client_info', array( $this, 'extract_client_info' ), 10 );
    }

    /**
     * @return void
     */
    public function extract_client_info() {

        if ( ! check_ajax_referer( 'rc-api-action', 'security', false ) ) {
            wp_send_json_error(['message' => __('Invalid Nonce', 'relais-colis-woocommerce')]);
        }

        // TODO: Appel API

        $api_response = [
            'nom'     => 'Dupont',
            'prenom'  => 'Jean',
            'solde'   => '123.45 €',
        ];

        wp_send_json_success($api_response);
    }
}
