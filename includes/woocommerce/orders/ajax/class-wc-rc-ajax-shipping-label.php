<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use RelaisColisWoocommerce\DAO\WP_Orders_Rel_Shipping_Labels_DAO;
use Exception;

/**
 * Class WC_RC_Ajax_Shipping_Label
 *
 * This class handles WooCommerce AJAX requests for managing shipping labels in the Relais Colis system.
 * It is responsible for generating, placing, and retrieving shipping labels for packages.
 *
 * ## Key Responsibilities:
 * - **Placing Shipping Labels (Advertisement)**: Sends package details to Relais Colis API to generate shipping labels.
 * - **Retrieving PDF Shipping Labels**: Downloads and stores the label in PDF format for printing.
 * - **Handling Multiple Shipping Methods**: Supports Relais Colis for both **B2C** and **C2C** interactions.
 * - **Logging & Debugging**: Uses `WP_Log` to track requests and responses for troubleshooting.
 * - **WooCommerce Order Integration**: Updates `_rc_colis` meta with shipping label details.
 *
 * ## Data Structure:
 * Each order contains an `_rc_colis` meta key that stores package details:
 * ```php
 * '_rc_colis' => [
 *     'items' => [
 *         [ 'id' => 83, 'name' => 'Ab.', 'weight' => 120, 'quantity' => 2, 'remaining_quantity' => 0 ],
 *         [ 'id' => 73, 'name' => 'Aut.', 'weight' => 25000, 'quantity' => 1, 'remaining_quantity' => 1 ]
 *     ],
 *     'colis' => [
 *         [
 *             'items' => [ 83 => 2 ],
 *             'weight' => 240,
 *             'dimensions' => [ 'height' => 0, 'width' => 0, 'length' => 0 ],
 *             'shipping_label' => '4H013000008101',
 *             'shipping_label_pdf' => '<url>',
 *             'shipping_status' => 'status_rc_depose_en_relais'
 *         ]
 *     ]
 * ]
 * ```
 *
 * ## Methods Overview:
 * - `action_wp_ajax_rc_place_shipping_label()`: Places a shipping label using Relais Colis API.
 * - `convert_package_weight_in_grams()`: Converts WooCommerce weight units into grams for API compatibility.
 * - **API Requests:**
 *   - `b2c_relay_place_advertisement()`: Sends B2C relay shipment requests.
 *   - `c2c_relay_place_advertisement()`: Sends C2C relay shipment requests.
 *   - `b2c_home_place_advertisement()`: Handles home deliveries for B2C.
 * - **Status Management:**
 *   - Retrieves shipping status and updates `_rc_colis` accordingly.
 *   - Stores PDF shipping labels linked to shipping numbers.
 *
 * ## Workflow:
 * 1. **Send Package Data to Relais Colis API**:
 *    - Order details, shipping method, and package weight are sent to API.
 *    - API returns a **shipping label** (tracking number).
 *
 * 2. **Retrieve PDF Shipping Label**:
 *    - If a shipping label is received, the system downloads the corresponding PDF.
 *    - The label is stored in `_rc_colis` meta for future printing.
 *
 * 3. **Store Status & Update WooCommerce Order**:
 *    - The shipping label and status are saved in WooCommerce order metadata.
 *    - Status changes trigger necessary updates.
 *
 * ## WooCommerce Hooks Used:
 * - `wp_ajax_rc_place_shipping_label`: Handles the AJAX request for placing shipping labels.
 *
 * ## ⚠Considerations:
 * - **Security**: Uses nonce verification to prevent unauthorized requests.
 * - **Performance Optimization**: Ensures batch processing for large orders.
 * - **API Error Handling**: Catches and logs errors when communicating with Relais Colis API.
 *
 * ## Example API Response:
 * ```json
 * {
 *     "success": true,
 *     "colis": [
 *         {
 *             "items": { "83": 2 },
 *             "weight": 240,
 *             "dimensions": { "height": 0, "width": 0, "length": 0 },
 *             "shipping_label": "4H013000008101",
 *             "shipping_label_pdf": "<url>"
 *         }
 *     ]
 * }
 * ```
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_RC_Ajax_Shipping_Label {

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
        add_action( 'wp_ajax_rc_place_shipping_label', array( $this, 'action_wp_ajax_rc_place_shipping_label' ) );
        add_action( 'wp_ajax_rc_get_shipping_label_pdf', array( $this, 'action_wp_ajax_rc_get_shipping_label_pdf' ) );
    }

    /**
     * AJAX Handler: Print a shipping label for all packages (placeAdvertisement RC API)
     */
    public function action_wp_ajax_rc_get_shipping_label_pdf() {

        try {

            // Nonce security check
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            WP_Log::debug( __METHOD__.' - Print shipping label (advertisement)', [
                'POST' => $_POST,
            ], 'relais-colis-woocommerce' );

            // Validate the order ID
            if ( !isset( $_POST[ 'order_id' ] ) || !is_numeric( $_POST[ 'order_id' ] ) ) {
                wp_send_json_error( [
                    'message' => __( 'Invalid order ID', 'relais-colis-woocommerce' )
                ] );
            }

            $wc_order_id = intval( $_POST[ 'order_id' ] );
            $shipping_label = isset( $_POST['shipping_label'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_label'] ) ) : '';
            $colis_index = isset( $_POST['colis_index'] ) ? intval( wp_unslash( $_POST['colis_index'] ) ) : 0;

            // Load packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->load_order_packages( $wc_order_id );

            // Ensure the package exists and contains the product
            if ( !isset( $colis[ $colis_index ] ) || !isset( $colis[ $colis_index ][ 'shipping_label' ] ) ) {

                wp_send_json_error( [ 'message' => __( 'Package not found', 'relais-colis-woocommerce' ) ] );
            }

            $wc_order = wc_get_order( $wc_order_id );

            // Place shipping label
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->print_shipping_label( $wc_order, $colis_index, $shipping_label );

            WP_Log::debug( __METHOD__.' - After placing shipping label (advertisement)', [
                'order_id' => $wc_order_id,
                'existing_package' => $colis
            ], 'relais-colis-woocommerce' );

            // Get order state
            $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items,
                'pdf_url' => $colis[$colis_index]['shipping_label_pdf'],
            ] );

        } catch ( Exception $e ) {
            WP_Log::error( __METHOD__.' - An error occurred while placing shipping label', [
                'error_message' => $e->getMessage(),
                'order_id' => $wc_order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while downloading shipping label', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }

    /**
     * AJAX Handler: Place a shipping label for all packages (placeAdvertisement RC API)
     */
    public function action_wp_ajax_rc_place_shipping_label() {

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

            // Place shipping label
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->place_shipping_label( $wc_order );

            WP_Log::debug( __METHOD__.' - After placing shipping label (advertisement)', [
                'order_id' => $wc_order_id,
                'existing_package' => $colis
            ], 'relais-colis-woocommerce' );

            // Get order state
            $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items,
                'rc_order_state' => $order_state
            ] );

        } catch ( Exception $e ) {
            WP_Log::error( __METHOD__.' - An error occurred while placing shipping label', [
                'error_message' => $e->getMessage(),
                'order_id' => $wc_order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while placing shipping label', 'relais-colis-woocommerce' ). ' : '.$e->getMessage(),
                'error_details' => $e->getMessage()
            ] );
        }
    }
}
