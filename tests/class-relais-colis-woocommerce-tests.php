<?php

namespace RelaisColisWoocommerce\Tests;

use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Generate;
use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Home_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_Bulk_Generate;
use RelaisColisWoocommerce\RCAPI\WP_RC_C2C_Get_Packages_Price;
use RelaisColisWoocommerce\RCAPI\WP_RC_C2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_Place_Return_V2;
use RelaisColisWoocommerce\RCAPI\WP_RC_Transport_Generate;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * Relais Colis Woocommerce main class.
 *
 * @since 1.0.0
 */
class Relais_Colis_Woocommerce_Tests {

    // Use Trait Singleton
    use Singleton;

    private $active = true;

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        if ( !$this->active ) return;

        ///////////////
        // TESTS WP_Relais_Colis_API - Request get_b2c_configuration
        ///////////////
//        $this->test_wp_relais_colis_api_get_b2c_configuration_success();
//        $this->test_wp_relais_colis_api_get_b2c_configuration_error();
//        $this->test_wp_relais_colis_api_get_c2c_configuration_success();
//        $this->test_wp_relais_colis_api_get_c2c_configuration_error();
//        $this->test_wp_relais_colis_api_b2c_relay_place_advertisement_success();
//        $this->test_wp_relais_colis_api_b2c_relay_place_advertisement_error();
      //  $this->test_wp_relais_colis_api_b2c_home_place_advertisement_success();
//        $this->test_wp_relais_colis_api_b2c_home_place_advertisement_error();
//        $this->test_wp_relais_colis_api_c2c_relay_place_advertisement_success();
//        $this->test_wp_relais_colis_api_c2c_relay_place_advertisement_error();
      //   $this->test_wp_relais_colis_api_b2c_generate_success();
//        $this->test_wp_relais_colis_api_c2c_generate_success();
//        $this->test_wp_relais_colis_api_b2c_generate_error();
//        $this->test_wp_relais_colis_api_c2c_generate_error();
//        $this->test_wp_relais_colis_api_bulk_generate_success();
//        $this->test_wp_relais_colis_api_b2c_place_return_success();
//        $this->test_wp_relais_colis_api_b2c_place_return_v3_success();
//        $this->test_wp_relais_colis_api_b2c_place_return_v3_error();
 //       $this->test_wp_relais_colis_api_c2c_get_infos_success();
//        $this->test_wp_relais_colis_api_c2c_get_infos_error();
//        $this->test_wp_relais_colis_api_c2c_get_packages_price_success();
//        $this->test_wp_relais_colis_api_c2c_get_packages_price_error();
//        $this->test_wp_relais_colis_api_transport_generate_success(); // TODO Implémentation à terminer
//        $this->test_wp_relais_colis_api_c2c_get_packages_status_success(); // TODO Implémentation à terminer
//        $this->test_wp_relais_colis_api_c2c_get_packages_status_error(); // TODO Implémentation à terminer


        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request get_b2c_configuration - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_get_b2c_configuration_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
//        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        //update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            $wp_rc_configuration = WP_Relais_Colis_API::instance()->get_b2c_configuration( false );

