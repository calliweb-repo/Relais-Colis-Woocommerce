<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

/**
 * Specific WooCommerce Shipping Method Class for Relais Colis
 *
 * Extend shipping methods to handle shipping calculations etc.
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Method_Relay extends WC_RC_Shipping_Method {

    const WC_RC_SHIPPING_METHOD_RELAY_ID = 'wc_rc_shipping_method_relay';

    /**
     * Constructor.
     *
     * @param int $instance_id Instance ID.
     */
    public function __construct( $instance_id = 0 ) {

        parent::__construct( $instance_id );

        // Unique ID
        $this->id = self::WC_RC_SHIPPING_METHOD_RELAY_ID;
        
        // Relais colis
        $this->method_title = $this->get_wc_rc_shipping_method_default_title();
        $this->method_description = __( 'Relais Colis: concerns collection from relay points.', 'relais-colis-woocommerce' );

        // Default activation
        $this->enabled = "yes";
        $this->title = $this->get_wc_rc_shipping_method_default_title();

        // Load method options
        $this->init();
    }

    /**
     * Get the specific ID for Relais Colis child class
     */
    protected function get_wc_rc_shipping_method_default_title() {

        return __( 'Relais Colis', 'relais-colis-woocommerce' );
    }
}
