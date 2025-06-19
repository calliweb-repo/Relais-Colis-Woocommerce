<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * WooCommerce Relais Colis Scripts Manager for checkout
 *
 * @since     1.0.0
 */
class WC_RC_Checkout_Scripts_Manager {

    // Use Trait Singleton
    use Singleton;

    const PREFIX_RC = 'wc_rc_shipping_method';

    private static $loaded_scripts = array();

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {
    }

    /**
     * Load scripts and styles for FSE checkout mode,
     * Ensure that files are loaded once
     * @return void
     */
    public function load_fse_checkout_scripts() {

        // Enqueued only in concerned checkout page
        if ( !is_checkout()  ) return;

        // Check that loaded once
        if ( in_array( self::PREFIX_RC, self::$loaded_scripts ) ) return;
        self::$loaded_scripts[] = self::PREFIX_RC;

        // Relais colis plugin URL
        $plugin_url = Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url();

        // JS - JQuery
        //wp_enqueue_script( 'jquery' );

        // JS - Relais Colis
        wp_enqueue_script( self::PREFIX_RC.'_js', $plugin_url.'assets/js/rc-fse-choose-options.js', array( 'jquery' ), '1.0', true );

        // CSS - JQuery, UI, Dialog, Leaflet
        wp_enqueue_style( self::PREFIX_RC.'_font_awesome_css', $plugin_url.'assets/css/font-awesome.css', array(), '6.0.0' );

        // CSS - Relais Colis
        wp_enqueue_style( self::PREFIX_RC.'_home_css', $plugin_url.'assets/css/rc-home-choose-options.css', array(), '1.0', 'all' );
        wp_enqueue_style( self::PREFIX_RC.'_homeplus_css', $plugin_url.'assets/css/rc-homeplus-choose-options.css', array(), '1.0', 'all' );
        wp_enqueue_style( self::PREFIX_RC.'_icon_css', $plugin_url.'assets/css/rc-choose-options-logo.css', array(), '1.0', 'all' );

        // Localize script
        wp_localize_script( self::PREFIX_RC.'_js', 'rc_choose_options',
            array(
                'label_please_select_relay' => __( 'Please select a relay point', 'relais-colis-woocommerce' )
            )
        );

    }

    /**
     * Load scripts and styles for old checkout mode,
     * Ensure that files are loaded once
     * @return void
     */
    public function load_old_checkout_scripts() {

        // Enqueued only in concerned checkout page
        if ( !is_checkout() ) return;

        // Check that loaded once
        if ( in_array( self::PREFIX_RC, self::$loaded_scripts ) ) return;
        self::$loaded_scripts[] = self::PREFIX_RC;

        // Relais colis plugin URL
        $plugin_url = Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url();

        // JS - JQuery
        //wp_enqueue_script( 'jquery' );

        // JS - Relais Colis
        wp_enqueue_script( self::PREFIX_RC.'_js', $plugin_url.'assets/js/rc-old-choose-options.js', array( 'jquery' ), '1.0', true );

        // CSS - JQuery, UI, Dialog, Leaflet
        wp_enqueue_style( self::PREFIX_RC.'_font_awesome_css', $plugin_url.'assets/css/font-awesome.min.css', array(), '6.0.0' );

        // CSS - Relais Colis
        wp_enqueue_style( self::PREFIX_RC.'_home_css', $plugin_url.'assets/css/rc-home-choose-options.css', array(), '1.0', 'all' );
        wp_enqueue_style( self::PREFIX_RC.'_homeplus_css', $plugin_url.'assets/css/rc-homeplus-choose-options.css', array(), '1.0', 'all' );
        wp_enqueue_style( self::PREFIX_RC.'_icon_css', $plugin_url.'assets/css/rc-choose-options-logo.css', array(), '1.0', 'all' );
    }

    /**
     * Load scripts and styles for cart mode,
     * Ensure that files are loaded once
     * @return void
     */
    public function load_cart_scripts() {

        // Enqueued only in concerned checkout page
        if ( !is_cart() ) return;

        // Check that loaded once
        if ( in_array( self::PREFIX_RC, self::$loaded_scripts ) ) return;
        self::$loaded_scripts[] = self::PREFIX_RC;

        // Relais colis plugin URL
        $plugin_url = Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url();

        wp_enqueue_style( self::PREFIX_RC.'_icon_css', $plugin_url.'assets/css/rc-choose-options-logo.css', array(), '1.0', 'all' );
    }
}