<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Relacoof_Products_DAO;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping AJAX Handler for Wooc get Products
 *
 * Used to register all WC_Shipping_Method
 *
 * @since     1.0.0
 */
class WC_Relacoof_Ajax_Get_Wc_Products {

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

        add_action( 'wp_ajax_relacoof_custom_get_wc_products', array( $this, 'action_wp_ajax_relacoof_custom_get_wc_products' ) );
        add_action( 'wp_ajax_nopriv_relacoof_custom_get_wc_products', array( $this, 'action_wp_ajax_relacoof_custom_get_wc_products' ) );
    }

    /**
     * @return void
     */
    public function action_wp_ajax_relacoof_custom_get_wc_products() {

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $sanitized_get = array_map('sanitize_text_field', $_GET);
        WP_Log::debug( __METHOD__, [ 'GET'=> $sanitized_get ], 'relais-colis-officiel');

        $nonce_check = check_ajax_referer( 'relacoof_multiselect_products_nonce', 'nonce', false );
        if ( !$nonce_check ) {

            WP_Log::error( __METHOD__.' - Nonce verification failed', [ 'received_nonce' => $sanitized_get['nonce'] ?? 'MISSING' ], 'relais-colis-officiel');
            wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );
        }

        // Get search query
        $search = isset( $sanitized_get[ 'search' ] ) ? sanitize_text_field( $sanitized_get[ 'search' ] ) : '';
        $service_id = isset( $sanitized_get['service_id'] ) ? intval( $sanitized_get['service_id'] ) : null;
        WP_Log::debug( __METHOD__, [ '$search' => $search, '$service_id' => $service_id ], 'relais-colis-officiel');

        // Query from DB
        $results = WP_Relacoof_Products_DAO::instance()->get_product_list( $search, 20, $service_id );
        WP_Log::debug( __METHOD__, [ '$results' => $results ], 'relais-colis-officiel');

        wp_send_json( $results );
    }
}
