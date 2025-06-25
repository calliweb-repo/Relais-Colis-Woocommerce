<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Products_DAO;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Admin_Settings;

/**
 * WooCommerce Shipping AJAX Handler for refresh infos
 *
 * Used to register all WC_Shipping_Method
 *
 * @since     1.0.0
 */
class WC_RC_Ajax_Refresh_Infos {

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

        add_action( 'wp_ajax_refresh_infos', array( $this, 'action_wp_ajax_refresh_infos' ) );
        add_action( 'wp_ajax_nopriv_refresh_infos', array( $this, 'action_wp_ajax_refresh_infos' ) );
    }

    /**
     * @return void
     */
    public function action_wp_ajax_refresh_infos() {

        WP_Log::debug( __METHOD__, [ '$_POST'=>$_POST ], 'relais-colis-woocommerce' );

        $nonce_check = check_ajax_referer( 'rc_refresh_button_nonce', 'nonce', false );
        if ( !$nonce_check ) {

            WP_Log::error( __METHOD__.' - Nonce verification failed', [ 'received_nonce' => $_POST['nonce'] ?? 'MISSING' ], 'relais-colis-woocommerce' );
            wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );
        }

        // Refresh infos

        // Get interaction mode and RC API validity access
        $is_c2c_interaction_mode = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode();
        $is_rc_api_valid_access = WC_RC_Shipping_Config_Manager::instance()->is_rc_api_valid_access();
        WP_Log::debug( __METHOD__, ['C2C interaction mode?'=>($is_c2c_interaction_mode?'true':'false')], 'relais-colis-woocommerce' );
        WP_Log::debug( __METHOD__, ['Is RC API valid access?'=>($is_rc_api_valid_access?'true':'false')], 'relais-colis-woocommerce' );

        if ( !$is_rc_api_valid_access ) {

            WP_Log::error( __METHOD__.' - Invalid key', [], 'relais-colis-woocommerce' );

            // Reset B2C configuration
            WC_RC_Shipping_Config_Manager::instance()->delete_b2c_config_data();
        }

        // C2C mode
        if ( $is_c2c_interaction_mode ) {

            // Configuration depends on interaction mode
            $wp_c2c_infos = WP_Relais_Colis_API::instance()->c2c_get_infos( false );
            if ( !is_null( $wp_c2c_infos ) && $wp_c2c_infos->validate() ) {

                // Get returned account status
                $account_status = $wp_c2c_infos->get_account_status();

                // Account status must be valid
                if ( is_null( $account_status ) || ( $account_status !== 'active' ) ) {

                    // Reset B2C configuration
                    WC_RC_Shipping_Config_Manager::instance()->delete_c2c_config_data();
                }
                else {

                    // All is right!

                    // Update config
                    WC_RC_Shipping_Config_Manager::instance()->update_c2c_config_data( $wp_c2c_infos );
                    WP_Log::debug( __METHOD__.' - C2C Infos updated ', [ '$wp_c2c_infos'=>$wp_c2c_infos ], 'relais-colis-woocommerce' );
                }

            }
        }
        // Else B2C mode
        else {
            // Configuration depends on interaction mode
            $wp_rc_configuration = WP_Relais_Colis_API::instance()->get_b2c_configuration( false );
            if ( !is_null( $wp_rc_configuration ) && $wp_rc_configuration->validate() ) {

                // Get returned activation key
                $activation_key = $wp_rc_configuration->get_activation_key();

                // Activation key must be the same
                $current_activation_key = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );
                if ( $activation_key !== $current_activation_key ) {

                    // Reset B2C configuration
                    WC_RC_Shipping_Config_Manager::instance()->delete_b2c_config_data();
                } else {

                    // All is right!

                    // Update config
                    WC_RC_Shipping_Config_Manager::instance()->update_b2c_config_data( $wp_rc_configuration );
                    WP_Log::debug( __METHOD__.' - B2C Infos updated ', [ '$wp_rc_configuration'=>$wp_rc_configuration ], 'relais-colis-woocommerce' );
                }
            }
        }

        wp_send_json_success();
    }
}
