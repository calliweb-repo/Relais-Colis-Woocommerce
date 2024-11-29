<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class RC_Settings_Page {

    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_rc_settings', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_rc_settings', __CLASS__ . '::update_settings' );
    }

    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['rc_settings'] = __( 'Relais Colis', 'relais-colis-woocommerce' );
        return $settings_tabs;
    }

    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }

    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    public static function get_settings() {
        $settings = array(
            'section_title' => array(
                'name' => __( 'Configurations Relais Colis', 'relais-colis-woocommerce' ),
                'type' => 'title',
                'desc' => '',
                'id' => 'rc_settings_section_title'
            ),
            'api_key' => array(
                'name' => __( 'Clé d\'activation', 'relais-colis-woocommerce' ),
                'type' => 'text',
                'desc' => __( 'Saisissez votre clé d\'activation Relais Colis.', 'relais-colis-woocommerce' ),
                'id' => 'rc_api_key'
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'rc_settings_section_end'
            )
        );

        return apply_filters( 'woocommerce_get_settings_' . 'rc_settings', $settings );
    }
}

RC_Settings_Page::init();