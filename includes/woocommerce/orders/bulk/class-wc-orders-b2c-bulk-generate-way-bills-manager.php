<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * Class WC_Orders_B2c_Bulk_Generate_Way_Bills_Manager
 * Manage Bulk actions in B2C mode: Generate way bills
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Orders_B2c_Bulk_Generate_Way_Bills_Manager extends WC_Orders_B2c_Bulk_Actions_Manager {

    // Use Trait Singleton
    use Singleton;

    const RC_BULK_GENERATE_WAY_BILLS_MANAGER = 'rc_bulk_generate_way_bills_manager';

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

        return self::RC_BULK_GENERATE_WAY_BILLS_MANAGER;
    }

    /**
     * Template Method
     * Get the title used to attach bulk action
     * @return mixed
     */
    protected function get_specific_bulk_action_title() {

        return __( 'Bulk generate way bills', 'relais-colis-woocommerce' );
    }

    /**
     * Template Method
     * Specific execution of the bulk action
     * @param array $order_ids the order IDs
     * @return mixed
     */
    protected function handle_specific_bulk_actions( $order_ids ) {

        // Bulk print shipping labels
        $way_bills = WC_Order_Packages_Manager::instance()->bulk_generate_way_bills( $order_ids );

        if ( $way_bills === false ) {

            WP_Log::debug( __METHOD__.' Errors occurred while way bills generation', [], 'relais-colis-woocommerce' );
        }

    }
}
