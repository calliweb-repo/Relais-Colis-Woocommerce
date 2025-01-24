<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping Method Manager.
 *
 * Used to register all WC_Shipping_Method
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Manager {

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

        // Add WC_Shipping_Methods
        add_filter('woocommerce_shipping_methods', array( $this, 'filter_woocommerce_shipping_methods'), 10, 1 );

        // Add a new settings tab to WooCommerce
        add_filter('woocommerce_get_settings_pages', array( $this, 'filter_woocommerce_get_settings_pages'), 10, 1 );
    }

    /**
     * Add all WC_Shipping_Method
     *
     * @param $methods list of shipping methods
     * @return mixed
     */
    public function filter_woocommerce_shipping_methods( $methods ) {

        // Add WC_Shipping_Method for Relais
        $methods[ WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID ] = 'RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Method_Home';
        $methods[ WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID ] = 'RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Method_Homeplus';
        $methods[ WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID ] = 'RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Method_Relay';
        WP_Log::debug( __METHOD__, [ 'methods' => $methods ], 'relais-colis-woocommerce' );

        return $methods;
    }

    /**
     * Add all settings
     *
     * @param $settings
     * @return mixed
     */
    public function filter_woocommerce_get_settings_pages( $settings ) {

        $settings[] = new WC_RC_Shipping_Settings();
        return $settings;
    }
}
