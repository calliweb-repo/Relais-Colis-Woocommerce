<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * WooCommerce Shipping AJAX Handler for RC API key validation
 *
 * Used to register all WC_Shipping_Method
 *
 * @since     1.0.0
 */
class WC_RC_Ajax_Validate_Api_Key {

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

        add_action( 'wp_ajax_validate_rc_api_key', array( $this, 'validate_api_key' ), 10 );
        add_action( 'wp_ajax_nopriv_validate_rc_api_key', array( $this, 'validate_api_key' ), 10 );
    }

    /**
     * @return void
     */
    public function validate_api_key(): void {

        $nonce_check = check_ajax_referer( 'rc-api-key-validation', 'security', false );
        if ( !$nonce_check ) {
            wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );
        }

        $api_key = isset( $_POST[ 'api_key' ] ) ? sanitize_text_field( $_POST[ 'api_key' ] ) : '';

        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => __( 'Clé API manquante', 'relais-colis-woocommerce' ) ] );
        }

        // TODO: Appel API

        wp_send_json_success( [ 'message' => __( 'Clé API valide', 'relais-colis-woocommerce' ) ] );
    }
}
