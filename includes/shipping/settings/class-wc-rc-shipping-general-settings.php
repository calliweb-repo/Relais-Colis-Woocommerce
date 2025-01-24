<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Configuration_DAO;
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
        add_filter( 'woocommerce_get_sections_'.WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS, array( $this, 'filter_woocommerce_get_sections_rc' ) );

        // Register settings section
        add_action( 'woocommerce_settings_tabs_'.WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_settings_rc_general' ) );

        // Update settings section
        add_action( 'woocommerce_update_options_'.WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_update_options_rc_general' ) );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

        // Field validation
        add_filter( 'woocommerce_admin_settings_sanitize_option_'.WP_Relais_Colis_API::OPTION_ACTIVATION_KEY_NAME, array( $this, 'filter_woocommerce_admin_settings_sanitize_option_activation_key' ), 10, 3 );

        // Need custom fields
        //WC_RC_Shipping_Field_Enable::instance();
        WC_RC_Shipping_Field_Custom_Html::instance();
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
        wp_enqueue_script( 'rc-api-validation', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/api-validation.js', array( 'jquery' ), '1.0', true );

        // Pass script params to JS
        wp_localize_script('rc-api-validation', 'rc_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rc-api-action'),
            'rc_api_key' => WP_Relais_Colis_API::OPTION_ACTIVATION_KEY_NAME,
        ]);
    }

    /**
     * Check activation key value
     * @param $value
     * @param $option
     * @param $raw_value
     * @return mixed
     */
    public function filter_woocommerce_admin_settings_sanitize_option_activation_key( $value, $option, $raw_value ) {

        WP_Log::notice( __METHOD__, ['value'=>$value, 'option'=>$option, 'raw_value'=>$raw_value], 'relais-colis-woocommerce');

        // Call API
        try {

            // Update option with given activation key before API RC request to validate it
            update_option( WP_Relais_Colis_API::OPTION_ACTIVATION_KEY_NAME, $value );

            $wp_rc_configuration = WP_Relais_Colis_API::instance()->get_b2c_configuration( false );

            if ( is_null( $wp_rc_configuration ) ) {

                WC_Admin_Settings::add_error( __( 'Failed to communicate with the RC API. Please check your connection and try again.', 'relais-colis-woocommerce' ) );
                return '';
            }

            // Display response
            if ( $wp_rc_configuration->validate() ) {

                // Get returned activation key
                $activation_key = $wp_rc_configuration->get_activation_key();

                // Debug log
                $options = $wp_rc_configuration->get_options();
                $modules = $wp_rc_configuration->get_modules();
                $id = $wp_rc_configuration->get_id();
                $ens_name = $wp_rc_configuration->get_ens_name();
                $ens_id = $wp_rc_configuration->get_ens_id();
                $ens_id_light = $wp_rc_configuration->get_ens_id_light();
                $is_active = $wp_rc_configuration->is_active();
                $use_id_ens = $wp_rc_configuration->use_id_ens();
                $livemapping_api = $wp_rc_configuration->get_livemapping_api();
                $livemapping_pid = $wp_rc_configuration->get_livemapping_pid();
                $livemapping_key = $wp_rc_configuration->get_livemapping_key();
                $return_version = $wp_rc_configuration->get_return_version();
                $return_login = $wp_rc_configuration->get_return_login();
                $return_password = $wp_rc_configuration->get_return_pass();
                $folder = $wp_rc_configuration->get_folder();
                $address1 = $wp_rc_configuration->get_address1();
                $postcode = $wp_rc_configuration->get_postcode();
                $city = $wp_rc_configuration->get_city();
                $agency_code = $wp_rc_configuration->get_agency_code();
                $return_site = $wp_rc_configuration->get_return_site();
                $created_at = $wp_rc_configuration->get_created_at();
                $updated_at = $wp_rc_configuration->get_updated_at();
                $updated_by = $wp_rc_configuration->get_updated_by();

                WP_Log::notice(__METHOD__ . ' - Valid response', [
                    'ID' => $id,
                    'Enseigne ID' => $ens_id,
                    'Enseigne ID Light' => $ens_id_light,
                    'Enseigne Nom' => $ens_name,
                    'Clé d\'activation' => $activation_key,
                    'Utilise Enseigne ID' => $use_id_ens ? 'Oui' : 'Non',
                    'Active' => $is_active ? 'Oui' : 'Non',
                    'Options' => $options,
                    'Modules' => $modules,
                    'Adresse Ligne 1' => $address1,
                    'Code Postal' => $postcode,
                    'Ville' => $city,
                    'Live Mapping API' => $livemapping_api,
                    'Live Mapping PID' => $livemapping_pid,
                    'Live Mapping Key' => $livemapping_key,
                    'Dossier' => $folder,
                    'Version de retour' => $return_version,
                    'Login de retour' => $return_login,
                    'Mot de passe de retour' => $return_password,
                    'Code agence' => $agency_code,
                    'Retour site' => $return_site,
                    'Créé le' => $created_at,
                    'Mis à jour le' => $updated_at,
                    'Mis à jour par' => $updated_by,
                ], 'relais-colis-woocommerce');

                // Activation key must be the same
                if ( $activation_key !== $value ) {

                    WC_Admin_Settings::add_error( __( 'The activation key you entered is invalid. Please check and try again.', 'relais-colis-woocommerce' ) );
                    return '';
                }

                // All is right!

                // Insert response in DB
                WP_Configuration_DAO::instance()->insert_rc_get_configuration_data( $wp_rc_configuration );

                return $value;

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );

                WC_Admin_Settings::add_error( __( 'The RC API returned an invalid response. Please try again later.', 'relais-colis-woocommerce' ) );
                return '';
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::warning( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );

            if ( $wp_relais_colis_api_exception->getCode() == 404 ) {

                WC_Admin_Settings::add_error( __( 'The activation key you entered is invalid. Please check and try again.', 'relais-colis-woocommerce' ) );
            } else {

                WC_Admin_Settings::add_error( __( 'Failed to communicate with the RC API. Please check your connection and try again.', 'relais-colis-woocommerce' ) );
            }

            return '';
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

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce');

        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Update properties
     */
    public function action_woocommerce_update_options_rc_general() {

        global $current_section;
        if ( $current_section !== self::SECTION_GENERAL ) return;

        WP_Log::notice( __METHOD__, [], 'relais-colis-woocommerce');

        woocommerce_update_options( $this->get_settings() );
    }

    /**
     * Get the properties
     * @return array
     */
    private function get_settings() {

        // Get RC configuration
        $rc_configuration = WP_Configuration_DAO::instance()->get_rc_configuration();
        WP_Log::notice( __METHOD__, ['$rc_configuration'=>$rc_configuration], 'relais-colis-woocommerce');

        // Generate HTML
        $rc_configuration_html = '';

        if ( !empty( $rc_configuration ) ) {

            $rc_configuration_html = '<table class="rc-configuration-details">';
            foreach ( $rc_configuration as $key => $value ) {

                $rc_configuration_html .= sprintf(
                    '<tr><th>%s</th><td>%s</td></tr>',
                    WP_Configuration_DAO::get_html_title( $key ),
                    is_bool( $value ) ? ( $value ? 'Yes' : 'No' ) : esc_html( $value )
                );
            }
            $rc_configuration_html .= '</table>';
        }

        return [

            // Section : Your API Information
            [
                'title' => __('Your API Information', 'relais-colis-woocommerce'), // Vos informations d'API
                'type' => 'title',
                'desc' => __('Enter your activation key to synchronize your information.', 'relais-colis-woocommerce'), // Entrez votre clé d’activation pour synchroniser vos informations.
                'id' => 'rc_api_title',
            ],
            // Live/Test Mode
            [
                'title' => __('Live/Test Mode', 'relais-colis-woocommerce'), // Mode Live/Test
                'desc' => __('Switch between Live mode (checked) and Test mode (unchecked).', 'relais-colis-woocommerce'), // Basculer entre le mode Live (coché) et Test (décoché).
                'id' => WP_Relais_Colis_API::OPTION_LIVE_TEST_MODE_NAME,
                'default' => 'no',
                'type' => 'checkbox',
            ],
            // Activation Key
            [
                'title' => __('Activation Key', 'relais-colis-woocommerce'), // Clé d’activation
                'id' => WP_Relais_Colis_API::OPTION_ACTIVATION_KEY_NAME,
                'type' => 'text',
                'default' => '',
                'desc_tip' => __('Your C2C or B2C activation key.', 'relais-colis-woocommerce'), // Votre clé d’activation C2C ou B2C.
            ],
            // Extract and Refresh Buttons
            [
                'type' => 'rc_action_buttons', // Boutons Extraire et rafraîchir
                'id' => 'rc_action_buttons',
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_api_section_end',
            ],

            // Section : B2C Options
            [
                'title' => __('B2C Options', 'relais-colis-woocommerce'), // Options B2C
                'type' => 'title',
                'desc' => __('Configure the options included in your B2C account.', 'relais-colis-woocommerce'), // Configurez les options incluses dans votre compte B2C.
                'id' => 'rc_b2c_title',
            ],
            [
                'type' => WC_RC_Shipping_Field_Custom_Html::FIELD_RC_CUSTOM_HTML,
                'id'   => 'rc_config_details',
                'html' => $rc_configuration_html,
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_b2c_section_end',
            ],

            // Section : Relais Colis Settings
            [
                'title' => __('Relais Colis Settings', 'relais-colis-woocommerce'), // Paramètres Relais Colis
                'type' => 'title',
                'id' => 'rc_settings_title',
            ],
            // Weight Units
            [
                'title' => __('Weight Units', 'relais-colis-woocommerce'), // Unités de poids
                'desc' => __('Select the weight unit to use.', 'relais-colis-woocommerce'), // Sélectionnez l'unité de poids à utiliser.
                'id' => 'rc_weight_unit',
                'type' => 'select',
                'options' => [
                    'g' => __('Grams (g)', 'relais-colis-woocommerce'), // Grammes (g)
                    'dg' => __('Decigrams (dg)', 'relais-colis-woocommerce'), // Décigrammes (dg)
                    'kg' => __('Kilograms (kg)', 'relais-colis-woocommerce'), // Kilogrammes (kg)
                ],
                'default' => 'g',
                'class' => 'wc-enhanced-select',
                'desc_tip' => true,
            ],
            // Length Units
            [
                'title' => __('Length Units', 'relais-colis-woocommerce'), // Unités de longueur
                'desc' => __('Select the length unit to use.', 'relais-colis-woocommerce'), // Sélectionnez l'unité de longueur à utiliser.
                'id' => 'rc_length_unit',
                'type' => 'select',
                'options' => [
                    'mm' => __('Millimeters (mm)', 'relais-colis-woocommerce'), // Millimètres (mm)
                    'cm' => __('Centimeters (cm)', 'relais-colis-woocommerce'), // Centimètres (cm)
                    'dm' => __('Decimeters (dm)', 'relais-colis-woocommerce'), // Décimètres (dm)
                    'm' => __('Meters (m)', 'relais-colis-woocommerce'), // Mètres (m)
                    'in' => __('Inches (in)', 'relais-colis-woocommerce'), // Pouces (in)
                ],
                'default' => 'cm',
                'class' => 'wc-enhanced-select',
                'desc_tip' => true,
            ],
            // Label Format
            [
                'title' => __('Label Format', 'relais-colis-woocommerce'), // Format d’étiquette
                'desc' => __('Choose the label format to print.', 'relais-colis-woocommerce'), // Choisissez le format d’étiquette à imprimer.
                'id' => 'rc_label_format',
                'type' => 'select',
                'options' => [
                    'A4' => __('A4 Format', 'relais-colis-woocommerce'), // Format A4
                    'A5' => __('A5 Format', 'relais-colis-woocommerce'), // Format A5
                    'carre' => __('Square Format', 'relais-colis-woocommerce'), // Format Carré
                    '10x15' => __('10x15 Format', 'relais-colis-woocommerce'), // Format 10x15
                ],
                'default' => 'A4',
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_settings_section_end',
            ],
        ];
    }
}
