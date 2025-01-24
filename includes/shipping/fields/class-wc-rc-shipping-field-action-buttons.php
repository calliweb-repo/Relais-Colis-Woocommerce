<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * WooCommerce Shipping rc_action_buttons field definition
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Field_Action_Buttons {

    const FIELD_RC_ACTION_BUTTONS = 'rc_action_buttons';

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
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_ACTION_BUTTONS, array( $this, 'action_woocommerce_admin_field_rc_action_buttons' ), 10, 1 );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

        // Init AJAX handlers
        WC_RC_Ajax_Extract_Client_Info::instance();
        WC_RC_Ajax_Refresh_Client_Info::instance();
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
        wp_enqueue_script( self::FIELD_RC_ACTION_BUTTONS.'_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/field-action-buttons.js', array( 'jquery' ), '1.0', true );

        // Pass script params to JS
        wp_localize_script( self::FIELD_RC_ACTION_BUTTONS.'_js', 'rc_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'rc-api-action' ),
            'nom_label' => __( 'Name', 'relais-colis-woocommerce' ),
            'prenom_label' => __( 'Firstname', 'relais-colis-woocommerce' ),
            'solde_label' => __( 'Balance', 'relais-colis-woocommerce' ),
        ) );
    }

    /**
     * Render field
     * @param $field
     */
    public function action_woocommerce_admin_field_rc_action_buttons( $field ) {

        $html_content = '
            <button type="button" id="rc-refresh-info" class="button">' . esc_html__('Rafraîchir les informations', 'relais-colis-woocommerce') . '</button>
            <button type="button" id="rc-extract-info" class="button">' . esc_html__('Extraire les informations', 'relais-colis-woocommerce') . '</button>
            <div id="rc-client-info" style="margin-top: 10px;"></div>
        ';

        echo $html_content;
    }
}
