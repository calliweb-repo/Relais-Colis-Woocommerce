<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Products_DAO;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping AJAX Handler for Wooc get Products
 *
 * Used to register all WC_Shipping_Method
 *
 * @since     1.0.0
 */
class WC_RC_Ajax_Get_Wc_Products {

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

        add_action( 'wp_ajax_get_wc_products', array( $this, 'get_wc_products' ) );
        add_action( 'wp_ajax_nopriv_get_wc_products', array( $this, 'get_wc_products' ) );
    }

    /**
     * @return void
     */
    public function get_wc_products() {

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        WP_Log::debug( __METHOD__, [ 'GET'=>$_GET ], 'relais-colis-woocommerce' );

        $nonce_check = check_ajax_referer( 'rc_multiselect_products_nonce', 'nonce', false );
        if ( !$nonce_check ) {

            WP_Log::error( __METHOD__.' - Nonce verification failed', [ 'received_nonce' => $_GET['nonce'] ?? 'MISSING' ], 'relais-colis-woocommerce' );
            wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );
        }

        // Get search query
        $search = isset( $_GET[ 'search' ] ) ? sanitize_text_field( $_GET[ 'search' ] ) : '';
        $service_id = isset( $_GET['service_id'] ) ? intval( $_GET['service_id'] ) : null;
        WP_Log::debug( __METHOD__, [ '$search' => $search, '$service_id' => $service_id ], 'relais-colis-woocommerce' );

        // Query from DB
        $results = WP_Products_DAO::instance()->get_product_list( $search, 20, $service_id );
        WP_Log::debug( __METHOD__, [ '$results' => $results ], 'relais-colis-woocommerce' );

        wp_send_json( $results );
    }
}
