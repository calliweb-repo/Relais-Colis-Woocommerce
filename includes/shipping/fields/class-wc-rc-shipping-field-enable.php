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
class WC_RC_Shipping_Field_Enable {

    const FIELD_RC_ENABLE_CHECKBOX = 'rc_enable_checkbox';

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
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_ENABLE_CHECKBOX, array( $this, 'action_woocommerce_admin_field_rc_enable_checkbox' ), 10, 1 );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
    }

    /**
     * Enqueue needed scripts
     */
    public function action_admin_enqueue_scripts() {

        // Enqueued only in concerned settings page
        $screen = get_current_screen();
        if ( ( $screen->id !== 'woocommerce_page_wc-settings' ) || !isset($_GET['tab']) || ( $_GET['tab'] !== WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS ) ) {

            return;
        }

        // JS
        wp_enqueue_script( self::FIELD_RC_ENABLE_CHECKBOX.'_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/field-enable.js', array( 'jquery' ), '1.0', true );

        // CSS
        wp_enqueue_style(self::FIELD_RC_ENABLE_CHECKBOX.'_css', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/field-enable.css', array(), '1.0', 'all');
    }

    /**
     * Render field
     * @param $field
     */
    public function action_woocommerce_admin_field_rc_enable_checkbox( $field ) {

        WP_Log::notice( __METHOD__, ['$field'=>$field], 'relais-colis-woocommerce' );

        // FIXME
        $value = 0;
        //$checked_text = checked( $value, 1, false );
        $checked_text = checked( $field['value'], 'yes' );

        ?>
        <tr>
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
            </th>
            <td class="forminp forminp-checkbox">
                <div class="<?php echo esc_attr( $field['field_name'] ); ?>">
                    <input
                        name="<?php echo esc_attr( $field['field_name'] ); ?>"
                        id="<?php echo esc_attr( $field['id'] ); ?>"
                        type="checkbox"
                        class="<?php echo esc_attr( isset( $field['class'] ) ? $field['class'] : '' ); ?>"
                        value="yes"
                        <?php $checked_text; ?>
                    >
                    <input
                        type="hidden"
                        name="hidden_checkbox_<?php echo esc_attr( $field['field_name'] ); ?>"
                        id="hidden_checkbox_<?php echo esc_attr( $field['field_name'] ); ?>"
                        value="yes"
                    >
                    <span class="button button-<?php ($checked_text ? 'primary' : 'secondary'); ?>">Live</span><span class="button button-<?php ($checked_text ? 'primary' : 'secondary'); ?>">Test</span>
                    <?php echo $field['desc']; // WPCS: XSS ok. ?>
                </div>
            </td>
        </tr>
        <?php
    }
}
