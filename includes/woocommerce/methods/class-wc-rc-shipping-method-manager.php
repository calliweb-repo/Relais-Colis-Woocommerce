<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Order;

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
     * Verify if the method id is one of Relais Colis methods
     * @param $method_id
     * @return boolean true if that is a RC method, otherwise false
     */
    public function is_a_rc_shipping_method( $method_id ) {

        $method_list = array(
            WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID,
            WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID,
            WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID,
        );
        return in_array( $method_id, $method_list );
    }

    /**
     * Get the shipping methods list
     * @return array
     */
    public function get_rc_shipping_methods() {

        return array(
            WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID => WC_RC_Shipping_Constants::OFFER_RELAIS_COLIS,
            WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID => WC_RC_Shipping_Constants::OFFER_HOME,
            WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID => WC_RC_Shipping_Constants::OFFER_HOME_PLUS,
        );
    }

    /**
     * Get name name
     * @param $method_id
     * @return string
     */
    public function get_rc_shipping_method_name( $method_id ) {

        switch( $method_id ) {
            case WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID:
                return WC_RC_Shipping_Constants::OFFER_RELAIS_COLIS;
            case WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID:
                return WC_RC_Shipping_Constants::OFFER_HOME;
            case WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID:
                return WC_RC_Shipping_Constants::OFFER_HOME_PLUS;
        }
    }

    /**
     * Get the RC shipping method associated with an order
     * @param $wc_order
     * @return int|boolean the method id, otherwise false if no RC shipping method found
     */
    public function get_rc_shipping_method( WC_Order $wc_order ) {

        // Retrieve the shipping methods used in the order
        $shipping_methods = $wc_order->get_shipping_methods();

        foreach ( $shipping_methods as $shipping_method ) {

            WP_Log::debug( __METHOD__, [ '$shipping_method' => $shipping_method ], 'relais-colis-woocommerce' );

            // Check if the shipping method is "Relais Colis"
            if ( $this->is_a_rc_shipping_method( $shipping_method->get_method_id() ) ) {

                return $shipping_method->get_method_id();
            }
        }
        return false;
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
