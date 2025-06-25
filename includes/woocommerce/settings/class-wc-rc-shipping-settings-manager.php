<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping Settings Manager.
 *
 * Used to centralize all initialization (called early to attach hooks at the good time, as ajax handlers)
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Settings_Manager {

    const WC_RC_SHIPPING_SETTINGS = 'wc_rc_shipping_settings';

    // Use Trait Singleton
    use Singleton;

    private $wc_rc_shipping_settings = null;

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // Add a new settings tab to WooCommerce
        add_filter('woocommerce_get_settings_pages', array( $this, 'filter_woocommerce_get_settings_pages'), 10, 1 );

        //
        // Init sections
        //
        WC_RC_Shipping_General_Settings::instance();
        WC_RC_Shipping_Infos_Settings::instance();
        WC_RC_Shipping_Units_Settings::instance();

        // B2C
        WC_RC_Shipping_Services_Settings::instance();
        WC_RC_Shipping_Tariff_Grids_Settings::instance();

        //
        // Init custom fields
        //
        WC_RC_Shipping_Field_Multiselect_Products::instance();
        WC_RC_Shipping_Field_Tariff_Grids::instance();
        WC_RC_Shipping_Field_Enable::instance();
        WC_RC_Shipping_Field_Custom_Html::instance();
        WC_RC_Shipping_Field_Copy_Paste_Button::instance();
        WC_RC_Shipping_Field_Refresh_Button::instance();
    }

    /**
     * Add all settings
     *
     * @param $settings
     * @return mixed
     */
    public function filter_woocommerce_get_settings_pages( $settings ) {

        if ( is_null( $this->wc_rc_shipping_settings ) ) {

            $this->wc_rc_shipping_settings = new WC_RC_Shipping_Settings();
        }
        $settings[] = $this->wc_rc_shipping_settings;
        return $settings;
    }

    /**
     * Generate alternative settings for invalid message
     * @return array
     */
    public function get_invalid_licence_settings() {

        // Generate HTML for informations
        $general_url = esc_url( admin_url( 'admin.php?page=wc-settings&tab='.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS.'&section='.WC_RC_Shipping_General_Settings::SECTION_GENERAL ) );
        $infos_html = '
            <div class="rc_invalid_license_notice">
                <h2><span>⚠</span> '.__( 'License Issue', 'relais-colis-woocommerce' ).'</h2>
                <p>
                    '.__( 'Please enter a valid activation key to access all features.', 'relais-colis-woocommerce' ).'
                </p>
                <p>
                    <a href="'.$general_url.'" class="button button-primary">
                       '.__( 'Enter my license', 'relais-colis-woocommerce' ).'
                    </a>
                </p>
            </div>
            ';

        // Hide save button
        ?>
        <style>
            body .woocommerce-save-button.components-button.is-primary:disabled {
                display: none !important;
            }
        </style>
        <?php

        return [
            [
                'type' => WC_RC_Shipping_Field_Custom_Html::FIELD_RC_CUSTOM_HTML,
                'id' => 'rc_invalid_licence_html',
                'html' => $infos_html,
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_invalid_licence_section_end',
            ]
        ];
    }
}
