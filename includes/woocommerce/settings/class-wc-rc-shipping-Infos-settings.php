<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Configuration_DAO;
use RelaisColisWoocommerce\DAO\WP_Information_DAO;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping Settings for Informations section
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Infos_Settings {

    const SECTION_INFORMATIONS = 'informations';

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

        // Register settings section
        add_filter( 'woocommerce_get_sections_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'filter_woocommerce_get_sections_rc' ) );

        // Register settings section
        add_action( 'woocommerce_settings_tabs_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_settings_rc_informations' ) );

        // Update settings section
        add_action( 'woocommerce_update_options_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS.'_'.self::SECTION_INFORMATIONS, array( $this, 'action_woocommerce_update_options_rc_informations' ) );

        // Add current CSS class to body classes (to allow hiding save buttons)
        add_filter( 'admin_body_class', array( $this, 'action_admin_body_class' ) );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
    }

    /**
     * Add body classes
     * @param $classes
     * @return mixed|string
     */
    public function action_admin_body_class( $classes ) {

        // Add the current CSS class tothe body classes
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        WP_Log::debug( __METHOD__, [ '$classes' => $classes, '$_GET' => $_GET ], 'relais-colis-woocommerce' );

        if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] === 'wc-settings' && isset( $_GET[ 'section' ] ) ) {
            $classes .= ' wc-settings-sub-tab-'.self::SECTION_INFORMATIONS;
        }
        return $classes;
    }

    /**
     * Enqueue needed scripts
     */
    public function action_admin_enqueue_scripts() {

        // Enqueued only in concerned settings page
        $screen = get_current_screen();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        WP_Log::debug( __METHOD__, [ '$screen' => $screen, '$_GET' => $_GET ], 'relais-colis-woocommerce' );
        if ( ( $screen->id === 'woocommerce_page_wc-settings' ) && isset( $_GET[ 'section' ] ) && ( $_GET[ 'section' ] === self::SECTION_INFORMATIONS ) ) {

            ?>
            <style>
                    body.wc-settings-sub-tab-<?php echo esc_attr(self::SECTION_INFORMATIONS); ?> .woocommerce-save-button.components-button.is-primary:disabled {
                        display: none !important;
                    }
            </style>
            <?php
        }
    }

    /**
     * Add section to the tab Relais Colis
     * @param $sections
     * @return mixed
     */
    public function filter_woocommerce_get_sections_rc( $sections ) {

        $sections[ self::SECTION_INFORMATIONS ] = __( 'Your Information', 'relais-colis-woocommerce' );
        return $sections;
    }

    /**
     * Add properties to the current section
     * @param $sections
     * @return mixed
     */
    public function action_woocommerce_settings_rc_informations() {

        global $current_section;
        if ( $current_section !== self::SECTION_INFORMATIONS ) return;

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Update properties
     */
    public function action_woocommerce_update_options_rc_informations() {

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        woocommerce_update_options( $this->get_settings() );
    }

    /**
     * Get the properties
     * @return array
     */
    private function get_settings() {

        // Other tabs loaded only if RC API access is valid
        if ( !WC_RC_Shipping_Config_Manager::instance()->is_rc_api_valid_access() ) {

            return WC_RC_Shipping_Settings_Manager::instance()->get_invalid_licence_settings();
        }

        // Get RC configuration
        $rc_configuration = WP_Configuration_DAO::instance()->get_rc_configuration( true );
        WP_Log::debug( __METHOD__, [ '$rc_configuration' => $rc_configuration ], 'relais-colis-woocommerce' );

        // Generate HTML for configurations
        $rc_configuration_html = '';

        // Generate text for copy paste button
        $copy_paste_formatted_info = '## Configuration ##'."\n"."\n";

        if ( !empty( $rc_configuration ) ) {

            //$rc_configuration_html = '<table class="rc-information-table">';
            $rc_configuration_html = '<table class="form-table">';
            foreach ( $rc_configuration as $key => $value ) {

                $rc_configuration_html .= sprintf(
                    '<tr><th>%s</th><td>%s</td></tr>',
                    WC_RC_Shipping_Constants::get_configuration_title( $key ),
                    is_bool( $value ) ? ( $value ? 'Yes' : 'No' ) : esc_html( $value )
                );

                $copy_paste_formatted_info .= $key.' ('.WC_RC_Shipping_Constants::get_configuration_title( $key ).') : '.$value."\n";
            }
            $rc_configuration_html .= '</table>';
            $copy_paste_formatted_info = esc_textarea( $copy_paste_formatted_info );
        }

        // Init settings
        $settings = array(
            // Section : B2C Configuration
            [
                'title' => __( 'Configuration', 'relais-colis-woocommerce' ), // Options B2C
                'type' => 'title',
                'desc' => __( 'The configuration associated with your account.', 'relais-colis-woocommerce' ), // Configurez les options incluses dans votre compte B2C.
                'id' => 'rc_b2c_configuration_title',
            ],
            // Copy paste button
            [
                'type' => WC_RC_Shipping_Field_Copy_Paste_Button::FIELD_RC_COPY_PASTE_BUTTON,
                'id' => WC_RC_Shipping_Field_Copy_Paste_Button::FIELD_RC_COPY_PASTE_BUTTON,
                'text' => $copy_paste_formatted_info,
            ],
            [
                'type' => WC_RC_Shipping_Field_Custom_Html::FIELD_RC_CUSTOM_HTML,
                'id' => 'rc_config_details',
                'html' => $rc_configuration_html,
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_b2c_configuration_section_end',
            ],

        );

        // Get C2C infos is C2C enabled
        if ( WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode() ) {

            // Get RC informations
            $rc_c2c_infos = WP_Information_DAO::instance()->get_rc_information();
            WP_Log::debug( __METHOD__, [ '$rc_c2c_infos' => $rc_c2c_infos ], 'relais-colis-woocommerce' );

            // Generate HTML for configurations
            $rc_c2c_infos_html = '';

            // Generate text for copy paste button
            $copy_paste_formatted_info = '## C2C Informations ##'."\n"."\n";

            if ( !empty( $rc_c2c_infos ) ) {

                $rc_c2c_infos_html = '<table class="rc-information-table">';
                $rc_c2c_infos_html = '<table class="form-table">';
                foreach ( $rc_c2c_infos as $key => $value ) {

                    $rc_c2c_infos_html .= sprintf(
                        '<tr><th>%s</th><td>%s</td></tr>',
                        WC_RC_Shipping_Constants::get_information_title( $key ),
                        is_bool( $value ) ? ( $value ? 'Yes' : 'No' ) : esc_html( $value )
                    );

                    $copy_paste_formatted_info .= $key.' ('.WC_RC_Shipping_Constants::get_information_title( $key ).') : '.$value."\n";
                }
                $rc_c2c_infos_html .= '</table>';
                $copy_paste_formatted_info = esc_textarea( $copy_paste_formatted_info );
            }

            // Add settings: Section : C2C infos
            $settings[] = array(
                'title' => __( 'C2C informations', 'relais-colis-woocommerce' ), // Options B2C
                'type' => 'title',
                'desc' => __( 'The informations associated with your C2C account.', 'relais-colis-woocommerce' ), // Configurez les options incluses dans votre compte B2C.
                'id' => 'rc_c2c_infos_title',
            );
            // Copy paste button
            $settings[] = array(
                'type' => WC_RC_Shipping_Field_Copy_Paste_Button::FIELD_RC_COPY_PASTE_BUTTON,
                'id' => WC_RC_Shipping_Field_Copy_Paste_Button::FIELD_RC_COPY_PASTE_BUTTON,
                'text' => $copy_paste_formatted_info,
            );
            $settings[] = array(
                'type' => WC_RC_Shipping_Field_Custom_Html::FIELD_RC_CUSTOM_HTML,
                'id' => 'rc_c2c_infos_details',
                'html' => $rc_c2c_infos_html,
            );
            $settings[] = array(
                'type' => 'sectionend',
                'id' => 'rc_c2c_infos_section_end',
            );
        }
        return $settings;
    }
}
