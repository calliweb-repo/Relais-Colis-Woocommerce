<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * WooCommerce Shipping rc_action_buttons field definition
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Field_Copy_Paste_Button {

    const FIELD_RC_COPY_PASTE_BUTTON = 'rc_copy_paste_button';

    private $copy_paste_button_css_class = 'copy_paste_button';
    private $info_text_area_css_class = 'info_text_area';

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
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_COPY_PASTE_BUTTON, array( $this, 'action_woocommerce_admin_field_rc_copy_paste_button' ), 10, 1 );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
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
        wp_enqueue_script( self::FIELD_RC_COPY_PASTE_BUTTON.'_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/field-copy-paste-button.js', array( 'jquery' ), '1.0', true );

        // Pass script params to JS
        wp_localize_script( self::FIELD_RC_COPY_PASTE_BUTTON.'_js',
            'rc_params', array(
                'copy_paste_button_css_class' => $this->copy_paste_button_css_class,
                'info_text_area_css_class' => $this->info_text_area_css_class,
                'copied_label' => __( 'Copied!', 'relais-colis-woocommerce' ),
                'copy_failed_label' => __( 'Copy failed!', 'relais-colis-woocommerce' ),
            )
        );
    }

    /**
     * Render field
     * @param $field
     */
    public function action_woocommerce_admin_field_rc_copy_paste_button( $field ) {

        // Get formatted info
        $formatted_info = $field['text'];

        ?>
        <div>
            <textarea class="<?php echo esc_attr($this->info_text_area_css_class); ?>" style="display:none;" readonly><?php echo esc_textarea($formatted_info); ?></textarea>
            <button type="button" class="<?php echo esc_attr($this->copy_paste_button_css_class); ?> button button-primary"><?php echo esc_html__('Copy the information to the clipboard', 'relais-colis-woocommerce') ?></button>
        </div>
        <?php
    }
}
