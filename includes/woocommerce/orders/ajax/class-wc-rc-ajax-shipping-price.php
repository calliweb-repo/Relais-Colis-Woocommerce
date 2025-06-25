<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Generate;
use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_C2C_Get_Packages_Price;
use RelaisColisWoocommerce\RCAPI\WP_RC_C2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_Place_Advertisement_Request;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use Exception;

/**
 * Class WC_RC_Ajax_Shipping_Price
 *
 * This class handles WooCommerce AJAX requests for managing shipping prices in the Relais Colis system.
 * It is responsible for generating, placing, and retrieving shipping prices for packages.
 *
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_RC_Ajax_Shipping_Price {

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
        add_action( 'wp_ajax_rc_get_packages_price', array( $this, 'action_wp_ajax_rc_get_packages_price' ) );
    }

    /**
     * AJAX Handler: Add price estimation if C2C mode
     */
    public function action_wp_ajax_rc_get_packages_price() {

        try {

            // Nonce security check
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            WP_Log::debug( __METHOD__.' - Get shipping price', [
                'POST' => $_POST,
            ], 'relais-colis-woocommerce' );

            // Validate the order ID
            if ( !isset( $_POST[ 'order_id' ] ) || !is_numeric( $_POST[ 'order_id' ] ) ) {
                wp_send_json_error( [
                    'message' => __( 'Invalid order ID', 'relais-colis-woocommerce' )
                ] );
            }

            $wc_order_id = intval( $_POST[ 'order_id' ] );

            // Load packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->load_order_packages( $wc_order_id );

            // Get interaction mode
            $is_c2c_interaction_mode = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode();

            // Only for C2C mode
            if ( !$is_c2c_interaction_mode ) {

                wp_send_json_error( [
                    'message' => __( 'Invalid mode: only C2C is authorized', 'relais-colis-woocommerce' )
                ] );
            }

            // Request RC API place_advertisement
            foreach ( $colis as &$c_colis ) {

                //
                // Call API - Get package price
                //
                $dynamic_params = array(
                    WP_RC_C2C_Get_Packages_Price::PACKAGES_WEIGHT => array( $c_colis[ 'weight' ] ),
                );
                WP_Log::debug( __METHOD__.' - Dynamic params ready for c2c_get_packages_price', [ '$dynamic_params' => $dynamic_params ], 'relais-colis-woocommerce' );

                $c2c_get_packages_price = WP_Relais_Colis_API::instance()->c2c_get_packages_price( $dynamic_params, false );

                if ( is_null( $c2c_get_packages_price ) ) {

                    WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                    continue;
                }

                // Price retrieved with success
                if ( $c2c_get_packages_price->validate() ) {

                    $shipping_price = $c2c_get_packages_price->entry;

                    // Set shipping label in colis
                    $c_colis[ 'c2c_shipping_price' ] = $shipping_price;
                }
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
            WP_Log::error( __METHOD__.' - Error getting price', [
                'error_message' => $e->getMessage(),
                'order_id' => $wc_order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while getting price', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }
}
