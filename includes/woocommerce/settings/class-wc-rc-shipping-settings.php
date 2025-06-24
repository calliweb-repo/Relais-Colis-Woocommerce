<?php

namespace RelaisColisWoocommerce\Shipping;
// @phpcs:disable WordPress.Security.NonceVerification.Recommended
defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use WC_Settings_Page;

/**
 * WooCommerce Shipping Settings.
 * Manage overall Relais Colis settings tab, and delegation
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Settings extends WC_Settings_Page {

    /**
     * Constructor
     */
    public function __construct() {

        $this->id = WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS;
        $this->label = __( 'Relais Colis', 'relais-colis-woocommerce' );

        parent::__construct();

        // Initialize all hooks
        $this->init();
    }

    /**
     * Init settings
     * @return void
     */
    public function init(): void {

        // Register settings tab name
        add_filter('woocommerce_settings_tabs_array', array( $this, 'filter_woocommerce_settings_tabs_array' ), 50);

        // Register settings section
        add_filter( 'woocommerce_get_sections_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'filter_woocommerce_get_sections_rc' ), 1 );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
    }

    /***
     * Adding CSS and JS into header
     * Default add assets/admin.css and assets/admin.js
     */
    public function action_admin_enqueue_scripts() {

        // Enqueued only in RC settings page
        $screen = get_current_screen();
        if ( ( $screen->id !== 'woocommerce_page_wc-settings' ) || !isset($_GET['tab']) || ( $_GET['tab'] !== WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS ) ) {

            return;
        }

        // CSS
        wp_enqueue_style(WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS.'_css', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/relais-colis.css', array(), '1.0', 'all');
   }

    /**
     * Initialize tab Relais Colis
     * Called before other Steeings to delete Général default tab
     * @param $sections
     * @return mixed
     */
    public function filter_woocommerce_get_sections_rc( $sections ) {

        return array();
    }

    /**
     * Register Relais Colis settings tab name
     * @param $settings_tabs
     * @return mixed
     */
    public function filter_woocommerce_settings_tabs_array($settings_tabs): mixed
    {
        $settings_tabs[WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS] = __('Relais Colis', 'relais-colis-woocommerce');
        return $settings_tabs;
    }
}
