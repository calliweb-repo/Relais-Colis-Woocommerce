<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * Class WC_Orders_C2c_Csv_Export_Manager
 * Manage CSV export of C2C selected orders
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Orders_C2c_Csv_Export_Manager {

    // Use Trait Singleton
    use Singleton;

    const RC_EXPORT_CSV_ACTION = 'rc_export_csv';

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // Only available in C2C mode
        if ( !WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode() ) return;

        // Add a custom bulk action for exporting orders to CSV
        add_filter( 'bulk_actions-edit-shop_order', array( $this, 'filter_bulk_actions_edit_shop_order' ), 10, 1 );

        // Handle the bulk export action
        add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'filter_handle_bulk_actions_edit_shop_order' ), 10, 3 );
    }

    /**
     * Add a custom bulk action for exporting orders to CSV
     *
     * @param array $bulk_actions The existing bulk actions
     * @return array Updated bulk actions with our custom action
     */
    public function filter_bulk_actions_edit_shop_order( $bulk_actions ) {

        $bulk_actions[self::RC_EXPORT_CSV_ACTION] = __( 'Export in CSV (C2C)', 'relais-colis-woocommerce' );
        return $bulk_actions;
    }

    /**
     * Handle the bulk export action
     *
     * @param string $redirect_url The URL to redirect to after processing
     * @param string $action The action being processed
     * @param array $order_ids The selected order IDs
     * @return string Updated redirect URL
     */
    public function filter_handle_bulk_actions_edit_shop_order( $redirect_url, $action, $order_ids ) {

        if ( $action !== self::RC_EXPORT_CSV_ACTION ) return $redirect_url;

        // Check user permissions
        if ( !current_user_can( 'manage_woocommerce' ) ) {

            wp_die( __( 'You do not have sufficient permissions to export orders.', 'relais-colis-woocommerce' ) );
        }

        // Generate CSV
        $this->generate_and_download_csv( $order_ids );
        WP_Log::notice( __METHOD__, ['$redirect_url'=>$redirect_url, '$action'=>$action, '$order_ids'=>$order_ids ], 'relais-colis-woocommerce' );

        // Prevent redirection
        exit;
        //return $redirect_url;
    }

    /**
     * Generate and force download of the CSV file
     *
     * @param array $order_ids The selected order IDs
     */
    public function generate_and_download_csv( $order_ids ) {

        if ( empty( $order_ids ) ) return;

        // Define the CSV headers
        $csv_headers = array(
            __('Quantity', 'relais-colis-woocommerce'),
            __('Weight', 'relais-colis-woocommerce'),
            __('Conforming dimensions', 'relais-colis-woocommerce'),
            __('Conforming goods', 'relais-colis-woocommerce'),
            __('Recipient title', 'relais-colis-woocommerce'),
            __('Recipient last name', 'relais-colis-woocommerce'),
            __('Recipient first name', 'relais-colis-woocommerce'),
            __('Destination address line 1', 'relais-colis-woocommerce'),
            __('Destination address line 2', 'relais-colis-woocommerce'),
            __('Destination postal code', 'relais-colis-woocommerce'),
            __('Destination city', 'relais-colis-woocommerce'),
            __('Destination country', 'relais-colis-woocommerce'),
            __('Recipient email', 'relais-colis-woocommerce'),
            __('Recipient phone', 'relais-colis-woocommerce'),
            __('Destination ID', 'relais-colis-woocommerce'),
            __('Destination name', 'relais-colis-woocommerce')
        );

        // Open output buffer to generate CSV
        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="export_commandes_c2c.csv"' );
        $output = fopen( 'php://output', 'w' );

        // Write CSV headers
        fputcsv( $output, $csv_headers, ';' );

        // Loop through selected orders
        foreach ( $order_ids as $order_id ) {

            // Get order
            $order = wc_get_order( $order_id );
            if ( !$order ) continue;

            // Extract relevant data
            $items = $order->get_items();
            $total_weight = 0;
            $total_quantity = 0;

            // Parse all order items
            foreach ( $items as $item ) {

                $product = $item->get_product();
                if ( $product ) {

                    $total_weight += (float) $product->get_weight() * $item->get_quantity();
                    $total_quantity += $item->get_quantity();
                }
            }

            $shipping_address = $order->get_address( 'shipping' );
//            $destination_id = $order->get_meta( 'rc_relay_id' );
//            $destination_name = $order->get_meta( 'rc_relay_name' );
            $destination_id = '';
            $destination_name = '';

            // Weight and dimensions unit
            $option_rc_weight_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT );

            // Build CSV row
            $csv_row = array(
                $total_quantity,
                $total_weight.$option_rc_weight_unit,
                '',
                '',
                $shipping_address['title'] ?? '',
                $shipping_address['last_name'] ?? '',
                $shipping_address['first_name'] ?? '',
                $shipping_address['address_1'] ?? '',
                $shipping_address['address_2'] ?? '',
                $shipping_address['postcode'] ?? '',
                $shipping_address['city'] ?? '',
                $shipping_address['country'] ?? '',
                $shipping_address['email'] ?? '',
                $shipping_address['phone'] ?? '',
                $destination_id,
                $destination_name
            );

            // Write row to CSV
            fputcsv( $output, $csv_row, ';' );
        }

        fclose( $output );
        exit;
    }
}
