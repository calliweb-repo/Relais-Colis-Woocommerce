<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Generate;
use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_C2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_Place_Advertisement_Request;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
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

    // Packages ar stored as meta-data within Orders
    //    [items] => [
    //            [0] => [
    //                    [id] => 83
    //                    [name] => Ab.
    //                    [weight] => 120
    //                    [quantity] => 2
    //                    [remaining_quantity] => 0
    //                ]
    //
    //            [1] => [
    //                    [id] => 73
    //                    [name] => Aut.
    //                    [weight] => 25000
    //                    [quantity] => 1
    //                    [remaining_quantity] => 1
    //                ]
    //         ]
    //
    //    [colis] => [
    //            [0] => [
    //                    [items] => [
    //                            [83] => 2
    //                        ]
    //
    //                    [weight] => 240
    //                    [dimensions] => [
    //                            [height] => 0
    //                            [width] => 0
    //                            [length] => 0
    //                        ]
    //                    [shipping_label] => 4H013000008101
    //                    [shipping_label_pdf] => <url du PDF>
    //                ]
    //        ]

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

            // Load packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->load_order_packages( $wc_order_id );

            // Get interaction mode
            $is_c2c_interaction_mode = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode();

            // Prepare request (generic part)
            // Country
            $country = get_option( 'woocommerce_default_country' ); // Eg: "FR:IDF"
            $country_array = explode( ":", $country );
            $store_country = $country_array[ 0 ]; // Country (Eg: FR)

            // Dynamic common params
            $dynamic_params = array(
                WP_RC_Place_Advertisement_Request::AGENCY_CODE => get_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_AGENCY_CODE, 'C3' ), // Code de l'agence FIXME
                WP_RC_Place_Advertisement_Request::CUSTOMER_ID => $wc_order->get_customer_id(),
                WP_RC_Place_Advertisement_Request::CUSTOMER_FULLNAME => $wc_order->get_shipping_first_name().' '.$wc_order->get_shipping_last_name(),
                WP_RC_Place_Advertisement_Request::CUSTOMER_EMAIL => $wc_order->get_billing_email(),
                WP_RC_Place_Advertisement_Request::CUSTOMER_PHONE => $wc_order->get_shipping_phone(),
                WP_RC_Place_Advertisement_Request::CUSTOMER_MOBILE => $wc_order->get_shipping_phone(),
                WP_RC_Place_Advertisement_Request::ORDER_REFERENCE => $wc_order->get_order_number(),
                WP_RC_Place_Advertisement_Request::SHIPPING_ADDRESS_1 => $wc_order->get_shipping_address_1(),
                WP_RC_Place_Advertisement_Request::SHIPPING_ADDRESS_2 => $wc_order->get_shipping_address_2(),
                WP_RC_Place_Advertisement_Request::SHIPPING_POSTCODE => $wc_order->get_shipping_postcode(),
                WP_RC_Place_Advertisement_Request::SHIPPING_CITY => $wc_order->get_shipping_city(),
                WP_RC_Place_Advertisement_Request::SHIPPING_COUNTRY_CODE => $store_country,
                WP_RC_Place_Advertisement_Request::LANGUAGE => 'FR',
            );

            // Check if the shipping method is "Relais Colis"
            $rc_shipping_method = WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $wc_order );

            switch ( $rc_shipping_method ) {
                case WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID:

                    // Request RC API place_advertisement
                    foreach ( $colis as &$c_colis ) {

                        // Depend on interaction mode (B2C or C2C)

                        // Get package weight
                        // Weight and dimensions unit
                        $option_rc_weight_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT );
                        $weight = WP_Helper::convert_to_grams( $c_colis[ 'weight' ], $option_rc_weight_unit );

                        $dynamic_params[ WP_RC_Place_Advertisement_Request::SHIPPMENT_WEIGHT ] = $weight;
                        $dynamic_params[ WP_RC_Place_Advertisement_Request::WEIGHT ] = $weight;

                        // C2C - Relay
                        if ( $is_c2c_interaction_mode ) {

                            // Call API
                            try {

                                // Dynamic params
                                $dynamic_params[ WP_RC_C2C_Relay_Place_Advertisement::XEETT ] = get_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_XEETT, 'I4040' ); // ID spécifique Xeett FIXME
                                $dynamic_params[ WP_RC_C2C_Relay_Place_Advertisement::ADDRESS1_EXPEDITEUR ] = get_option( 'woocommerce_store_address' );
                                $dynamic_params[ WP_RC_C2C_Relay_Place_Advertisement::ADDRESS2_EXPEDITEUR ] = get_option( 'woocommerce_store_address_2' );
                                $dynamic_params[ WP_RC_C2C_Relay_Place_Advertisement::EMAIL_EXPEDITEUR ] = get_option( 'woocommerce_email_from_address' );
                                $dynamic_params[ WP_RC_C2C_Relay_Place_Advertisement::CITY_EXPEDITEUR ] = get_option( 'woocommerce_store_city' );
                                $dynamic_params[ WP_RC_C2C_Relay_Place_Advertisement::NAME_EXPEDITEUR ] = get_option( 'blogname' );
                                $dynamic_params[ WP_RC_C2C_Relay_Place_Advertisement::PHONE_EXPEDITEUR ] = get_option( 'woocommerce_store_phone' );
                                $dynamic_params[ WP_RC_C2C_Relay_Place_Advertisement::POSTCODE_EXPEDITEUR ] = get_option( 'woocommerce_store_postcode' );

                                $c2c_relay_place_advertisement = WP_Relais_Colis_API::instance()->c2c_relay_place_advertisement( $dynamic_params, false );

                                if ( is_null( $c2c_relay_place_advertisement ) ) {

                                    WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                                    continue;
                                }

                                // Display response
                                if ( $c2c_relay_place_advertisement->validate() ) {

                                    $entry = $c2c_relay_place_advertisement->entry;

                                    WP_Log::debug( __METHOD__.' - Valid response', [
                                        'Entry' => $entry,
                                    ], 'relais-colis-woocommerce' );

                                    // Set shipping label in colis
                                    $c_colis[ 'shipping_label' ] = $entry;

                                    // Init RC status
                                    WC_Orders_RC_Status_Manager::instance()->init_order_rc_status( $wc_order, $entry );

                                    // If shipping label is received, then download PDF format
                                    // Dynamic params
                                    $option_rc_label_format = get_option( WC_RC_Shipping_Constants::OPTION_RC_LABEL_FORMAT );
                                    $dynamic_params = array(
                                        WP_RC_B2C_Generate::FORMAT => $option_rc_label_format,
                                        WP_RC_B2C_Generate::PDF => $entry,
                                    );
                                    $c2c_generate = WP_Relais_Colis_API::instance()->c2c_generate( $dynamic_params, false );

                                    if ( is_null( $c2c_generate ) ) {

                                        WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                                        continue;
                                    }

                                    // PDF downloaded successfully
                                    $c_colis[ 'shipping_label_pdf' ] = $c2c_generate->get_pdf_delivery_label();

                                } else {

                                    WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
                                    continue;
                                }
                            } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

                                WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
                                continue;
                            }
                        } // B2C - Relay
                        else {

                            // Call API
                            try {
                                // Dynamic params
                                $dynamic_params[ WP_RC_B2C_Relay_Place_Advertisement::PSEUDO_RVC ] = '01309';
                                $dynamic_params[ WP_RC_B2C_Relay_Place_Advertisement::XEETT ] = get_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_XEETT, 'I4040' ); // ID spécifique Xeett FIXME

                                $b2c_relay_place_advertisement = WP_Relais_Colis_API::instance()->b2c_relay_place_advertisement( $dynamic_params, false );

                                if ( is_null( $b2c_relay_place_advertisement ) ) {

                                    WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                                    continue;
                                }

                                // Display response
                                if ( $b2c_relay_place_advertisement->validate() ) {

                                    $entry = $b2c_relay_place_advertisement->entry;

                                    WP_Log::debug( __METHOD__.' - Valid response', [
                                        'Entry' => $entry,
                                    ], 'relais-colis-woocommerce' );

                                    // Set shipping label in colis
                                    $c_colis[ 'shipping_label' ] = $entry;

                                    // Init RC status
                                    WC_Orders_RC_Status_Manager::instance()->init_order_rc_status( $wc_order, $entry );

                                    // If shipping label is received, then download PDF format
                                    // Dynamic params
                                    $option_rc_label_format = get_option( WC_RC_Shipping_Constants::OPTION_RC_LABEL_FORMAT );
                                    $dynamic_params = array(
                                        WP_RC_B2C_Generate::FORMAT => $option_rc_label_format,
                                        WP_RC_B2C_Generate::PDF => $entry,
                                    );
                                    $c2c_generate = WP_Relais_Colis_API::instance()->b2c_generate( $dynamic_params, false );

                                    if ( is_null( $c2c_generate ) ) {

                                        WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                                        continue;
                                    }

                                    // PDF downloaded successfully
                                    $c_colis[ 'shipping_label_pdf' ] = $c2c_generate->get_pdf_delivery_label();

                                } else {

                                    WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
                                    continue;
                                }
                            } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

                                WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
                                continue;
                            }
                        }
                    }
                    break;

                case WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID:
                case WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID:

                    // Request RC API place_advertisement
                    foreach ( $colis as &$c_colis ) {

                        // Depend on interaction mode (B2C or C2C)

                        // C2C - Home
                        if ( $is_c2c_interaction_mode ) {

                            // Not supported
                        } // B2C - Home
                        else {

                            // Call API
                            try {

                                // Get package weight
                                // Weight and dimensions unit
                                $option_rc_weight_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT );
                                $weight = WP_Helper::convert_to_grams( $c_colis[ 'weight' ], $option_rc_weight_unit );

                                $dynamic_params[ WP_RC_Place_Advertisement_Request::SHIPPMENT_WEIGHT ] = $weight;
                                $dynamic_params[ WP_RC_Place_Advertisement_Request::WEIGHT ] = $weight;

                                $b2c_home_place_advertisement = WP_Relais_Colis_API::instance()->b2c_home_place_advertisement( $dynamic_params, false );

                                if ( is_null( $b2c_home_place_advertisement ) ) {

                                    WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                                    continue;
                                }

                                // Display response
                                if ( $b2c_home_place_advertisement->validate() ) {

                                    $entry = $b2c_home_place_advertisement->entry;

                                    WP_Log::debug( __METHOD__.' - Valid response', [
                                        'Entry' => $entry,
                                    ], 'relais-colis-woocommerce' );

                                    // Set shipping label in colis
                                    $c_colis[ 'shipping_label' ] = $entry;

                                    // Init RC status
                                    WC_Orders_RC_Status_Manager::instance()->init_order_rc_status( $wc_order, $entry );

                                    // If shipping label is received, then download PDF format
                                    // Dynamic params
                                    $option_rc_label_format = get_option( WC_RC_Shipping_Constants::OPTION_RC_LABEL_FORMAT );
                                    $dynamic_params = array(
                                        WP_RC_B2C_Generate::FORMAT => $option_rc_label_format,
                                        WP_RC_B2C_Generate::PDF => $entry,
                                    );
                                    $c2c_generate = WP_Relais_Colis_API::instance()->b2c_generate( $dynamic_params, false );

                                    if ( is_null( $c2c_generate ) ) {

                                        WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                                        continue;
                                    }

                                    // PDF downloaded successfully
                                    $c_colis[ 'shipping_label_pdf' ] = $c2c_generate->get_pdf_delivery_label();

                                } else {

                                    WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
                                    continue;
                                }
                            } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

                                WP_Log::error( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
                                continue;
                            }
                        }
                    }
                    break;
            }

            // Save packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->save_order_packages( $colis, $wc_order_id );

            WP_Log::debug( __METHOD__.' - After placing shipping label (advertisement)', [
                'order_id' => $wc_order_id,
                'existing_package' => $colis
            ], 'relais-colis-woocommerce' );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items
            ] );

        } catch ( Exception $e ) {
            WP_Log::error( __METHOD__.' - Error adding package', [
                'error_message' => $e->getMessage(),
                'order_id' => $wc_order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while adding a package', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }
}
