<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping Method Manager.
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Method_Manager {

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
    }

    /**
     * Add all WC_Shipping_Method
     *
     * @param $methods list of shipping methods
     * @return mixed
     */
    public function filter_woocommerce_shipping_methods( $methods ) {

        // Add WC_Shipping_Method for Relais
        // It depends on RC enseigne options
        if ( WC_RC_Shipping_Config_Manager::instance()->has_delivery_offer_enabled( WC_RC_Shipping_Constants::OFFER_RELAIS_COLIS ) ) {

            $methods[ WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID ] = 'RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Method_Relay';
        }
        if ( WC_RC_Shipping_Config_Manager::instance()->has_delivery_offer_enabled( WC_RC_Shipping_Constants::OFFER_HOME ) ) {

            $methods[ WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID ] = 'RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Method_Home';
        }
        if ( WC_RC_Shipping_Config_Manager::instance()->has_delivery_offer_enabled( WC_RC_Shipping_Constants::OFFER_HOME_PLUS ) ) {

            $methods[ WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID ] = 'RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Method_Homeplus';
        }

        WP_Log::debug( __METHOD__, [ 'methods' => $methods ], 'relais-colis-woocommerce' );

        return $methods;
    }
}
