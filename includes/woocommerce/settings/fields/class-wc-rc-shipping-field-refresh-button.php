<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended
namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * WooCommerce Shipping rc_refresh_button field definition
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Field_Refresh_Button {

    const FIELD_RC_REFRESH_BUTTON = 'rc_refresh_button';

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
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_REFRESH_BUTTON, array( $this, 'action_woocommerce_admin_field_rc_refresh_button' ), 10, 1 );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

        // Use AJAX handler
        WC_RC_Ajax_Refresh_Infos::instance();
    }

    /**
     * Enqueue needed scripts
     */
    public function action_admin_enqueue_scripts() {

        // Enqueued only in concerned settings page
        $screen = get_current_screen();
        if ( ( $screen->id !== 'woocommerce_page_wc-settings' ) || !isset($_GET['tab']) || ( $_GET['tab'] !== WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS ) ) {

            return;
        }

        // JS
        wp_enqueue_script( self::FIELD_RC_REFRESH_BUTTON.'_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/field-refresh-button.js', array( 'jquery' ), '1.0', true );

        // Pass script params to JS
        wp_localize_script( self::FIELD_RC_REFRESH_BUTTON.'_js',
            'rc_refresh_button_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'rc_refresh_button_nonce' ),
            )
        );
    }

    /**
     * Render field
     * @param $field
     */
    public function action_woocommerce_admin_field_rc_refresh_button( $field ) {

        ob_start(); ?>
        <div>
            <button type="button" class="<?php echo esc_attr(self::FIELD_RC_REFRESH_BUTTON); ?> button button-primary"><?php echo esc_html__('Refresh your information', 'relais-colis-woocommerce') ?></button>
        </div>
        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo ob_get_clean();
    }
}
