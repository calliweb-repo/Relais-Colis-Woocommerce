<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Shipping_Method;


/**
 * Specific WooCommerce Shipping Method Class for Relais Colis
 *
 * Extend shipping methods to handle shipping calculations etc.
 *
 * @since     1.0.0
 */
abstract class WC_RC_Shipping_Method extends WC_Shipping_Method {

    /**
     * Constructor.
     *
     * @param int $instance_id Instance ID.
     */
    public function __construct( $instance_id = 0 ) {

        parent::__construct( $instance_id );

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        // Have to be defined in child
        // - Unique ID: $this->id
        // - Method title: $this->method_title
        // - Method description: $this->method_description
        // - Title: $this->title

        // Default activation
        $this->enabled = "yes";

        // Define what this shipping method supports
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        // Load method options
        // Have to be called in child class: $this->init();
    }

    /**
     * Get the specific ID for Relais Colis child class
     */
    abstract protected function get_wc_rc_shipping_method_default_title();

    /**
     *  Init defines the parameter strategy for loading/saving
     */
    public function init() {

        // Load parameters
        $this->init_form_fields();
        $this->init_settings();

        // Save parameters
        add_action( 'woocommerce_update_options_shipping_'.$this->id, array( $this, 'process_admin_options' ) );

        WP_Log::debug( __METHOD__.' - Init OK', [], 'relais-colis-woocommerce' );
    }

    /**
     * Initialise Shipping Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title' => __( 'Enable/Disable', 'relais-colis-woocommerce' ),
                'type' => 'checkbox',
                'description' => __( 'Enable this shipping method.', 'relais-colis-woocommerce' ),
                'default' => 'yes',
            ],
            'title' => [
                'title' => __( 'Title', 'relais-colis-woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'relais-colis-woocommerce' ),
                'default' => $this->get_wc_rc_shipping_method_default_title(),
            ],
        ];
        WP_Log::debug( __METHOD__.' - Init Form fields OK', [], 'relais-colis-woocommerce' );
    }

    /**
     * Called to calculate shipping rates for this method. Rates can be added using the add_rate() method.
     *
     * @param array $package Package array.
     * @override
     */
    public function calculate_shipping( $package = [] ) {

        // FIXME tests
        $cost = $this->get_option( 'cost' );
        $rate = [
            'id' => $this->id,
            'label' => $this->title,
            'calc_tax' => 'per_order',
        ];
        $this->add_rate( $rate );
    }
}
