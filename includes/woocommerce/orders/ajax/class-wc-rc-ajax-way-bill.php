<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\RCAPI\WP_RC_Transport_Generate;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use Exception;

/**
 * Class WC_RC_Ajax_Way_Bill
 *
 * This class handles WooCommerce AJAX requests for managing way bills in the Relais Colis system.
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_RC_Ajax_Way_Bill {

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

        // All actions are sent using AJAX
        add_action( 'wp_ajax_rc_generate_way_bill', array( $this, 'action_wp_ajax_rc_generate_way_bill' ) );
    }

    /**
     * AJAX Handler: Place a way bill for all packages (/transport/generate RC API)
     */
    public function action_wp_ajax_rc_generate_way_bill() {

        try {

            // Nonce security check
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            WP_Log::debug( __METHOD__.' - Place shipping label (advertisement)', [
                'POST' => $_POST,
            ], 'relais-colis-woocommerce' );

            // Validate the order ID
            if ( !isset( $_POST[ 'order_id' ] ) || !is_numeric( $_POST[ 'order_id' ] ) ) {
                wp_send_json_error( [
                    'message' => __( 'Invalid order ID', 'relais-colis-woocommerce' )
                ] );
            }

            $wc_order_id = intval( $_POST[ 'order_id' ] );
            $wc_order = wc_get_order( $wc_order_id );

            // Place a way bill for all packages (/transport/generate RC API)
            $rc_way_bill = WC_Order_Packages_Manager::instance()->generate_way_bill( $wc_order );

            // Get order state
            $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

            // Success response
            wp_send_json_success( [
                'rc_way_bill' => $rc_way_bill,
                'rc_order_state' => $order_state
            ] );

        } catch ( Exception $e ) {
            WP_Log::debug( __METHOD__.' - Error generate transport way bill', [
                'error_message' => $e->getMessage(),
                'order_id' => $wc_order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while generating way bill', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }
}
