<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended
namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping rc_enable_checkbox field definition
 *
 * @since 1.0.0
 */
class WC_RC_Shipping_Field_Enable {

    const FIELD_RC_ENABLE_CHECKBOX = 'rc_enable_checkbox';

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
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_ENABLE_CHECKBOX, [ $this, 'action_woocommerce_admin_field_rc_enable_checkbox' ], 10, 1 );

        // Register scripts
        add_action( 'admin_enqueue_scripts', [ $this, 'action_admin_enqueue_scripts' ] );
    }

    /**
     * Enqueue needed scripts
     */
    public function action_admin_enqueue_scripts() {

        // Enqueued only in concerned settings page
        $screen = get_current_screen();
        if ( $screen->id !== 'woocommerce_page_wc-settings' || !isset( $_GET[ 'tab' ] ) || $_GET[ 'tab' ] !== WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS ) {
            return;
        }

        // JS
        wp_enqueue_script( self::FIELD_RC_ENABLE_CHECKBOX.'_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/field-enable.js', [ 'jquery' ], '1.0', true );

        // CSS
        wp_enqueue_style( self::FIELD_RC_ENABLE_CHECKBOX.'_css', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/field-enable.css', [], '1.0', 'all' );
    }

    /**
     * Render field
     * @param $field
    */
    public function action_woocommerce_admin_field_rc_enable_checkbox( $field ) {

        WP_Log::debug( __METHOD__, [ '$field' => $field ], 'relais-colis-woocommerce' );

        // Ensure the field ID exists
        if ( empty( $field[ 'id' ] ) ) {

            return;
        }

        // Set defaults
        $defaults  = array(
            'title'     => '',
            'type'      => WC_RC_Shipping_Field_Enable::FIELD_RC_ENABLE_CHECKBOX,
            'desc'      => '',
            'yes_label' => __('Yes', 'relais-colis-woocommerce'),
            'no_label'  => __('No', 'relais-colis-woocommerce'),
            'default'   => 'yes',
            'disabled'   => false,
        );
        $field  = wp_parse_args( $field, $defaults );

        $value = isset( $field[ 'value' ] ) && $field[ 'value' ] === 'yes' ? 'yes' : 'no';
        $is_active = $value === 'yes' ? 'active' : '';

        ?>
        <tr>
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field[ 'id' ] ); ?>"><?php echo esc_html( $field[ 'title' ] ); ?></label>
            </th>
            <td class="forminp forminp-checkbox">
                <div class="rc_enable_checkbox <?php echo esc_attr( $is_active ); ?>">
                    <input
                            type="hidden"
                            name="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"
                            id="<?php echo esc_attr( $field[ 'id' ] ); ?>_hidden"
                            value="<?php echo esc_attr( $value ); ?>"
                    >
                    <input
                            name="<?php echo esc_attr( $field[ 'field_name' ] ); ?>"
                            id="<?php echo esc_attr( $field[ 'id' ] ); ?>"
                            type="checkbox"
                            value="yes"
                        <?php disabled( $field[ 'disabled' ], true ); ?>
                        <?php checked( $value, 'yes' ); ?>
                    >
                    <div class="toggle-switch"></div>
                    <span class="label-off"><?php echo esc_html( $field[ 'no_label' ] ); ?></span>
                    <span class="label-on"><?php echo esc_html( $field[ 'yes_label' ] ); ?></span>
                </div>
                <?php if ( !empty( $field[ 'desc' ] ) ) { ?>
                    <p class="description"><?php echo esc_html( $field['desc'] ); ?></p>
                <?php } ?>
            </td>
        </tr>
        <?php
    }
}