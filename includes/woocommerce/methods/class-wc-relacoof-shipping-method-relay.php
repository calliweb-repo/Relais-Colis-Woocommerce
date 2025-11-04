<?php

namespace RelaisColisWoocommerce\Shipping;

use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * Specific WooCommerce Shipping Method Class for Relais Colis
 *
 * Extend shipping methods to handle shipping calculations etc.
 *
 * @since     1.0.0
 */
class WC_Relacoof_Shipping_Method_Relay extends WC_Relacoof_Shipping_Method {

    const WC_Relacoof_Shipping_Method_RELAY_ID = 'WC_Relacoof_Shipping_Method_relay';

    /**
     * Constructor.
     *
     * @param int $instance_id Instance ID.
     */
    public function __construct( $instance_id = 0 ) {

        parent::__construct( $instance_id );

        // Unique ID
        $this->id = self::WC_Relacoof_Shipping_Method_RELAY_ID;
        
        // Relais colis
        $this->method_description = __( 'Relais Colis: concerns collection from relay points.', 'relais-colis-officiel');

        // Default activation
        $this->enabled = "yes";

        // Load method options
        $this->init();

        // Add a LiveMapping relay selection map after shipping method choice
        // Old checkout: using woocommerce_after_shipping_rate
        // FSE checkout: using checkout/blocs
        add_action('woocommerce_after_shipping_rate', array( $this, 'action_woocommerce_after_shipping_rate'), 10, 2);
    }

    /**
     * Get the specific ID for Relais Colis child class
     */
    protected function get_WC_Relacoof_Shipping_Method_default_title() {

        return __( 'Relais Colis', 'relais-colis-officiel');
    }

    /**
     * Template Method used to convert this method id into DB used method name
     * @return string
     */
    protected function get_database_method_name() {

        return WC_Relacoof_Shipping_Constants::METHOD_NAME_RELAIS_COLIS;
    }

    public function action_woocommerce_after_shipping_rate( $method, $index) {

        WP_Log::debug( __METHOD__, ['method'=>$method, 'index'=>$index ], 'relais-colis-officiel');
        //echo "<p style='color: red;'>woocommerce_after_shipping_rate</p>";
    }
}
