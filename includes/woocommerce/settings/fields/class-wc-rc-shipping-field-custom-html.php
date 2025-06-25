<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping rc_enable_checkbox field definition
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Field_Custom_Html {

    const FIELD_RC_CUSTOM_HTML = 'rc_custom_html';

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

        // Render custom fields
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_CUSTOM_HTML, array( $this, 'action_woocommerce_admin_field_rc_custom_html' ), 10, 1 );
    }

    /**
     * Render field
     * @param $field
     */
    public function action_woocommerce_admin_field_rc_custom_html( $field ) {

        WP_Log::debug( __METHOD__, [ '$field' => $field ], 'relais-colis-woocommerce' );

        if ( isset( $field['html'] ) ) {

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $field['html'];
        }
    }
}
