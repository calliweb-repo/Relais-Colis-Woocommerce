<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * Class WC_Orders_C2c_Bulk_Place_Labels_Manager
 * Manage Bulk actions in C2C mode: Place Labels
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Orders_C2c_Bulk_Place_Labels_Manager extends WC_Orders_C2c_Bulk_Actions_Manager {

    // Use Trait Singleton
    use Singleton;

    const RC_BULK_PLACE_LABELS_ACTION_C2C = 'rc_bulk_place_labels_action_c2c';

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        parent::init();
    }

    /**
     * Template Method
     * Get the slug used to attach bulk action
     * @return mixed
     */
    protected function get_specific_bulk_action_slug() {

        return self::RC_BULK_PLACE_LABELS_ACTION_C2C;
    }

    /**
     * Template Method
     * Get the title used to attach bulk action
     * @return mixed
     */
    protected function get_specific_bulk_action_title() {

        return __( 'Bulk place labels', 'relais-colis-woocommerce' );
    }

    /**
     * Template Method
     * Specific execution of the bulk action
     * @param array $order_ids the order IDs
     * @return mixed
     */
    protected function handle_specific_bulk_actions( $order_ids ) {

        // Get all orders with state ORDER_STATE_ITEMS_DISTRIBUTED
        //$order_state_items_distributed = WC_Order_Packages_Manager::instance()->get_orders_with_state( WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_DISTRIBUTED );
        foreach ( $order_ids as $order_id ) {

            //if ( in_array( $order_id, $order_state_items_distributed ) ) {

                // Call place shipping label
                try {
                    $wc_order = wc_get_order( $order_id );
                    WC_Order_Packages_Manager::instance()->place_shipping_label( $wc_order );

                    /**
                     * Notify 3rd party code on Relais Colis bulk action result
                     *
                     * @param int $order_id The order ID
                     * @param boolean $is_success true if success, otherwise false
                     * @param string $message A message associated with the hook
                     * @since 1.0.0
                     *
                     */
                    do_action( "after_bulk_actions_rc_shop_order", $order_id, true, __('All the shipping labels have been placed', 'relais-colis-woocommerce') );


                } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

                    /**
                     * Notify 3rd party code on Relais Colis bulk action result
                     *
                     * @param int $order_id The order ID
                     * @param boolean $is_success true if success, otherwise false
                     * @param string $message A message associated with the hook
                     * @since 1.0.0
                     *
                     */
                    do_action( "after_bulk_actions_rc_shop_order", $order_id, false, $wp_relais_colis_api_exception->getMessage() );
                }
            }

            // Pause 100ms
            usleep( 100000 );
        //}
    }
}
