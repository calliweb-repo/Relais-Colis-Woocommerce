<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended
namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Configuration_DAO;
use RelaisColisWoocommerce\DAO\WP_Information_DAO;
use RelaisColisWoocommerce\RCAPI\WP_RC_Get_Infos_Response;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Admin_Settings;

/**
 * WooCommerce Shipping Settings for General section
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_General_Settings {

    const SECTION_GENERAL = '';

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
        add_action( 'woocommerce_settings_tabs_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_settings_rc_general' ) );

        // Update settings section
        add_action( 'woocommerce_update_options_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_update_options_rc_general' ) );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

        // Field validation
        add_filter( 'woocommerce_admin_settings_sanitize_option_'.WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, array( $this, 'filter_woocommerce_admin_settings_sanitize_option_activation_key' ), 10, 3 );
        add_filter( 'woocommerce_admin_settings_sanitize_option_'.WC_RC_Shipping_Constants::OPTION_C2C_HASH_TOKEN, array( $this, 'filter_woocommerce_admin_settings_sanitize_option_c2c_hash_token' ), 20, 3 );
    }

    /**
     * Update properties
     */
    public function action_woocommerce_update_options_rc_general() {

        global $current_section;
        if ( $current_section !== self::SECTION_GENERAL ) return;

        WP_Log::debug( __METHOD__, [ 'POST' => $_POST ], 'relais-colis-woocommerce' );

        woocommerce_update_options( $this->get_settings() );
    }

    /**
     * Enqueue needed scripts
     */
    public function action_admin_enqueue_scripts() {

        // Enqueued only in concerned settings page
        $screen = get_current_screen();
        if ( ( $screen->id !== 'woocommerce_page_wc-settings' ) || !isset( $_GET[ 'tab' ] ) || ( $_GET[ 'tab' ] !== WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS ) ) {

            return;
        }

        // JS
        wp_enqueue_script( 'rc-api-validation', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/api-validation.js', array( 'jquery' ), '1.0', true );

        // Pass script params to JS
        wp_localize_script( 'rc-api-validation', 'rc_ajax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'rc-api-action' ),
            'rc_api_key' => WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY,
        ] );
    }

    /**
     * Check activation key value
     * @param $value
     * @param $option
     * @param $raw_value
     * @return mixed
     */
    public function filter_woocommerce_admin_settings_sanitize_option_activation_key( $value, $option, $raw_value ) {

        WP_Log::debug( __METHOD__, [ 'value' => $value, 'option' => $option, 'raw_value' => $raw_value, 'POST'=>$_POST ], 'relais-colis-woocommerce' );

        // Call API again because test mode has changed
        try {

            // If empty key, just delete options
            if ( empty( $value ) ) {

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_b2c_config_data();

                return $value;
            }

            // Update option with given activation key before API RC request to be able to request it
            update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, $value );
            update_option( WC_RC_Shipping_Constants::OPTION_LIVE_TEST_MODE_NAME, $_POST[WC_RC_Shipping_Constants::OPTION_LIVE_TEST_MODE_NAME] );

            // Configuration depends on interaction mode
            $wp_rc_configuration = WP_Relais_Colis_API::instance()->get_b2c_configuration( false );

            if ( is_null( $wp_rc_configuration ) ) {

                WC_Admin_Settings::add_error( __( 'Failed to communicate with the RC API. Please check your connection and try again.', 'relais-colis-woocommerce' ) );

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_b2c_config_data();

                return '';
            }

            // Display response
            if ( $wp_rc_configuration->validate() ) {

                // Get returned activation key
                $activation_key = $wp_rc_configuration->get_activation_key();

                // Activation key must be the same
                if ( $activation_key !== $value ) {

                    WC_Admin_Settings::add_error( __( 'The activation key you entered is invalid. Please check and try again.', 'relais-colis-woocommerce' ) );

                    // Reset B2C configuration
                    WC_RC_Shipping_Config_Manager::instance()->delete_b2c_config_data();

                    return '';
                }

                // All is right!

                // Update config
                WC_RC_Shipping_Config_Manager::instance()->update_b2c_config_data( $wp_rc_configuration );

                return $value;

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );

                WC_Admin_Settings::add_error( __( 'The RC API returned an invalid response. Please try again later.', 'relais-colis-woocommerce' ) );

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_b2c_config_data();

                return '';
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::warning( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );

            if ( $wp_relais_colis_api_exception->getCode() == 404 ) {

                WC_Admin_Settings::add_error( __( 'The activation key you entered is invalid. Please check and try again.', 'relais-colis-woocommerce' ) );

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_b2c_config_data();
                return '';

            } else {

                WC_Admin_Settings::add_error( __( 'Failed to communicate with the RC API. Please check your connection and try again.', 'relais-colis-woocommerce' ) );

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_b2c_config_data();

                return '';
            }
        }
    }

    /**
     * Check activation key value
     * @param $value
     * @param $option
     * @param $raw_value
     * @return mixed
     */
    public function filter_woocommerce_admin_settings_sanitize_option_c2c_hash_token( $value, $option, $raw_value ) {

        try {
            $wp_rc_configuration = WP_Relais_Colis_API::instance()->get_b2c_configuration( false );
            $activation_key = $wp_rc_configuration->get_activation_key();
            if (empty($activation_key)) {
                return '';
            }
        } catch (WP_Relais_Colis_API_Exception $e) {
            WP_Log::warning(__METHOD__.' - Error updating C2C infos', [
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ], 'relais-colis-woocommerce');
            return '';
        }
            
        WP_Log::debug( __METHOD__, [ 'value' => $value, 'option' => $option, 'raw_value' => $raw_value ], 'relais-colis-woocommerce' );

        // Call API
        try {
            // If empty key, just delete options
            if ( empty( $value ) ) {

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_c2c_config_data();

                return $value;
            }

            // Update option with given hash token key before API RC request to be able to request it
            update_option( WC_RC_Shipping_Constants::OPTION_C2C_HASH_TOKEN, $value );
            update_option( WC_RC_Shipping_Constants::OPTION_LIVE_TEST_MODE_NAME, $_POST[WC_RC_Shipping_Constants::OPTION_LIVE_TEST_MODE_NAME] );

            // Configuration depends on interaction mode
            $wp_c2c_infos = WP_Relais_Colis_API::instance()->c2c_get_infos( false );

            if ( is_null( $wp_c2c_infos ) ) {

                WC_Admin_Settings::add_error( __( 'Failed to communicate with the RC API. Please check your connection and try again.', 'relais-colis-woocommerce' ) );

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_c2c_config_data();

                return '';
            }

            // Display response
            if ( $wp_c2c_infos->validate() ) {

                // Get returned account status
                $account_status = $wp_c2c_infos->get_account_status();

                // Account status must be valid
                if ( is_null( $account_status ) || ( $account_status !== 'active' ) ) {

                    WC_Admin_Settings::add_error( __( 'The hash token you entered is invalid. Please check and try again.', 'relais-colis-woocommerce' ) );

                    // Reset B2C configuration
                    WC_RC_Shipping_Config_Manager::instance()->delete_c2c_config_data();

                    return '';
                }

                // All is right!

                // Update config
                WC_RC_Shipping_Config_Manager::instance()->update_c2c_config_data( $wp_c2c_infos );

                return $value;

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );

                WC_Admin_Settings::add_error( __( 'The RC API returned an invalid response. Please try again later.', 'relais-colis-woocommerce' ) );

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_c2c_config_data();

                return '';
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::warning( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );

            if ( $wp_relais_colis_api_exception->getCode() == 400 ) {

                WC_Admin_Settings::add_error( __( 'The hash token you entered is invalid. Please check and try again.', 'relais-colis-woocommerce' ) );

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_c2c_config_data();

                return '';
            } else {

                WC_Admin_Settings::add_error( __( 'Failed to communicate with the RC API. Please check your connection and try again.', 'relais-colis-woocommerce' ) );

                // Reset B2C configuration
                WC_RC_Shipping_Config_Manager::instance()->delete_c2c_config_data();

                return '';
            }
        }

    }

    /**
     * Add section to the tab Relais Colis
     * @param $sections
     * @return mixed
     */
    public function filter_woocommerce_get_sections_rc( $sections ) {

        $sections[ self::SECTION_GENERAL ] = __( 'General configuration', 'relais-colis-woocommerce' );
        return $sections;
    }

    /**
     * Add properties to the current section
     * @param $sections
     */
    public function action_woocommerce_settings_rc_general() {

        global $current_section;
        if ( $current_section !== self::SECTION_GENERAL ) return;

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

            // Mettre à jour les informations via l'API avant d'afficher
    if (WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode()) {
        try {
            // Appel à l'API pour récupérer les dernières informations
            $wp_c2c_infos = WP_Relais_Colis_API::instance()->c2c_get_infos( false );

            
            if ($wp_c2c_infos && $wp_c2c_infos->validate()) {
                // Mettre à jour les informations dans la base de données
                WC_RC_Shipping_Config_Manager::instance()->update_c2c_config_data( $wp_c2c_infos );
            }
        } catch (WP_Relais_Colis_API_Exception $e) {
            WP_Log::warning(__METHOD__.' - Error updating C2C infos', [
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ], 'relais-colis-woocommerce');
        }
    }


        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Get the properties
     * @return array
     */
    private function get_settings() {

        // Get interaction mode and RC API validity access
        $is_c2c_interaction_mode = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode();
        $is_rc_api_valid_access = WC_RC_Shipping_Config_Manager::instance()->is_rc_api_valid_access();

        // Build default settings
        $settings = array(
            // Section : Your API Information
            [
                'title' => __( 'Your API Information', 'relais-colis-woocommerce' ),
                'type' => 'title',
                'desc' => __( 'Enter your activation key to synchronize your information.', 'relais-colis-woocommerce' ),
                'id' => 'rc_api_title',
            ],
            // Live/Test Mode
            [
                'title' => __( 'Live/Test Mode', 'relais-colis-woocommerce' ),
                'desc' => __( 'Switch between Live mode and Test mode.', 'relais-colis-woocommerce' ),
                'id' => WC_RC_Shipping_Constants::OPTION_LIVE_TEST_MODE_NAME,
                'default' => 'yes',
                'yes_label' => 'Live',
                'no_label' => 'Test',
                'type' => WC_RC_Shipping_Field_Enable::FIELD_RC_ENABLE_CHECKBOX,
            ],
            // Activation Key
            [
                'title' => __( 'Activation Key', 'relais-colis-woocommerce' ),
                'id' => WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY,
                'type' => 'text',
                'default' => '',
                'desc_tip' => __( 'Your C2C or B2C activation key.', 'relais-colis-woocommerce' ),
            ],
        );
        // Add hash token input if C2C mode and invalid RC API access
        if ( $is_c2c_interaction_mode && !$is_rc_api_valid_access ) {

            $settings[] =
                [
                    'title' => __( 'C2C hash token', 'relais-colis-woocommerce' ),
                    'id' => WC_RC_Shipping_Constants::OPTION_C2C_HASH_TOKEN,
                    'type' => 'text',
                    'default' => '',
                    'desc_tip' => __( 'Your C2C hash token.', 'relais-colis-woocommerce' ),
                ];
        }

        // Add section end
        $settings[] =
            [
                'type' => 'sectionend',
                'id' => 'rc_api_section_end',
            ];
/*
        // Render refresh button only if RC API access is valid
        if ( WC_RC_Shipping_Config_Manager::instance()->is_rc_api_valid_access() ) {

            // Refresh button
            $settings[] = array(
                'type' => WC_RC_Shipping_Field_Refresh_Button::FIELD_RC_REFRESH_BUTTON,
                'id' => WC_RC_Shipping_Field_Refresh_Button::FIELD_RC_REFRESH_BUTTON,
            );

            // Add section end
            $settings[] =
                [
                    'type' => 'sectionend',
                    'id' => 'rc_api_refresh_section_end',
                ];
        }*/

        // When RC API access is valid, then display customer infos
        if ( $is_rc_api_valid_access ) {

            // Add C2C infos if C2C interaction mode
            if ( $is_c2c_interaction_mode ) {

                // Get RC informations
                $rc_c2c_infos = WP_Information_DAO::instance()->get_rc_information();
                WP_Log::debug( __METHOD__, [ '$rc_c2c_infos' => $rc_c2c_infos ], 'relais-colis-woocommerce' );

                // Generate HTML for informations
                $infos_html = '
                    <table class="form-table">
                        <tr><th>'.WC_RC_Shipping_Constants::get_information_title( WC_RC_Shipping_Constants::INFORMATION_BALANCE ).'</th><td>'.esc_html( $rc_c2c_infos[ WC_RC_Shipping_Constants::INFORMATION_BALANCE ] ).' €</td></tr>
                    </table>
                    ';

                $settings[] =
                    [
                        'title' => __( 'Hello', 'relais-colis-woocommerce' ).' '.$rc_c2c_infos[ WC_RC_Shipping_Constants::INFORMATION_FIRSTNAME ].' '.$rc_c2c_infos[ WC_RC_Shipping_Constants::INFORMATION_LASTNAME ],
                        'type' => 'title',
                        'id' => 'rc_c2c_infos_general_title',
                    ];
                $settings[] =
                    [
                        'type' => WC_RC_Shipping_Field_Custom_Html::FIELD_RC_CUSTOM_HTML,
                        'id' => 'rc_c2c_infos_general_html',
                        'html' => $infos_html,
                    ];
                $settings[] =
                    [
                        'type' => 'sectionend',
                        'id' => 'rc_c2c_infos_general_section_end',
                    ];
            }

            // Get RC configuration options
            $rc_configuration_options = WP_Configuration_DAO::instance()->get_rc_configuration_options();
            WP_Log::debug( __METHOD__, [ '$rc_configuration_options' => $rc_configuration_options ], 'relais-colis-woocommerce' );

            if ( !empty( $rc_configuration_options ) ) {

                // Generate HTML for configuration options
                $rc_configuration_options_html = '<table class="form-table">';
                foreach ( $rc_configuration_options as $rc_configuration_option ) {

                    WP_Log::debug( __METHOD__, [ '$rc_configuration_option' => $rc_configuration_option ], 'relais-colis-woocommerce' );

                    $rc_configuration_options_html .= sprintf(
                        '<tr><th>%s</th><td>%s</td></tr>',
                        $rc_configuration_option[ 'name' ],
                        ( ( $rc_configuration_option[ 'active' ] == 1 ) ? __( 'Yes', 'relais-colis-woocommerce' ) : __( 'No', 'relais-colis-woocommerce' ) )
                    );
                }
                $rc_configuration_options_html .= '</table>';

                $specific_desc = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode() ? __( 'The options included in your C2C account.', 'relais-colis-woocommerce' ) : __( 'The options included in your B2C account.', 'relais-colis-woocommerce' );

                $settings[] =
                    [
                        'title' => __( 'Options', 'relais-colis-woocommerce' ),
                        'type' => 'title',
                        'desc' => $specific_desc,
                        'id' => 'rc_b2c_options_title',
                    ];
                $settings[] =
                    [
                        'type' => WC_RC_Shipping_Field_Custom_Html::FIELD_RC_CUSTOM_HTML,
                        'id' => 'rc_config_options_details',
                        'html' => $rc_configuration_options_html,
                    ];
                $settings[] =
                    [
                        'type' => 'sectionend',
                        'id' => 'rc_b2c_options_section_end',
                    ];
            }
        }

        return $settings;
    }
}
