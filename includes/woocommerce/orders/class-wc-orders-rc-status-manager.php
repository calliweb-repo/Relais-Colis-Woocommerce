<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Orders_Rel_Shipping_Labels_DAO;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Order;

/**
 * WooCommerce Order RC status Manager.
 *
 * @since     1.0.0
 */
class WC_Orders_RC_Status_Manager {

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

        // Used to update orders rc status periodically
        add_action( 'wp_loaded', array( $this, 'action_wp_loaded' ), 10 );
    }

    /**
     * Used to update orders rc status periodically
     * @return void
     */
    public function action_wp_loaded() {

        // Get pending shipping status
        $has_orders_pending_update = WP_Orders_Rel_Shipping_Labels_DAO::instance()->has_orders_pending_update();
        WP_Log::notice( __METHOD__, [ '$has_orders_pending_update' => $has_orders_pending_update?'true':'false' ], 'relais-colis-woocommerce' );

        // If not empty, need to update a few shippinh status
        if ( !$has_orders_pending_update ) return;
        
        // Call API
        try {

            $packages_status = WP_Relais_Colis_API::instance()->get_packages_status( false );

            if ( is_null( $packages_status ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Get RC statuses shipping_label=>shipping_status
            $rc_statuses = $packages_status->get_simplified_rc_statuses();
            WP_Log::notice( __METHOD__, [ 'rc_statuses' => $rc_statuses ], 'relais-colis-woocommerce' );

            // Update RC status for these orders, requesting RC API /api/package/getDataEvts endpoint
            foreach ( $rc_statuses as $rc_shipping_label => $rc_status ) {

                // Update the status iin DB
                WP_Orders_Rel_Shipping_Labels_DAO::instance()->update_shipping_status( $rc_shipping_label, $rc_status );
            }

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::error( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }


    /**
     * Initialize the order RC status
     * @param $wc_order
     * @return void
     */
    public function init_order_rc_status( WC_Order $wc_order, $shipping_label ) {

        WP_Log::debug( __METHOD__.' - Init order RC status.', [ 'wc_order' => $wc_order ], 'relais-colis-woocommerce' );

        WP_Orders_Rel_Shipping_Labels_DAO::instance()->insert_shipping_label( $wc_order->get_id(), $shipping_label );
    }
}