            if ( is_null( $wp_rc_configuration ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $wp_rc_configuration->validate() ) {

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
                $activation_key = $wp_rc_configuration->get_activation_key();
                $created_at = $wp_rc_configuration->get_created_at();
                $updated_at = $wp_rc_configuration->get_updated_at();
                $updated_by = $wp_rc_configuration->get_updated_by();

                WP_Log::debug(__METHOD__ . ' - Valid response', [
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

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request get_b2c_configuration - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_get_b2c_configuration_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        // ERROR in activationKey
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v-----' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            $wp_rc_configuration = WP_Relais_Colis_API::instance()->get_b2c_configuration( false );

            if ( is_null( $wp_rc_configuration ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $wp_rc_configuration->validate() ) {

                WP_Log::debug(__METHOD__ . ' - Valid response', [], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request get_c2c_configuration - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_get_c2c_configuration_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            $wp_rc_configuration = WP_Relais_Colis_API::instance()->get_c2c_configuration( false );

            if ( is_null( $wp_rc_configuration ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $wp_rc_configuration->validate() ) {

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
                $activation_key = $wp_rc_configuration->get_activation_key();
                $created_at = $wp_rc_configuration->get_created_at();
                $updated_at = $wp_rc_configuration->get_updated_at();
                $updated_by = $wp_rc_configuration->get_updated_by();

                WP_Log::debug(__METHOD__ . ' - Valid response', [
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

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request get_c2c_configuration - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_get_c2c_configuration_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        // ERROR in activationKey
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v-----' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            $wp_rc_configuration = WP_Relais_Colis_API::instance()->get_c2c_configuration( false );

            if ( is_null( $wp_rc_configuration ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $wp_rc_configuration->validate() ) {

                WP_Log::debug(__METHOD__ . ' - Valid response', [], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request b2c_relay_place_advertisement - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_b2c_relay_place_advertisement_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_B2C_Relay_Place_Advertisement::AGENCY_CODE => 'C3',            // Code de l'agence
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_ID => '99',           // ID du client
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_FULLNAME => 'Tom Hatte',     // Nom complet du client
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_EMAIL => 'tom.hatte@yopmail.com',        // Email du client
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_PHONE => '0412356789',        // Numéro de téléphone du client
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_MOBILE => '0606060606',       // Numéro de mobile du client
                WP_RC_B2C_Relay_Place_Advertisement::PSEUDO_RVC => '01309',            // Pseudo RVC
                WP_RC_B2C_Relay_Place_Advertisement::ORDER_REFERENCE => '99',       // Référence de commande
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_1 => "12 rue de l'épinoy",    // Adresse de livraison ligne 1
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_2 => '',    // Adresse de livraison ligne 2 (facultatif)
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_POSTCODE => '59175',     // Code postal de livraison
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_CITY => 'Templatemars',         // Ville de livraison
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_COUNTRY_CODE => 'FR', // Code pays de livraison
                WP_RC_B2C_Relay_Place_Advertisement::XEETT => 'I4040',                 // ID spécifique Xeett
            );

            $b2c_relay_place_advertisement = WP_Relais_Colis_API::instance()->b2c_relay_place_advertisement( $dynamic_params, false );

            if ( is_null( $b2c_relay_place_advertisement ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $b2c_relay_place_advertisement->validate() ) {

                $entry = $b2c_relay_place_advertisement->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request b2c_relay_place_advertisement - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_b2c_relay_place_advertisement_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
//        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_B2C_Relay_Place_Advertisement::AGENCY_CODE => 'C3',            // Code de l'agence
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_ID => '99',           // ID du client
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_FULLNAME => 'Tom Hatte',     // Nom complet du client
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_EMAIL => 'tom.hatte@yopmail.com',        // Email du client
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_PHONE => '0412356789',        // Numéro de téléphone du client
                WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_MOBILE => '0606060606',       // Numéro de mobile du client
                // ERROR suffix with ---
                // Waiting for response : [response_raw] => {"title":"An Error occurred","status":400,"detail":"Object(App\\Entity\\PackageDetail).pseudoRvc:\n    Le pseudo RVC ne peut \u00eatre plus longue que 5 caract\u00e8res (code d94b19cc-114f-4f44-9cc4-4138e80a87b9)\n"}
                WP_RC_B2C_Relay_Place_Advertisement::PSEUDO_RVC => '01309---',            // Pseudo RVC
                WP_RC_B2C_Relay_Place_Advertisement::ORDER_REFERENCE => '99',       // Référence de commande
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_1 => "12 rue de l'épinoy",    // Adresse de livraison ligne 1
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_2 => '',    // Adresse de livraison ligne 2 (facultatif)
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_POSTCODE => '59175',     // Code postal de livraison
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_CITY => 'Templatemars',         // Ville de livraison
                WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_COUNTRY_CODE => 'FR', // Code pays de livraison
                WP_RC_B2C_Relay_Place_Advertisement::XEETT => 'I4040',                 // ID spécifique Xeett
            );

            $b2c_relay_place_advertisement = WP_Relais_Colis_API::instance()->b2c_relay_place_advertisement( $dynamic_params, false );

            if ( is_null( $b2c_relay_place_advertisement ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $b2c_relay_place_advertisement->validate() ) {

                $entry = $b2c_relay_place_advertisement->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request b2c_home_place_advertisement - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_b2c_home_place_advertisement_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_B2C_Home_Place_Advertisement::AGENCY_CODE => 'C3',            // Code de l'agence
                //WP_RC_B2C_Home_Place_Advertisement::AGENCY_CODE => 'P9',            // Code de l'agence
                //WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_ID => '99',           // ID du client
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_ID => '3',           // ID du client
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_FULLNAME => 'Tom Hatte',     // Nom complet du client
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_EMAIL => 'tom.hatte@yopmail.com',        // Email du client
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_PHONE => '0412356789',        // Numéro de téléphone du client
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_MOBILE => '0606060606',       // Numéro de mobile du client
                //WP_RC_B2C_Home_Place_Advertisement::ORDER_REFERENCE => '99',       // Référence de commande
                WP_RC_B2C_Home_Place_Advertisement::ORDER_REFERENCE => '206',       // Référence de commande
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_ADDRESS_1 => "12 rue de l'épinoy",    // Adresse de livraison ligne 1
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_ADDRESS_2 => '',    // Adresse de livraison ligne 2 (facultatif)
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_POSTCODE => '59175',     // Code postal de livraison
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_CITY => 'Templatemars',         // Ville de livraison
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_COUNTRY_CODE => 'FR', // Code pays de livraison
                WP_RC_B2C_Home_Place_Advertisement::SHIPPMENT_WEIGHT => 1000, // Code pays de livraison
                WP_RC_B2C_Home_Place_Advertisement::WEIGHT => 1000, // Code pays de livraison
            );

            $b2c_home_place_advertisement = WP_Relais_Colis_API::instance()->b2c_home_place_advertisement( $dynamic_params, false );

            if ( is_null( $b2c_home_place_advertisement ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $b2c_home_place_advertisement->validate() ) {

                $entry = $b2c_home_place_advertisement->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request b2c_home_place_advertisement - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_b2c_home_place_advertisement_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                // ERROR suffix with ---
                WP_RC_B2C_Home_Place_Advertisement::AGENCY_CODE => 'C3--------',            // Code de l'agence
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_ID => '99',           // ID du client
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_FULLNAME => 'Tom Hatte',     // Nom complet du client
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_EMAIL => 'tom.hatte@yopmail.com',        // Email du client
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_PHONE => '0412356789',        // Numéro de téléphone du client
                WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_MOBILE => '0606060606',       // Numéro de mobile du client
                WP_RC_B2C_Home_Place_Advertisement::ORDER_REFERENCE => '99',       // Référence de commande
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_ADDRESS_1 => "12 rue de l'épinoy",    // Adresse de livraison ligne 1
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_ADDRESS_2 => '',    // Adresse de livraison ligne 2 (facultatif)
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_POSTCODE => '59175',     // Code postal de livraison
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_CITY => 'Templatemars',         // Ville de livraison
                WP_RC_B2C_Home_Place_Advertisement::SHIPPING_COUNTRY_CODE => 'FR', // Code pays de livraison
            );

            $b2c_home_place_advertisement = WP_Relais_Colis_API::instance()->b2c_home_place_advertisement( $dynamic_params, false );

            if ( is_null( $b2c_home_place_advertisement ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $b2c_home_place_advertisement->validate() ) {

                $entry = $b2c_home_place_advertisement->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request c2c_relay_place_advertisement - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_relay_place_advertisement_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v
//        update_option( $options_suffix_param.'_C2C_hashToken', '4850e2012b4a77431774cd928e6ea944e98628a6242f' ); // preprod - C2C - 4850e2012b4a77431774cd928e6ea944e98628a6242f
        update_option( $options_suffix_param.'_C2C_hashToken', 'bef3f8fa7b689bb89105394ad43940c133724b0d924e' ); // prod - C2C - bef3f8fa7b689bb89105394ad43940c133724b0d924e

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_C2C_Relay_Place_Advertisement::AGENCY_CODE => 'C3',            // Code de l'agence
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_ID => '99',           // ID du client
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_FULLNAME => 'Tom Hatte',     // Nom complet du client
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_EMAIL => 'tom.hatte@yopmail.com',        // Email du client
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_PHONE => '0412356789',        // Numéro de téléphone du client
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_MOBILE => '0606060606',       // Numéro de mobile du client
                WP_RC_C2C_Relay_Place_Advertisement::ORDER_REFERENCE => '99',       // Référence de commande
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_1 => "12 rue de l'épinoy",    // Adresse de livraison ligne 1
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_2 => '',    // Adresse de livraison ligne 2 (facultatif)
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_POSTCODE => '59175',     // Code postal de livraison
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_CITY => 'Templatemars',         // Ville de livraison
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_COUNTRY_CODE => 'FR', // Code pays de livraison
                WP_RC_C2C_Relay_Place_Advertisement::XEETT => 'I4040',                 // ID spécifique Xeett
                WP_RC_C2C_Relay_Place_Advertisement::ADDRESS1_EXPEDITEUR => "20 rue de l'épinoy",    // Adresse expediteur ligne 1
                WP_RC_C2C_Relay_Place_Advertisement::ADDRESS2_EXPEDITEUR => '',    // Adresse expediteur ligne 2
                WP_RC_C2C_Relay_Place_Advertisement::EMAIL_EXPEDITEUR => 'jf@yopmail.com',       // E-mail expediteur
                WP_RC_C2C_Relay_Place_Advertisement::CITY_EXPEDITEUR => 'Templemars',        // Ville de l"expediteur
                WP_RC_C2C_Relay_Place_Advertisement::NAME_EXPEDITEUR => 'Quadra Informatique',        // Nom de l'expediteur
                WP_RC_C2C_Relay_Place_Advertisement::PHONE_EXPEDITEUR => '04987654321',       // Téléphone de l'expediteur
                WP_RC_C2C_Relay_Place_Advertisement::POSTCODE_EXPEDITEUR => '59175',   // Code postal de l'expediteur
            );

            $c2c_relay_place_advertisement = WP_Relais_Colis_API::instance()->c2c_relay_place_advertisement( $dynamic_params, false );

            if ( is_null( $c2c_relay_place_advertisement ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $c2c_relay_place_advertisement->validate() ) {

                $entry = $c2c_relay_place_advertisement->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request c2c_relay_place_advertisement - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_relay_place_advertisement_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v
//        update_option( $options_suffix_param.'_C2C_hashToken', '4850e2012b4a77431774cd928e6ea944e98628a6242f' ); // preprod - C2C - 4850e2012b4a77431774cd928e6ea944e98628a6242f
        update_option( $options_suffix_param.'_C2C_hashToken', 'bef3f8fa7b689bb89105394ad43940c133724b0d924e' ); // prod - C2C - bef3f8fa7b689bb89105394ad43940c133724b0d924e

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_C2C_Relay_Place_Advertisement::AGENCY_CODE => 'C3',            // Code de l'agence
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_ID => '99',           // ID du client
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_FULLNAME => 'Tom Hatte',     // Nom complet du client
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_EMAIL => 'tom.hatte@yopmail.com',        // Email du client
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_PHONE => '0412356789',        // Numéro de téléphone du client
                WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_MOBILE => '0606060606',       // Numéro de mobile du client
                // ERROR suffix with ---
                WP_RC_C2C_Relay_Place_Advertisement::ORDER_REFERENCE => '99--------',       // Référence de commande
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_1 => "12 rue de l'épinoy",    // Adresse de livraison ligne 1
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_2 => '',    // Adresse de livraison ligne 2 (facultatif)
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_POSTCODE => '59175',     // Code postal de livraison
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_CITY => 'Templatemars',         // Ville de livraison
                WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_COUNTRY_CODE => 'FR', // Code pays de livraison
                // ERROR MISSING
                // ERROR suffix with ---
                WP_RC_C2C_Relay_Place_Advertisement::XEETT => 'I4040--------',                 // ID spécifique Xeett
                WP_RC_C2C_Relay_Place_Advertisement::ADDRESS1_EXPEDITEUR => "20 rue de l'épinoy",    // Adresse expediteur ligne 1
                WP_RC_C2C_Relay_Place_Advertisement::ADDRESS2_EXPEDITEUR => '',    // Adresse expediteur ligne 2
                WP_RC_C2C_Relay_Place_Advertisement::EMAIL_EXPEDITEUR => 'jf@yopmail.com',       // E-mail expediteur
                WP_RC_C2C_Relay_Place_Advertisement::CITY_EXPEDITEUR => 'Templemars',        // Ville de l"expediteur
                WP_RC_C2C_Relay_Place_Advertisement::NAME_EXPEDITEUR => 'Quadra Informatique',        // Nom de l'expediteur
                WP_RC_C2C_Relay_Place_Advertisement::PHONE_EXPEDITEUR => '04987654321',       // Téléphone de l'expediteur
                WP_RC_C2C_Relay_Place_Advertisement::POSTCODE_EXPEDITEUR => '59175',   // Code postal de l'expediteur
            );

            $c2c_relay_place_advertisement = WP_Relais_Colis_API::instance()->c2c_relay_place_advertisement( $dynamic_params, false );

            if ( is_null( $c2c_relay_place_advertisement ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $c2c_relay_place_advertisement->validate() ) {

                $entry = $c2c_relay_place_advertisement->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request b2c_generate - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_b2c_generate_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_B2C_Generate::FORMAT => WP_RC_B2C_Generate::FORMAT_A5,
                WP_RC_B2C_Generate::ETIQUETTE1 => '4H013000031701', // Result of b2c_relay_place_advertisement
            );

            $b2c_generate = WP_Relais_Colis_API::instance()->b2c_generate( $dynamic_params, false );

            if ( is_null( $b2c_generate ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            WP_Log::debug( __METHOD__.' - Response is a PDF delivery label', ['URL'=>$b2c_generate->get_pdf_delivery_label()], 'relais-colis-woocommerce' );

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request b2c_generate - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_b2c_generate_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
        // ERROR 500 Internal Server Error
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v-------' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_B2C_Generate::FORMAT => WP_RC_B2C_Generate::FORMAT_A5,
                // ERROR 500 Internal Server Error
                WP_RC_B2C_Generate::ETIQUETTE1 => '4H013000239501--', // Result of b2c_relay_place_advertisement
                //WP_RC_B2C_Generate::ETIQUETTE1 => '4H013000239501', // Result of b2c_relay_place_advertisement
            );

            $b2c_generate = WP_Relais_Colis_API::instance()->b2c_generate( $dynamic_params, false );

            if ( is_null( $b2c_generate ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            WP_Log::debug( __METHOD__.' - Response is a PDF delivery label', ['URL'=>$b2c_generate->get_pdf_delivery_label()], 'relais-colis-woocommerce' );

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request c2c_generate - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_generate_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_B2C_Generate::FORMAT => WP_RC_B2C_Generate::FORMAT_A4,
                WP_RC_B2C_Generate::ETIQUETTE1 => '4H013000007501', // Result of c2c_relay_place_advertisement
            );

            $c2c_generate = WP_Relais_Colis_API::instance()->c2c_generate( $dynamic_params, false );

            if ( is_null( $c2c_generate ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            WP_Log::debug( __METHOD__.' - Response is a PDF delivery label', ['URL'=>$c2c_generate->get_pdf_delivery_label()], 'relais-colis-woocommerce' );

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request c2c_generate - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_generate_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
        // ERROR 500 Internal Server Error
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v------' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_B2C_Generate::FORMAT => WP_RC_B2C_Generate::FORMAT_A4,
                // ERROR 500 Internal Server Error
                //WP_RC_B2C_Generate::ETIQUETTE1 => '4H013000007501--', // Result of c2c_relay_place_advertisement
                WP_RC_B2C_Generate::ETIQUETTE1 => '4H013000007501', // Result of c2c_relay_place_advertisement
            );

            $c2c_generate = WP_Relais_Colis_API::instance()->c2c_generate( $dynamic_params, false );

            if ( is_null( $c2c_generate ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            WP_Log::debug( __METHOD__.' - Response is a PDF delivery label', ['URL'=>$c2c_generate->get_pdf_delivery_label()], 'relais-colis-woocommerce' );

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request bulk_generate - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_bulk_generate_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_Bulk_Generate::FORMAT => WP_RC_Bulk_Generate::FORMAT_A5,
                WP_RC_Bulk_Generate::ETIQUETTE.'1' => '4H013000006401', // Result of c2c_relay_place_advertisement
                WP_RC_Bulk_Generate::ETIQUETTE.'2' => '4H013000006901', // Result of b2c_relay_place_advertisement
            );

            $bulk_generate = WP_Relais_Colis_API::instance()->bulk_generate( $dynamic_params, false );

            if ( is_null( $bulk_generate ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            WP_Log::debug( __METHOD__.' - Response is a PDF delivery label', ['URL'=>$bulk_generate->get_pdf_delivery_label()], 'relais-colis-woocommerce' );

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request b2c_place_return - SUCCESS / ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_b2c_place_return_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_Place_Return_V2::REQUESTS => array(
                    array(
                        WP_RC_Place_Return_V2::ORDER_ID => '99',
                        WP_RC_Place_Return_V2::CUSTOMER_ID => '99',
                        WP_RC_Place_Return_V2::CUSTOMER_FULLNAME => 'Tom Hatte',
                        WP_RC_Place_Return_V2::XEETT => 'I4040',
                        WP_RC_Place_Return_V2::XEETT_NAME => 'La Poste',
                        WP_RC_Place_Return_V2::CUSTOMER_PHONE => '0412356789',
                        WP_RC_Place_Return_V2::CUSTOMER_MOBILE => '0606060606',
                        WP_RC_Place_Return_V2::REFERENCE => 'TE12ST34',
                        WP_RC_Place_Return_V2::CUSTOMER_COMPANY => 'WE+',
                        WP_RC_Place_Return_V2::CUSTOMER_ADDRESS1 => "12 rue de l'épinoy",
                        WP_RC_Place_Return_V2::CUSTOMER_ADDRESS2 => "",
                        WP_RC_Place_Return_V2::CUSTOMER_POSTCODE => "59175",
                        WP_RC_Place_Return_V2::CUSTOMER_CITY => "Templatemars",
                        WP_RC_Place_Return_V2::CUSTOMER_COUNTRY => "France",
                        WP_RC_Place_Return_V2::PRESTATIONS => "1",
                    ),
                ),
            );

            $b2c_place_return = WP_Relais_Colis_API::instance()->b2c_place_return( $dynamic_params, false );

            if ( is_null( $b2c_place_return ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $b2c_place_return->validate() ) {

                // Get enseigne response part
                $enseigne_options = $b2c_place_return->get_options();
                $enseigne_modules = $b2c_place_return->get_modules();
                $enseigne_id = $b2c_place_return->get_id();
                $enseigne_ens_name = $b2c_place_return->get_ens_name();
                $enseigne_ens_id = $b2c_place_return->get_ens_id();
                $enseigne_ens_id_light = $b2c_place_return->get_ens_id_light();
                $enseigne_is_active = $b2c_place_return->is_active();
                $enseigne_use_id_ens = $b2c_place_return->use_id_ens();
                $enseigne_livemapping_api = $b2c_place_return->get_livemapping_api();
                $enseigne_livemapping_pid = $b2c_place_return->get_livemapping_pid();
                $enseigne_livemapping_key = $b2c_place_return->get_livemapping_key();
                $enseigne_return_version = $b2c_place_return->get_return_version();
                $enseigne_return_login = $b2c_place_return->get_return_login();
                $enseigne_return_password = $b2c_place_return->get_return_pass();
                $enseigne_folder = $b2c_place_return->get_folder();
                $enseigne_address1 = $b2c_place_return->get_address1();
                $enseigne_postcode = $b2c_place_return->get_postcode();
                $enseigne_city = $b2c_place_return->get_city();
                $enseigne_agency_code = $b2c_place_return->get_agency_code();
                $enseigne_return_site = $b2c_place_return->get_return_site();
                $enseigne_activation_key = $b2c_place_return->get_activation_key();
                $enseigne_created_at = $b2c_place_return->get_created_at();
                $enseigne_updated_at = $b2c_place_return->get_updated_at();
                $enseigne_updated_by = $b2c_place_return->get_updated_by();

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Enseigne - ID' => $enseigne_id,
                    'Enseigne - Enseigne ID' => $enseigne_ens_id,
                    'Enseigne - Enseigne ID Light' => $enseigne_ens_id_light,
                    'Enseigne - Enseigne Nom' => $enseigne_ens_name,
                    'Enseigne - Clé d\'activation' => $enseigne_activation_key,
                    'Enseigne - Utilise Enseigne ID' => $enseigne_use_id_ens ? 'Oui' : 'Non',
                    'Enseigne - Active' => $enseigne_is_active ? 'Oui' : 'Non',
                    'Enseigne - Options' => $enseigne_options,
                    'Enseigne - Modules' => $enseigne_modules,
                    'Enseigne - Adresse Ligne 1' => $enseigne_address1,
                    'Enseigne - Code Postal' => $enseigne_postcode,
                    'Enseigne - Ville' => $enseigne_city,
                    'Enseigne - Live Mapping API' => $enseigne_livemapping_api,
                    'Enseigne - Live Mapping PID' => $enseigne_livemapping_pid,
                    'Enseigne - Live Mapping Key' => $enseigne_livemapping_key,
                    'Enseigne - Dossier' => $enseigne_folder,
                    'Enseigne - Version de retour' => $enseigne_return_version,
                    'Enseigne - Login de retour' => $enseigne_return_login,
                    'Enseigne - Mot de passe de retour' => $enseigne_return_password,
                    'Enseigne - Code agence' => $enseigne_agency_code,
                    'Enseigne - Retour site' => $enseigne_return_site,
                    'Enseigne - Créé le' => $enseigne_created_at,
                    'Enseigne - Mis à jour le' => $enseigne_updated_at,
                    'Enseigne - Mis à jour par' => $enseigne_updated_by,
                ], 'relais-colis-woocommerce');

                // Get additional response details
                $entry_id = $b2c_place_return->get_entry_id();
                $order_id = $b2c_place_return->get_order_id();
                $additional_enseigne_id = $b2c_place_return->get_enseigne_id();
                $customer_id = $b2c_place_return->get_customer_id();
                $customer_fullname = $b2c_place_return->get_customer_fullname();
                $xeett = $b2c_place_return->get_xeett();
                $xeett_name = $b2c_place_return->get_xeett_name();
                $customer_phone = $b2c_place_return->get_customer_phone();
                $customer_mobile = $b2c_place_return->get_customer_mobile();
                $customer_company = $b2c_place_return->get_customer_company();
                $customer_address1 = $b2c_place_return->get_customer_address1();
                $customer_address2 = $b2c_place_return->get_customer_address2();
                $customer_postcode = $b2c_place_return->get_customer_postcode();
                $customer_city = $b2c_place_return->get_customer_city();
                $customer_country = $b2c_place_return->get_customer_country();
                $reference = $b2c_place_return->get_reference();
                $response_status = $b2c_place_return->get_response_status();
                $error_type = $b2c_place_return->get_error_type();
                $error_description = $b2c_place_return->get_error_description();
                $return_number = $b2c_place_return->get_return_number();
                $number_cab = $b2c_place_return->get_number_cab();
                $limit_date = $b2c_place_return->get_limit_date();
                $image_url = $b2c_place_return->get_image_url();
                $bordereau_smart_url = $b2c_place_return->get_bordereau_smart_url();
                $created_at = $b2c_place_return->get_created_at();
                $token = $b2c_place_return->get_token();

                WP_Log::debug(__METHOD__ . ' - Additional response details', [
                    'Entry ID' => $entry_id,
                    'Order ID' => $order_id,
                    'Enseigne ID' => $additional_enseigne_id,
                    'Customer ID' => $customer_id,
                    'Customer Full Name' => $customer_fullname,
                    'Xeett' => $xeett,
                    'Xeett Name' => $xeett_name,
                    'Customer Phone' => $customer_phone,
                    'Customer Mobile' => $customer_mobile,
                    'Customer Company' => $customer_company,
                    'Customer Address Line 1' => $customer_address1,
                    'Customer Address Line 2' => $customer_address2,
                    'Customer Postcode' => $customer_postcode,
                    'Customer City' => $customer_city,
                    'Customer Country' => $customer_country,
                    'Reference' => $reference,
                    'Response Status' => $response_status,
                    'Error Type' => $error_type,
                    'Error Description' => $error_description,
                    'Return Number' => $return_number,
                    'Cab Number' => $number_cab,
                    'Limit Date' => $limit_date,
                    'Image URL' => $image_url,
                    'Bordereau Smart URL' => $bordereau_smart_url,
                    'Created At' => $created_at,
                    'Token' => $token,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request b2c_place_return_v3 - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_b2c_place_return_v3_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_Place_Return_V2::REQUESTS => array(
                    array(
                        WP_RC_Place_Return_V2::ORDER_ID => '99',
                        WP_RC_Place_Return_V2::CUSTOMER_ID => '99',
                        WP_RC_Place_Return_V2::CUSTOMER_FULLNAME => 'Tom Hatte',
                        WP_RC_Place_Return_V2::XEETT => 'I4040',
                        WP_RC_Place_Return_V2::XEETT_NAME => 'La Poste',
                        WP_RC_Place_Return_V2::CUSTOMER_PHONE => '0412356789',
                        WP_RC_Place_Return_V2::CUSTOMER_MOBILE => '0606060606',
                        WP_RC_Place_Return_V2::REFERENCE => 'TE12ST34',
                        WP_RC_Place_Return_V2::CUSTOMER_COMPANY => '',
                        WP_RC_Place_Return_V2::CUSTOMER_ADDRESS1 => "12 rue de l'épinoy",
                        WP_RC_Place_Return_V2::CUSTOMER_ADDRESS2 => "",
                        WP_RC_Place_Return_V2::CUSTOMER_POSTCODE => "59175",
                        WP_RC_Place_Return_V2::CUSTOMER_CITY => "Templatemars",
                        WP_RC_Place_Return_V2::CUSTOMER_COUNTRY => "France",
                        WP_RC_Place_Return_V2::PRESTATIONS => "1",
                    ),
                ),
            );

            $b2c_place_return = WP_Relais_Colis_API::instance()->b2c_place_return_v3( $dynamic_params, false );

            if ( is_null( $b2c_place_return ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $b2c_place_return->validate() ) {

                // Get enseigne response part
                $enseigne_options = $b2c_place_return->get_options();
                $enseigne_modules = $b2c_place_return->get_modules();
                $enseigne_id = $b2c_place_return->get_id();
                $enseigne_ens_name = $b2c_place_return->get_ens_name();
                $enseigne_ens_id = $b2c_place_return->get_ens_id();
                $enseigne_ens_id_light = $b2c_place_return->get_ens_id_light();
                $enseigne_is_active = $b2c_place_return->is_active();
                $enseigne_use_id_ens = $b2c_place_return->use_id_ens();
                $enseigne_livemapping_api = $b2c_place_return->get_livemapping_api();
                $enseigne_livemapping_pid = $b2c_place_return->get_livemapping_pid();
                $enseigne_livemapping_key = $b2c_place_return->get_livemapping_key();
                $enseigne_return_version = $b2c_place_return->get_return_version();
                $enseigne_return_login = $b2c_place_return->get_return_login();
                $enseigne_return_password = $b2c_place_return->get_return_pass();
                $enseigne_folder = $b2c_place_return->get_folder();
                $enseigne_address1 = $b2c_place_return->get_address1();
                $enseigne_postcode = $b2c_place_return->get_postcode();
                $enseigne_city = $b2c_place_return->get_city();
                $enseigne_agency_code = $b2c_place_return->get_agency_code();
                $enseigne_return_site = $b2c_place_return->get_return_site();
                $enseigne_activation_key = $b2c_place_return->get_activation_key();
                $enseigne_created_at = $b2c_place_return->get_created_at();
                $enseigne_updated_at = $b2c_place_return->get_updated_at();
                $enseigne_updated_by = $b2c_place_return->get_updated_by();

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Enseigne - ID' => $enseigne_id,
                    'Enseigne - Enseigne ID' => $enseigne_ens_id,
                    'Enseigne - Enseigne ID Light' => $enseigne_ens_id_light,
                    'Enseigne - Enseigne Nom' => $enseigne_ens_name,
                    'Enseigne - Clé d\'activation' => $enseigne_activation_key,
                    'Enseigne - Utilise Enseigne ID' => $enseigne_use_id_ens ? 'Oui' : 'Non',
                    'Enseigne - Active' => $enseigne_is_active ? 'Oui' : 'Non',
                    'Enseigne - Options' => $enseigne_options,
                    'Enseigne - Modules' => $enseigne_modules,
                    'Enseigne - Adresse Ligne 1' => $enseigne_address1,
                    'Enseigne - Code Postal' => $enseigne_postcode,
                    'Enseigne - Ville' => $enseigne_city,
                    'Enseigne - Live Mapping API' => $enseigne_livemapping_api,
                    'Enseigne - Live Mapping PID' => $enseigne_livemapping_pid,
                    'Enseigne - Live Mapping Key' => $enseigne_livemapping_key,
                    'Enseigne - Dossier' => $enseigne_folder,
                    'Enseigne - Version de retour' => $enseigne_return_version,
                    'Enseigne - Login de retour' => $enseigne_return_login,
                    'Enseigne - Mot de passe de retour' => $enseigne_return_password,
                    'Enseigne - Code agence' => $enseigne_agency_code,
                    'Enseigne - Retour site' => $enseigne_return_site,
                    'Enseigne - Créé le' => $enseigne_created_at,
                    'Enseigne - Mis à jour le' => $enseigne_updated_at,
                    'Enseigne - Mis à jour par' => $enseigne_updated_by,
                ], 'relais-colis-woocommerce');

                // Get additional response details
                $entry_id = $b2c_place_return->get_entry_id();
                $order_id = $b2c_place_return->get_order_id();
                $additional_enseigne_id = $b2c_place_return->get_enseigne_id();
                $customer_id = $b2c_place_return->get_customer_id();
                $customer_fullname = $b2c_place_return->get_customer_fullname();
                $xeett = $b2c_place_return->get_xeett();
                $xeett_name = $b2c_place_return->get_xeett_name();
                $customer_phone = $b2c_place_return->get_customer_phone();
                $customer_mobile = $b2c_place_return->get_customer_mobile();
                $customer_company = $b2c_place_return->get_customer_company();
                $customer_address1 = $b2c_place_return->get_customer_address1();
                $customer_address2 = $b2c_place_return->get_customer_address2();
                $customer_postcode = $b2c_place_return->get_customer_postcode();
                $customer_city = $b2c_place_return->get_customer_city();
                $customer_country = $b2c_place_return->get_customer_country();
                $reference = $b2c_place_return->get_reference();
                $response_status = $b2c_place_return->get_response_status();
                $error_type = $b2c_place_return->get_error_type();
                $error_description = $b2c_place_return->get_error_description();
                $return_number = $b2c_place_return->get_return_number();
                $number_cab = $b2c_place_return->get_number_cab();
                $limit_date = $b2c_place_return->get_limit_date();
                $image_url = $b2c_place_return->get_image_url();
                $bordereau_smart_url = $b2c_place_return->get_bordereau_smart_url();
                $created_at = $b2c_place_return->get_created_at();
                $token = $b2c_place_return->get_token();

                WP_Log::debug(__METHOD__ . ' - Additional response details', [
                    'Entry ID' => $entry_id,
                    'Order ID' => $order_id,
                    'Enseigne ID' => $additional_enseigne_id,
                    'Customer ID' => $customer_id,
                    'Customer Full Name' => $customer_fullname,
                    'Xeett' => $xeett,
                    'Xeett Name' => $xeett_name,
                    'Customer Phone' => $customer_phone,
                    'Customer Mobile' => $customer_mobile,
                    'Customer Company' => $customer_company,
                    'Customer Address Line 1' => $customer_address1,
                    'Customer Address Line 2' => $customer_address2,
                    'Customer Postcode' => $customer_postcode,
                    'Customer City' => $customer_city,
                    'Customer Country' => $customer_country,
                    'Reference' => $reference,
                    'Response Status' => $response_status,
                    'Error Type' => $error_type,
                    'Error Description' => $error_description,
                    'Return Number' => $return_number,
                    'Cab Number' => $number_cab,
                    'Limit Date' => $limit_date,
                    'Image URL' => $image_url,
                    'Bordereau Smart URL' => $bordereau_smart_url,
                    'Created At' => $created_at,
                    'Token' => $token,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request b2c_place_return_v3 - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_b2c_place_return_v3_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        // ERROR in _activationKey
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            // ERROR in ORDER_ID
            $dynamic_params = array(
                WP_RC_Place_Return_V2::REQUESTS => array(
                    array(
                        WP_RC_Place_Return_V2::ORDER_ID => '99',
                        WP_RC_Place_Return_V2::CUSTOMER_ID => '99',
                        WP_RC_Place_Return_V2::CUSTOMER_FULLNAME => 'Tom Hatte',
                        WP_RC_Place_Return_V2::XEETT => 'I4040---------',
                        WP_RC_Place_Return_V2::XEETT_NAME => 'La Poste',
                        WP_RC_Place_Return_V2::CUSTOMER_PHONE => '0412356789',
                        WP_RC_Place_Return_V2::CUSTOMER_MOBILE => '0606060606',
                        WP_RC_Place_Return_V2::REFERENCE => 'TE12ST34',
                        WP_RC_Place_Return_V2::CUSTOMER_COMPANY => '',
                        WP_RC_Place_Return_V2::CUSTOMER_ADDRESS1 => "12 rue de l'épinoy",
                        WP_RC_Place_Return_V2::CUSTOMER_ADDRESS2 => "",
                        WP_RC_Place_Return_V2::CUSTOMER_POSTCODE => "59175",
                        WP_RC_Place_Return_V2::CUSTOMER_CITY => "Templatemars",
                        WP_RC_Place_Return_V2::CUSTOMER_COUNTRY => "France",
                        WP_RC_Place_Return_V2::PRESTATIONS => "1",
                    ),
                ),
            );

            $b2c_place_return = WP_Relais_Colis_API::instance()->b2c_place_return_v3( $dynamic_params, false );

            if ( is_null( $b2c_place_return ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $b2c_place_return->validate() ) {

                // Get enseigne response part
                $enseigne_options = $b2c_place_return->get_options();
                $enseigne_modules = $b2c_place_return->get_modules();
                $enseigne_id = $b2c_place_return->get_id();
                $enseigne_ens_name = $b2c_place_return->get_ens_name();
                $enseigne_ens_id = $b2c_place_return->get_ens_id();
                $enseigne_ens_id_light = $b2c_place_return->get_ens_id_light();
                $enseigne_is_active = $b2c_place_return->is_active();
                $enseigne_use_id_ens = $b2c_place_return->use_id_ens();
                $enseigne_livemapping_api = $b2c_place_return->get_livemapping_api();
                $enseigne_livemapping_pid = $b2c_place_return->get_livemapping_pid();
                $enseigne_livemapping_key = $b2c_place_return->get_livemapping_key();
                $enseigne_return_version = $b2c_place_return->get_return_version();
                $enseigne_return_login = $b2c_place_return->get_return_login();
                $enseigne_return_password = $b2c_place_return->get_return_pass();
                $enseigne_folder = $b2c_place_return->get_folder();
                $enseigne_address1 = $b2c_place_return->get_address1();
                $enseigne_postcode = $b2c_place_return->get_postcode();
                $enseigne_city = $b2c_place_return->get_city();
                $enseigne_agency_code = $b2c_place_return->get_agency_code();
                $enseigne_return_site = $b2c_place_return->get_return_site();
                $enseigne_activation_key = $b2c_place_return->get_activation_key();
                $enseigne_created_at = $b2c_place_return->get_created_at();
                $enseigne_updated_at = $b2c_place_return->get_updated_at();
                $enseigne_updated_by = $b2c_place_return->get_updated_by();

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Enseigne - ID' => $enseigne_id,
                    'Enseigne - Enseigne ID' => $enseigne_ens_id,
                    'Enseigne - Enseigne ID Light' => $enseigne_ens_id_light,
                    'Enseigne - Enseigne Nom' => $enseigne_ens_name,
                    'Enseigne - Clé d\'activation' => $enseigne_activation_key,
                    'Enseigne - Utilise Enseigne ID' => $enseigne_use_id_ens ? 'Oui' : 'Non',
                    'Enseigne - Active' => $enseigne_is_active ? 'Oui' : 'Non',
                    'Enseigne - Options' => $enseigne_options,
                    'Enseigne - Modules' => $enseigne_modules,
                    'Enseigne - Adresse Ligne 1' => $enseigne_address1,
                    'Enseigne - Code Postal' => $enseigne_postcode,
                    'Enseigne - Ville' => $enseigne_city,
                    'Enseigne - Live Mapping API' => $enseigne_livemapping_api,
                    'Enseigne - Live Mapping PID' => $enseigne_livemapping_pid,
                    'Enseigne - Live Mapping Key' => $enseigne_livemapping_key,
                    'Enseigne - Dossier' => $enseigne_folder,
                    'Enseigne - Version de retour' => $enseigne_return_version,
                    'Enseigne - Login de retour' => $enseigne_return_login,
                    'Enseigne - Mot de passe de retour' => $enseigne_return_password,
                    'Enseigne - Code agence' => $enseigne_agency_code,
                    'Enseigne - Retour site' => $enseigne_return_site,
                    'Enseigne - Créé le' => $enseigne_created_at,
                    'Enseigne - Mis à jour le' => $enseigne_updated_at,
                    'Enseigne - Mis à jour par' => $enseigne_updated_by,
                ], 'relais-colis-woocommerce');

                // Get additional response details
                $entry_id = $b2c_place_return->get_entry_id();
                $order_id = $b2c_place_return->get_order_id();
                $additional_enseigne_id = $b2c_place_return->get_enseigne_id();
                $customer_id = $b2c_place_return->get_customer_id();
                $customer_fullname = $b2c_place_return->get_customer_fullname();
                $xeett = $b2c_place_return->get_xeett();
                $xeett_name = $b2c_place_return->get_xeett_name();
                $customer_phone = $b2c_place_return->get_customer_phone();
                $customer_mobile = $b2c_place_return->get_customer_mobile();
                $customer_company = $b2c_place_return->get_customer_company();
                $customer_address1 = $b2c_place_return->get_customer_address1();
                $customer_address2 = $b2c_place_return->get_customer_address2();
                $customer_postcode = $b2c_place_return->get_customer_postcode();
                $customer_city = $b2c_place_return->get_customer_city();
                $customer_country = $b2c_place_return->get_customer_country();
                $reference = $b2c_place_return->get_reference();
                $response_status = $b2c_place_return->get_response_status();
                $error_type = $b2c_place_return->get_error_type();
                $error_description = $b2c_place_return->get_error_description();
                $return_number = $b2c_place_return->get_return_number();
                $number_cab = $b2c_place_return->get_number_cab();
                $limit_date = $b2c_place_return->get_limit_date();
                $image_url = $b2c_place_return->get_image_url();
                $bordereau_smart_url = $b2c_place_return->get_bordereau_smart_url();
                $created_at = $b2c_place_return->get_created_at();
                $token = $b2c_place_return->get_token();

                WP_Log::debug(__METHOD__ . ' - Additional response details', [
                    'Entry ID' => $entry_id,
                    'Order ID' => $order_id,
                    'Enseigne ID' => $additional_enseigne_id,
                    'Customer ID' => $customer_id,
                    'Customer Full Name' => $customer_fullname,
                    'Xeett' => $xeett,
                    'Xeett Name' => $xeett_name,
                    'Customer Phone' => $customer_phone,
                    'Customer Mobile' => $customer_mobile,
                    'Customer Company' => $customer_company,
                    'Customer Address Line 1' => $customer_address1,
                    'Customer Address Line 2' => $customer_address2,
                    'Customer Postcode' => $customer_postcode,
                    'Customer City' => $customer_city,
                    'Customer Country' => $customer_country,
                    'Reference' => $reference,
                    'Response Status' => $response_status,
                    'Error Type' => $error_type,
                    'Error Description' => $error_description,
                    'Return Number' => $return_number,
                    'Cab Number' => $number_cab,
                    'Limit Date' => $limit_date,
                    'Image URL' => $image_url,
                    'Bordereau Smart URL' => $bordereau_smart_url,
                    'Created At' => $created_at,
                    'Token' => $token,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request c2c_get_infos - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_get_infos_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( $options_suffix_param.'_C2C_hashToken', '4850e2012b4a77431774cd928e6ea944e98628a6242f' ); // preprod - C2C - 4850e2012b4a77431774cd928e6ea944e98628a6242f
        //update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v
        update_option( $options_suffix_param.'_C2C_hashToken', 'bef3f8fa7b689bb89105394ad43940c133724b0d924e' ); // prod - C2C - bef3f8fa7b689bb89105394ad43940c133724b0d924e

        // Call API
        try {
            // No param

            $c2c_get_infos = WP_Relais_Colis_API::instance()->c2c_get_infos( false );

            if ( is_null( $c2c_get_infos ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $c2c_get_infos->validate() ) {

                // Get enseigne response part
                $id = $c2c_get_infos->get_id();
                $firstname = $c2c_get_infos->get_firstname();
                $lastname = $c2c_get_infos->get_lastname();
                $email = $c2c_get_infos->get_email();
                $balance = $c2c_get_infos->get_balance();
                $account_status = $c2c_get_infos->get_account_status();
                $account_type = $c2c_get_infos->get_account_type();
                $code_enseigne = $c2c_get_infos->get_code_enseigne();

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Client - ID' => $id,
                    'Client - Prénom' => $firstname,
                    'Client - Nom' => $lastname,
                    'Client - Email' => $email,
                    'Client - Solde' => $balance,
                    'Client - Statut du compte' => $account_status,
                    'Client - Type de compte' => $account_type,
                    'Client - Code Enseigne' => $code_enseigne,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request c2c_get_infos - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_get_infos_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
        // ERROR in hash
//        update_option( $options_suffix_param.'_C2C_hashToken', '4850e2012b4a77431774cd928e6ea944e98628a6242f' ); // preprod - C2C - 4850e2012b4a77431774cd928e6ea944e98628a6242f
        update_option( $options_suffix_param.'_C2C_hashToken', 'bef3f8fa7b689bb89105394ad43940c133724b0d924e---' ); // prod - C2C - bef3f8fa7b689bb89105394ad43940c133724b0d924e

        // Call API
        try {
            // No param

            $c2c_get_infos = WP_Relais_Colis_API::instance()->c2c_get_infos( false );

            if ( is_null( $c2c_get_infos ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $c2c_get_infos->validate() ) {

                // Get enseigne response part
                $id = $c2c_get_infos->get_id();
                $firstname = $c2c_get_infos->get_firstname();
                $lastname = $c2c_get_infos->get_lastname();
                $email = $c2c_get_infos->get_email();
                $balance = $c2c_get_infos->get_balance();
                $account_status = $c2c_get_infos->get_account_status();
                $account_type = $c2c_get_infos->get_account_type();
                $code_enseigne = $c2c_get_infos->get_code_enseigne();

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Client - ID' => $id,
                    'Client - Prénom' => $firstname,
                    'Client - Nom' => $lastname,
                    'Client - Email' => $email,
                    'Client - Solde' => $balance,
                    'Client - Statut du compte' => $account_status,
                    'Client - Type de compte' => $account_type,
                    'Client - Code Enseigne' => $code_enseigne,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request c2c_get_packages_price - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_get_packages_price_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( $options_suffix_param.'_C2C_hashToken', '4850e2012b4a77431774cd928e6ea944e98628a6242f' ); // preprod - C2C - 4850e2012b4a77431774cd928e6ea944e98628a6242f
        update_option( $options_suffix_param.'_C2C_hashToken', 'bef3f8fa7b689bb89105394ad43940c133724b0d924e' ); // prod - C2C - bef3f8fa7b689bb89105394ad43940c133724b0d924e

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_C2C_Get_Packages_Price::PACKAGES_WEIGHT => array( 2000 ),
            );

            $c2c_get_packages_price = WP_Relais_Colis_API::instance()->c2c_get_packages_price( $dynamic_params, false );

            if ( is_null( $c2c_get_packages_price ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $c2c_get_packages_price->validate() ) {

                $entry = $c2c_get_packages_price->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request c2c_get_packages_price - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_get_packages_price_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( $options_suffix_param.'_C2C_hashToken', '4850e2012b4a77431774cd928e6ea944e98628a6242f' ); // preprod - C2C - 4850e2012b4a77431774cd928e6ea944e98628a6242f
        update_option( $options_suffix_param.'_C2C_hashToken', 'bef3f8fa7b689bb89105394ad43940c133724b0d924e' ); // prod - C2C - bef3f8fa7b689bb89105394ad43940c133724b0d924e

        // Call API
        try {
            // Dynamic params
            // ERROR in params
            $dynamic_params = array(
                //WP_RC_C2C_Get_Packages_Price::PACKAGES_WEIGHT => array( "toto" ),
            );

            $c2c_get_packages_price = WP_Relais_Colis_API::instance()->c2c_get_packages_price( $dynamic_params, false );

            if ( is_null( $c2c_get_packages_price ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $c2c_get_packages_price->validate() ) {

                $entry = $c2c_get_packages_price->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request transport_generate - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_transport_generate_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // Dynamic params
            $dynamic_params = array(
                WP_RC_Transport_Generate::SEARCH_BY => 'TODO',
                WP_RC_Transport_Generate::LETTER_NUMBER => '1',
                WP_RC_Transport_Generate::COLIS1 => 'TODO',
                WP_RC_Transport_Generate::COLIS2 => 'TODO',
            );

            $transport_generate = WP_Relais_Colis_API::instance()->transport_generate( $dynamic_params, false );

            if ( is_null( $transport_generate ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            WP_Log::debug( __METHOD__.' - Response is a PDF delivery label', ['URL'=>$transport_generate->get_pdf_transport_label()], 'relais-colis-woocommerce' );

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request get_packages_status - SUCCESS
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_get_packages_status_success() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // No param

            $get_packages_status = WP_Relais_Colis_API::instance()->get_packages_status( false );

            if ( is_null( $get_packages_status ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $get_packages_status->validate() ) {

                $entry = $get_packages_status->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * TEST
     * WP_Relais_Colis_API - Request get_packages_status - ERROR
     * @return void
     */
    public function test_wp_relais_colis_api_c2c_get_packages_status_error() {

        // Prepare options
        $options_suffix_param = Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param();
        update_option( $options_suffix_param.'_api_mode', WC_RC_Shipping_Constants::LIVE_MODE );
        // ERROR in action key
//        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E' ); // preprod - B2C - rtimlC15XYz5w9TSLf0bI8dmoPEsKp7E
        update_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY, 'fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v-------' ); // prod - B2C - fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v

        // Call API
        try {
            // No param

            $get_packages_status = WP_Relais_Colis_API::instance()->get_packages_status( false );

            if ( is_null( $get_packages_status ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                return;
            }

            // Display response
            if ( $get_packages_status->validate() ) {

                $entry = $get_packages_status->entry;

                WP_Log::debug(__METHOD__ . ' - Valid response', [
                    'Entry' => $entry,
                ], 'relais-colis-woocommerce');

            } else {

                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );
            }
        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
        }
    }
}
