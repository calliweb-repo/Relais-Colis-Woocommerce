<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Utils\WP_Log;


/**
 * Specific WooCommerce Shipping Method Class for Relais Colis Home
 *
 * Extend shipping methods to handle shipping calculations etc.
 *
 * @since     1.0.0
 */
class WC_Relacoof_Shipping_Method_Home extends WC_Relacoof_Shipping_Method {

    const WC_Relacoof_Shipping_Method_HOME_ID = 'WC_Relacoof_Shipping_Method_home';

    /**
     * Constructor.
     *
     * @param int $instance_id Instance ID.
     */
    public function __construct( $instance_id = 0 ) {

        parent::__construct( $instance_id );

        // Unique ID
        $this->id = self::WC_Relacoof_Shipping_Method_HOME_ID;

        // Relais colis
        //$this->method_title = $this->get_WC_Relacoof_Shipping_Method_default_title();
        $this->method_description = __( 'Relais Colis: home deliveries.', 'relais-colis-officiel');
        //$this->title = isset( $this->settings[ 'title' ] ) ? $this->settings[ 'title' ] : $this->get_WC_Relacoof_Shipping_Method_default_title();

        // Load method options
        $this->init();
    }

    /**
     * Get the specific ID for Relais Colis child class
     */
    protected function get_WC_Relacoof_Shipping_Method_default_title() {

        return __( 'Relais Colis Home', 'relais-colis-officiel');
    }

    /**
     * Template Method used to convert this method id into DB used method name
     * @return string
     */
    protected function get_database_method_name() {

        return WC_Relacoof_Shipping_Constants::METHOD_NAME_HOME;
    }
}
