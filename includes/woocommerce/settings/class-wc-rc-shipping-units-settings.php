<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Configuration_DAO;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Admin_Settings;
use WC_Settings_Products;

/**
 * WooCommerce Shipping Settings for General section
 *
 * Add a few units to native WooCommerce units, and ability to save it via RC settings
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Units_Settings {

    const SECTION_UNITS = 'units';

    // Use Trait Singleton
    use Singleton;

    // Keep native WooCommerce option units in memory
    private $woocommerce_weight_unit_options;
    private $woocommerce_dimension_unit_options;


    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // Register settings section
        add_filter( 'woocommerce_get_sections_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'filter_woocommerce_get_sections_rc' ) );

        // Register settings section
        add_action( 'woocommerce_settings_tabs_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_settings_rc_units' ) );

        // Update settings section
        add_action( 'woocommerce_update_options_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_update_options_rc_units' ) );

        // Add native units
        add_filter( 'woocommerce_product_settings', array( $this, 'filter_woocommerce_product_settings' ) );

        add_filter('woocommerce_product_settings', function($settings) {
            WP_Log::debug('BEFORE CUSTOM UNITS ADDED', ['units' => $settings], 'relais-colis-woocommerce');
            return $settings;
        }, 5); // Exécution très tôt

        add_filter('woocommerce_product_settings', function($settings) {
            WP_Log::debug('AFTER CUSTOM UNITS ADDED', ['units' => $settings], 'relais-colis-woocommerce');
            return $settings;
        }, 20); // Exécution plus tard

        add_action('admin_init', function() {
            $stored_units = get_option('woocommerce_weight_unit');
            WP_Log::debug('WooCommerce Stored Weight Units', ['units' => $stored_units], 'relais-colis-woocommerce');
        });
    }

    public function filter_woocommerce_products_general_settings( $settings ) {

        WP_Log::debug( __METHOD__, [ '$settings' => $settings ], 'relais-colis-woocommerce' );
        return $settings;
    }

    /**
     * Add woocommerce native units
     * @param $settings woocommerce settings
     * @return mixed
     */
    public function filter_woocommerce_product_settings( $settings ) {

        WP_Log::debug( __METHOD__, [ '$settings' => $settings ], 'relais-colis-woocommerce' );

        foreach ( $settings as &$setting ) {

            // Add woocommerce_weight_unit
            if ( $setting[ 'id' ] === 'woocommerce_weight_unit' ) {

                $setting[ 'options' ] = array_replace( $setting[ 'options' ], WC_RC_Shipping_Constants::get_weight_units() );
                $this->woocommerce_weight_unit_options = $setting[ 'options' ];
            }

            // Add woocommerce_dimension_unit
            if ( $setting[ 'id' ] === 'woocommerce_dimension_unit' ) {

                $setting[ 'options' ] = array_replace( $setting[ 'options' ], WC_RC_Shipping_Constants::get_dimension_units() );
                $this->woocommerce_dimension_unit_options = $setting[ 'options' ];
            }
        }
        WP_Log::debug( __METHOD__.' - After array_replace', [ '$settings' => $settings ], 'relais-colis-woocommerce' );

        return $settings;
    }

    /**
     * Add section to the tab Relais Colis
     * @param $sections
     * @return mixed
     */
    public function filter_woocommerce_get_sections_rc( $sections ) {

        $sections[ self::SECTION_UNITS ] = __( 'Options', 'relais-colis-woocommerce' );
        return $sections;
    }

    /**
     * Add properties to the current section
     * @param $sections
     */
    public function action_woocommerce_settings_rc_units() {

        global $current_section;
        if ( $current_section !== self::SECTION_UNITS ) return;

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Update properties
     */
    public function action_woocommerce_update_options_rc_units() {

        global $current_section;
        if ( $current_section !== self::SECTION_UNITS ) return;

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        woocommerce_update_options( $this->get_settings() );
    }

    /**
     * Get the properties
     * @return array
     */
    private function get_settings() {

        // Other tabs loaded only if RC API access is valid
        if ( !WC_RC_Shipping_Config_Manager::instance()->is_rc_api_valid_access() ) {

            return WC_RC_Shipping_Settings_Manager::instance()->get_invalid_licence_settings();
        }

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        // Tips to force instant call of filter woocommerce_product_settings to add custom CR units
        $wc_settings_products = new WC_Settings_Products();
        $wc_settings_products->get_settings_for_section( '' );

        return [

            // Section : Relais Colis Settings
            [
                'title' => __( 'Label Format', 'relais-colis-woocommerce' ),
                'type' => 'title',
                'id' => 'rc_settings_title',
            ],
            // // Weight Units
            // [
            //     'title' => __( 'Weight Units', 'relais-colis-woocommerce' ),
            //     'desc' => __( 'Select the weight unit to use.', 'relais-colis-woocommerce' ),
            //     'id' => WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT,
            //     'type' => 'select',
            //     'options' => ( !is_null( $this->woocommerce_weight_unit_options ) ? $this->woocommerce_weight_unit_options : WC_RC_Shipping_Constants::get_weight_units() ),
            //     'default' => 'kg',
            //     'desc_tip' => true,
            // ],
            // // Length Units
            // [
            //     'title' => __( 'Length Units', 'relais-colis-woocommerce' ),
            //     'desc' => __( 'Select the length unit to use.', 'relais-colis-woocommerce' ),
            //     'id' => WC_RC_Shipping_Constants::OPTION_RC_LENGTH_UNIT,
            //     'type' => 'select',
            //     'options' => ( !is_null( $this->woocommerce_dimension_unit_options ) ? $this->woocommerce_dimension_unit_options : WC_RC_Shipping_Constants::get_dimension_units() ),
            //     'default' => 'cm',
            //     'desc_tip' => true,
            // ],
            // Label Format
            [
                'title' => __( 'Format Choice', 'relais-colis-woocommerce' ),
                'desc' => __( 'Choose the label format to print.', 'relais-colis-woocommerce' ),
                'id' => WC_RC_Shipping_Constants::OPTION_RC_LABEL_FORMAT,
                'type' => 'select',
                'options' => WC_RC_Shipping_Constants::get_format_units(),
                'default' => 'A4',
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_settings_section_end',
            ],
        ];
    }
}
