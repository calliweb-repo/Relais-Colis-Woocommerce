<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * Class WC_Orders_B2c_Bulk_Auto_Distribute_Manager
 * Manage Bulk actions in B2C mode: Auto distribute
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Orders_B2c_Bulk_Auto_Distribute_Manager extends WC_Orders_B2c_Bulk_Actions_Manager {

    // Use Trait Singleton
    use Singleton;

    const RC_BULK_AUTO_DISTRIBUTE_ACTION = 'rc_bulk_auto_distribute_action';

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

        return self::RC_BULK_AUTO_DISTRIBUTE_ACTION;
    }

    /**
     * Template Method
     * Get the title used to attach bulk action
     * @return mixed
     */
    protected function get_specific_bulk_action_title() {

        return __( 'Bulk auto distribute', 'relais-colis-woocommerce' );
    }

    /**
     * Template Method
     * Specific execution of the bulk action
     * @param array $order_ids the order IDs
     * @return mixed
     */
    protected function handle_specific_bulk_actions( $order_ids ) {

        // Get all orders with state ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED
        //$order_state_items_to_be_distributed = WC_Order_Packages_Manager::instance()->get_orders_with_state( WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED );
        //WP_Log::debug( __METHOD__, [ '$order_ids' => $order_ids, '$order_state_items_to_be_distributed' => $order_state_items_to_be_distributed ], 'relais-colis-woocommerce' );
        foreach ( $order_ids as $order_id ) {

            //if ( in_array( $order_id, $order_state_items_to_be_distributed ) ) {

                // Call auto distribute
                WC_Order_Packages_Manager::instance()->auto_distribute_packages( $order_id );
            //}
        }
    }
}
