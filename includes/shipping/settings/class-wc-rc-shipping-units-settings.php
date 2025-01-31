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

/**
 * WooCommerce Shipping Settings for General section
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Units_Settings {

    const SECTION_UNITS = 'units';

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

        // Register settings section
        add_filter( 'woocommerce_get_sections_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'filter_woocommerce_get_sections_rc' ) );

        // Register settings section
        add_action( 'woocommerce_settings_tabs_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_settings_rc_units' ) );

        // Update settings section
        add_action( 'woocommerce_update_options_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_update_options_rc_units' ) );
    }

    /**
     * Add section to the tab Relais Colis
     * @param $sections
     * @return mixed
     */
    public function filter_woocommerce_get_sections_rc( $sections ) {

        $sections[ self::SECTION_UNITS ] = __( 'Units', 'relais-colis-woocommerce' );
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

        return [

            // Section : Relais Colis Settings
            [
                'title' => __( 'Units of measurement', 'relais-colis-woocommerce' ), // Paramètres Relais Colis
                'type' => 'title',
                'id' => 'rc_settings_title',
            ],
            // Weight Units
            [
                'title' => __( 'Weight Units', 'relais-colis-woocommerce' ), // Unités de poids
                'desc' => __( 'Select the weight unit to use.', 'relais-colis-woocommerce' ), // Sélectionnez l'unité de poids à utiliser.
                'id' => 'rc_weight_unit',
                'type' => 'select',
                'options' => [
                    'g' => __( 'Grams (g)', 'relais-colis-woocommerce' ), // Grammes (g)
                    'dg' => __( 'Decigrams (dg)', 'relais-colis-woocommerce' ), // Décigrammes (dg)
                    'kg' => __( 'Kilograms (kg)', 'relais-colis-woocommerce' ), // Kilogrammes (kg)
                ],
                'default' => 'g',
                'class' => 'wc-enhanced-select',
                'desc_tip' => true,
            ],
            // Length Units
            [
                'title' => __( 'Length Units', 'relais-colis-woocommerce' ), // Unités de longueur
                'desc' => __( 'Select the length unit to use.', 'relais-colis-woocommerce' ), // Sélectionnez l'unité de longueur à utiliser.
                'id' => 'rc_length_unit',
                'type' => 'select',
                'options' => [
                    'mm' => __( 'Millimeters (mm)', 'relais-colis-woocommerce' ), // Millimètres (mm)
                    'cm' => __( 'Centimeters (cm)', 'relais-colis-woocommerce' ), // Centimètres (cm)
                    'dm' => __( 'Decimeters (dm)', 'relais-colis-woocommerce' ), // Décimètres (dm)
                    'm' => __( 'Meters (m)', 'relais-colis-woocommerce' ), // Mètres (m)
                    'in' => __( 'Inches (in)', 'relais-colis-woocommerce' ), // Pouces (in)
                ],
                'default' => 'cm',
                'class' => 'wc-enhanced-select',
                'desc_tip' => true,
            ],
            // Label Format
            [
                'title' => __( 'Label Format', 'relais-colis-woocommerce' ), // Format d’étiquette
                'desc' => __( 'Choose the label format to print.', 'relais-colis-woocommerce' ), // Choisissez le format d’étiquette à imprimer.
                'id' => 'rc_label_format',
                'type' => 'select',
                'options' => [
                    'A4' => __( 'A4 Format', 'relais-colis-woocommerce' ), // Format A4
                    'A5' => __( 'A5 Format', 'relais-colis-woocommerce' ), // Format A5
                    'carre' => __( 'Square Format', 'relais-colis-woocommerce' ), // Format Carré
                    '10x15' => __( '10x15 Format', 'relais-colis-woocommerce' ), // Format 10x15
                ],
                'default' => 'A4',
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_settings_section_end',
            ],
        ];
    }
}
