<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Generate;
use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_C2C_Get_Packages_Price;
use RelaisColisWoocommerce\RCAPI\WP_RC_C2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_Place_Advertisement_Request;
use RelaisColisWoocommerce\RCAPI\WP_RC_Place_Return_V3;
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
class WC_RC_Ajax_Shipping_Return {

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
        add_action( 'wp_ajax_rc_generate_return_label', array( $this, 'action_wp_ajax_rc_generate_return_label' ) );
    }

    /**
     * AJAX Handler: Add price estimation if C2C mode
     */
    public function action_wp_ajax_rc_generate_return_label() {

        try {

            // Nonce security check
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            WP_Log::debug( __METHOD__.' - Generate shipping return', [
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

            // Get interaction mode
            $is_c2c_interaction_mode = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode();

            // Only for B2C mode
            if ( $is_c2c_interaction_mode ) {

                wp_send_json_error( [
                    'message' => __( 'Invalid B2C mode', 'relais-colis-woocommerce' )
                ] );
            }
            // Country
            $country = get_option( 'woocommerce_default_country' ); // Eg: "FR:IDF"
            $country_array = explode( ":", $country );
            $store_country = $country_array[ 0 ]; // Country (Eg: FR)

            // Request RC API api/return/placeReturnV3
            // Dynamic params
            $dynamic_params = array(
                WP_RC_Place_Return_V3::REQUESTS => array(
                    array(
                        WP_RC_Place_Return_V3::ORDER_ID => $wc_order_id,
                        WP_RC_Place_Return_V3::CUSTOMER_ID => $wc_order->get_customer_id(),
                        WP_RC_Place_Return_V3::CUSTOMER_FULLNAME => $wc_order->get_shipping_first_name().' '.$wc_order->get_shipping_last_name(),
                        WP_RC_Place_Return_V3::XEETT => get_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_XEETT, 'I4040' ), // FIXME ??
                        WP_RC_Place_Return_V3::XEETT_NAME => 'La Poste', // FIXME ??
                        WP_RC_Place_Return_V3::CUSTOMER_PHONE => $wc_order->get_shipping_phone(),
                        WP_RC_Place_Return_V3::CUSTOMER_MOBILE => $wc_order->get_shipping_phone(),
                        WP_RC_Place_Return_V3::REFERENCE => $wc_order_id,
                        WP_RC_Place_Return_V3::CUSTOMER_COMPANY => $wc_order->get_shipping_company(),
                        WP_RC_Place_Return_V3::CUSTOMER_ADDRESS1 => $wc_order->get_shipping_address_1(),
                        WP_RC_Place_Return_V3::CUSTOMER_ADDRESS2 => $wc_order->get_shipping_address_2(),
                        WP_RC_Place_Return_V3::CUSTOMER_POSTCODE => $wc_order->get_shipping_postcode(),
                        WP_RC_Place_Return_V3::CUSTOMER_CITY => $wc_order->get_shipping_city(),
                        WP_RC_Place_Return_V3::CUSTOMER_COUNTRY => $store_country,
                        WP_RC_Place_Return_V3::PRESTATIONS => "1", // FIXME ???
                    ),
                ),
            );

            // RC API call
            $b2c_place_return = WP_Relais_Colis_API::instance()->b2c_place_return_v3( $dynamic_params, false );

            if ( is_null( $b2c_place_return ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                wp_send_json_error( [
                    'message' => __( 'No response', 'relais-colis-woocommerce' )
                ] );
            }

            // Display response
            if ( $b2c_place_return->validate() ) {

                // Affect to order :
                // bordereau_smart_url
                // return_number
                // number_cab
                // limit_date
                // image_url
                // token
                // created_at
                // Update order meta data
                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_BORDEREAU_SMART_URL, $b2c_place_return->get_bordereau_smart_url() );
                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_RETURN_NUMBER, $b2c_place_return->get_return_number() );
                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_NUMBER_CAB, $b2c_place_return->get_number_cab() );
                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_LIMIT_DATE, $b2c_place_return->get_limit_date() );
                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_IMAGE_URL, $b2c_place_return->get_image_url() );
                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_TOKEN, $b2c_place_return->get_token() );
                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_CREATED_AT, $b2c_place_return->get_created_at() );
                $wc_order->save();
                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );

            } else {

                WP_Log::debug( __METHOD__.' - Valid response', ['$b2c_place_return'=>$b2c_place_return], 'relais-colis-woocommerce' );
            }

            // Success response
            wp_send_json_success( [
             'return_bordereau_smart_url' => $b2c_place_return->get_bordereau_smart_url(),
             'return_number' => $b2c_place_return->get_return_number(),
             'return_number_cab' => $b2c_place_return->get_number_cab(),
             'return_limit_date' => $b2c_place_return->get_limit_date(),
             'return_image_url' => $b2c_place_return->get_image_url(),
             'return_token' => $b2c_place_return->get_token(),
             'return_created_at' => $b2c_place_return->get_created_at(),
            ] );

        } catch ( Exception $e ) {
            WP_Log::debug( __METHOD__.' - Error placing return', [
                'error_message' => $e->getMessage(),
                'order_id' => $wc_order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while placing return', 'relais-colis-woocommerce' ).' '.$e->getMessage()
            ] );
        }
    }
}
