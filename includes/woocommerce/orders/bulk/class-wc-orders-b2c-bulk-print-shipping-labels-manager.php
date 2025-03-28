<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * Class WC_Orders_B2c_Bulk_Print_Shipping_Labels_Manager
 * Manage Bulk actions in B2C mode: Print shipping Labels
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Orders_B2c_Bulk_Print_Shipping_Labels_Manager extends WC_Orders_B2c_Bulk_Actions_Manager {

    // Use Trait Singleton
    use Singleton;

    const RC_BULK_PRINT_SHIPPING_LABELS = 'rc_bulk_print_shipping_labels';

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

        return self::RC_BULK_PRINT_SHIPPING_LABELS;
    }

    /**
     * Template Method
     * Get the title used to attach bulk action
     * @return mixed
     */
    protected function get_specific_bulk_action_title() {

        return __( 'Bulk print shipping labels', 'relais-colis-woocommerce' );
    }

    /**
     * Template Method
     * Specific execution of the bulk action
     * @param array $order_ids the order IDs
     * @return mixed
     */
    protected function handle_specific_bulk_actions( $order_ids ) {

        // Bulk print shipping labels
        $pdf_delivery_label = WC_Order_Packages_Manager::instance()->bulk_print_shipping_labels( $order_ids );

        if ( $pdf_delivery_label === false ) {

            WP_Log::debug( __METHOD__.' Errors occurred while shipping labels print', [], 'relais-colis-woocommerce' );
        }

    }
}
