<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Configuration_DAO;
use RelaisColisWoocommerce\DAO\WP_Information_DAO;
use RelaisColisWoocommerce\RCAPI\WP_RC_Get_Configuration_Response;
use RelaisColisWoocommerce\RCAPI\WP_RC_Get_Infos_Response;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;

/**
 * WooCommerce Shipping Method Manager.
 *
 * Used to register all WC_Shipping_Method
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Config_Manager {

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

        // Define the options and their default values
        $default_options = [
            WC_RC_Shipping_Constants::OPTION_RC_INTERACTION_MODE => WC_RC_Shipping_Constants::B2C_INTERACTION_MODE,
            WC_RC_Shipping_Constants::OPTION_LIVE_TEST_MODE_NAME => WC_RC_Shipping_Constants::LIVE_MODE,
            WC_RC_Shipping_Constants::OPTION_RC_API_ACCESS_VALID => 0,
        ];

        // Loop through each option and set the default if not already set
        foreach ( $default_options as $option_name => $default_value ) {

            if ( get_option( $option_name ) === false ) {

                update_option( $option_name, $default_value );
            }
        }
    }

    /**
     * Has active offer options
     * @param $offer one of OFFER_RELAIS_COLIS, OFFER_HOME, OFFER_HOME_PLUS
     * @return boolean true if enabled, otherwise false
     */
    public function has_delivery_offer_enabled( $offer ) {

        // Load options
        $rc_configuration_options = WP_Configuration_DAO::instance()->get_rc_configuration_options( true );

        switch ( $offer ) {
            // OFFER_RELAIS_COLIS enabled if rc_delivery is in options with true value
            case WC_RC_Shipping_Constants::OFFER_RELAIS_COLIS:
                foreach ( $rc_configuration_options as $rc_configuration_option ) {

                    if ( $rc_configuration_option['value'] === WC_RC_Shipping_Constants::OFFER_RELAIS_COLIS_ACTIVE_VALUE ) return true;
                }
                break;

                // OFFER_HOME enabled if home_delivery is in options with true value
            case WC_RC_Shipping_Constants::OFFER_HOME_PLUS:
                foreach ( $rc_configuration_options as $rc_configuration_option ) {

                    if ( $rc_configuration_option['value'] === WC_RC_Shipping_Constants::OFFER_HOME_PLUS_ACTIVE_VALUE ) return true;
                }
                break;

            // OFFER_HOME_PLUS enabled if rc_max is in options with true value
            case WC_RC_Shipping_Constants::OFFER_HOME:
                foreach ( $rc_configuration_options as $rc_configuration_option ) {

                    if ( $rc_configuration_option['value'] === WC_RC_Shipping_Constants::OFFER_HOME_ACTIVE_VALUE ) return true;
                }
                break;
        }
        return false;
    }

    /**
     * Get the request mode, live or test
     * @return string the request mode, LIVE_MODE or TEST_MODE
     */
    public function get_request_mode() {

        $mode = get_option( WC_RC_Shipping_Constants::OPTION_LIVE_TEST_MODE_NAME, WC_RC_Shipping_Constants::TEST_MODE );
        if ( ( $mode !== WC_RC_Shipping_Constants::TEST_MODE ) && ( $mode !== WC_RC_Shipping_Constants::LIVE_MODE ) ) {

            $mode = WC_RC_Shipping_Constants::LIVE_MODE;
        }
        return $mode;
    }

    /**
     * Get the RC interaction mode, C2C or B2C
     * @return void
     */
    public function get_rc_interaction_mode() {
       // var_dump(get_option( WC_RC_Shipping_Constants::OPTION_RC_INTERACTION_MODE, WC_RC_Shipping_Constants::B2C_INTERACTION_MODE ));
        $mode = get_option( WC_RC_Shipping_Constants::OPTION_RC_INTERACTION_MODE, WC_RC_Shipping_Constants::B2C_INTERACTION_MODE );
        if ( ( $mode !== WC_RC_Shipping_Constants::B2C_INTERACTION_MODE ) && ( $mode !== WC_RC_Shipping_Constants::C2C_INTERACTION_MODE ) ) {

            $mode = WC_RC_Shipping_Constants::B2C_INTERACTION_MODE;
        }
        return $mode;
    }

    /**
     * Check if C2C interaction mode
     * @return bool if C2C interaction mode, false otherwise (then b2c)
     */
    public function is_c2c_interaction_mode() {

        return ( $this->get_rc_interaction_mode() === WC_RC_Shipping_Constants::C2C_INTERACTION_MODE );
    }

    /**
     * Check if RC API access is valid
     * @return bool true if RC API access is valid, false otherwise
     */
    public function is_rc_api_valid_access() {

        $rc_api_valid_access = get_option( WC_RC_Shipping_Constants::OPTION_RC_API_ACCESS_VALID );
        return ( $rc_api_valid_access == 1 );
    }

    /**
     * Used to delete B2C configuration
     */
    public function delete_b2c_config_data() {

        // Delete related data
        WP_Configuration_DAO::instance()->delete_rc_get_configuration_data();

        // Delete C2C data too
        $this->delete_c2c_config_data();

        // Delete RC API access validity info
        delete_option( WC_RC_Shipping_Constants::OPTION_RC_API_ACCESS_VALID );
    }

    /**
     * Used to delete C2C configuration
     */
    public function delete_c2c_config_data() {

        // Delete C2C data
        WP_Information_DAO::instance()->delete_rc_get_information_data();

        // Delete RC API access validity info
        delete_option( WC_RC_Shipping_Constants::OPTION_RC_API_ACCESS_VALID );
    }

    /**
     * Used to update B2C configuration received from RC API server
     * Will deduce interaction mode and RC API access validity
     *
     * @param WP_RC_Get_Configuration_Response $wp_rc_configuration
     * @return void
     */
    public function update_b2c_config_data( WP_RC_Get_Configuration_Response $wp_rc_configuration ) {

        if ( is_null( $wp_rc_configuration ) ) return;

        // Insert responses in DB
        WP_Configuration_DAO::instance()->replace_rc_get_configuration_data( $wp_rc_configuration );

        // Check for enseigne id to determine interaction mode
        if ( !empty( $wp_rc_configuration->get_ens_id() ) && ( $wp_rc_configuration->get_ens_id() === WC_RC_Shipping_Constants::ENS_ID_C2C_INTERACTION_MODE_VALUE ) ) {

            // Set C2C interaction mode
            update_option( WC_RC_Shipping_Constants::OPTION_RC_INTERACTION_MODE, WC_RC_Shipping_Constants::C2C_INTERACTION_MODE );

            // Delete C2C data too
            $this->delete_c2c_config_data();

        } else {

            // Set B2C interaction mode
            update_option( WC_RC_Shipping_Constants::OPTION_RC_INTERACTION_MODE, WC_RC_Shipping_Constants::B2C_INTERACTION_MODE );

            // Then valid RC API access validity
            update_option( WC_RC_Shipping_Constants::OPTION_RC_API_ACCESS_VALID, 1 );
        }
    }

    /**
     * Used to update C2C configuration received from RC API server
     * @param WP_RC_Get_Infos_Response $wp_c2c_infos
     * @return void
     */
    public function update_c2c_config_data( WP_RC_Get_Infos_Response $wp_c2c_infos ) {

        if ( is_null( $wp_c2c_infos ) ) return;

        // Insert responses in DB
        WP_Information_DAO::instance()->replace_rc_get_information_data( $wp_c2c_infos );

        // Determine interaction mode (should be updated yet...)
        update_option( WC_RC_Shipping_Constants::OPTION_RC_INTERACTION_MODE, WC_RC_Shipping_Constants::C2C_INTERACTION_MODE );

        // Then valid RC API access validity
        // There is no concrete info about that in C2C response, other than receiving a complete and valid response
        update_option( WC_RC_Shipping_Constants::OPTION_RC_API_ACCESS_VALID, 1 );
    }

    /**
     * Used to update configuration data
     * @return void
     */
    public function update_configuration_data() {
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
                WP_Configuration_DAO::instance()->replace_rc_get_configuration_data( $wp_rc_configuration );
            }
        }
    }
}
