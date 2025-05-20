<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Orders_Rel_Shipping_Labels_DAO;
use RelaisColisWoocommerce\RCAPI\WP_RC_Get_Packages_Status;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Order;

/**
 * Class WC_Orders_RC_Status_Manager
 *
 * This class is responsible for managing the Relais Colis (RC) shipping status of WooCommerce orders.
 * It periodically updates the shipping status by interacting with the Relais Colis API and ensures that
 * WooCommerce orders are correctly linked with their respective shipping statuses.
 *
 * ## Key Responsibilities:
 * - **Automated Status Updates**: Periodically fetches and updates shipping statuses from the Relais Colis API.
 * - **Database Synchronization**: Maintains order-shipping label relationships in the custom `rc_orders_rel_shipping_labels` table.
 * - **WooCommerce Hook Integration**: Uses the `wp_loaded` hook to trigger background updates.
 * - **Error Handling & Logging**: Ensures robust error logging when API failures occur.
 * - **Scalability**: Designed to handle a large number of orders efficiently.
 *
 * ## Data Flow:
 * 1. **Orders are created in WooCommerce** with a Relais Colis shipping method.
 * 2. **Shipping labels are assigned** using the `init_order_rc_status()` method.
 * 3. **The status of these labels is periodically checked** using `action_wp_loaded()`.
 * 4. **If updates are required**, the class queries the Relais Colis API and updates the status accordingly.
 *
 * ## 🛠️ Methods Overview:
 * - `init()`: Registers WooCommerce hooks and initializes periodic status updates.
 * - `action_wp_loaded()`: Checks for pending updates and fetches shipping statuses from the Relais Colis API.
 * - `init_order_rc_status()`: Links an order with a shipping label in the database.
 *
 * ## Workflow:
 * 1. **Checking for Pending Updates**:
 *    - Queries the `rc_orders_rel_shipping_labels` table to identify orders needing updates.
 *    - If there are no pending updates, the process stops.
 *
 * 2. **Fetching Latest Shipping Statuses**:
 *    - Calls the Relais Colis API to retrieve updated statuses.
 *    - Logs API responses and errors.
 *
 * 3. **Updating Orders in WooCommerce**:
 *    - Iterates over received statuses.
 *    - Updates the shipping status in the database.
 *
 * ## WooCommerce Hooks Used:
 * - `wp_loaded`: Triggers the status update process when WordPress initializes.
 *
 * ## ⚠Considerations:
 * - **API Rate Limits**: Ensures that API calls are optimized to avoid excessive requests.
 * - **Database Efficiency**: Uses indexes on `shipping_label` and `order_id` for fast lookups.
 * - **Security**: Uses proper exception handling and logging to prevent silent failures.
 *
 * ## Example of Data Handled:
 * ```php
 * [
 *     'order_id' => 12345,
 *     'shipping_label' => '4H013000008101',
 *     'shipping_status' => 'status_rc_depose_en_relais',
 *     'last_updated' => '2025-03-12 10:15:00'
 * ]
 * ```
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
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
        // // Get pending shipping status
        // $orders_pending_update = WP_Orders_Rel_Shipping_Labels_DAO::instance()->get_orders_pending_update();
        // WP_Log::debug( __METHOD__, [ '$orders_pending_update' => $orders_pending_update ], 'relais-colis-woocommerce' );

        // // If not empty, need to update a few shipping status
        // if ( empty( $orders_pending_update ) ) return;
        
        // // Call API
        // try {
        //     // Get shipping labels
        //     $parcel_numbers = array();
        //     foreach ( $orders_pending_update as $order_pending_update ) {

        //         $parcel_numbers[] = $order_pending_update['shipping_label'];
        //     }
        //     $params = array(
        //         WP_RC_Get_Packages_Status::PARCEL_NUMBERS => $parcel_numbers,
        //     );

        //     $packages_status = WP_Relais_Colis_API::instance()->get_packages_status( $params, false );

        //     if ( is_null( $packages_status ) ) {

        //         WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
        //         return;
        //     }

        //     // Get RC statuses shipping_label=>shipping_status
        //     $rc_statuses = $packages_status->get_simplified_rc_statuses();
        //     WP_Log::debug( __METHOD__, [ 'rc_statuses' => $rc_statuses ], 'relais-colis-woocommerce' );

        //     // Update RC status for these orders, requesting RC API /api/package/getDataEvts endpoint
        //     foreach ( $rc_statuses as $rc_shipping_label => $rc_status ) {

        //         // Update the status iin DB
        //         WP_Orders_Rel_Shipping_Labels_DAO::instance()->update_shipping_status( $rc_shipping_label, $rc_status );
        //     }

        // } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

        //     WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        // }
    }


    /**
     * Initialize the order RC status
     * @param $wc_order
     * @return void
     */
    public function init_order_rc_status( WC_Order $wc_order, $entry ) {

        WP_Log::debug( __METHOD__.' - Init order RC status.', [ 'wc_order' => $wc_order ], 'relais-colis-woocommerce' );

        if(is_array($entry)){
            foreach ($entry as $key => $item) {
                WP_Orders_Rel_Shipping_Labels_DAO::instance()->insert_shipping_label( $wc_order->get_id(), $item );
            }
        }else{
            WP_Orders_Rel_Shipping_Labels_DAO::instance()->insert_shipping_label( $wc_order->get_id(), $entry );
        }
    }
}
