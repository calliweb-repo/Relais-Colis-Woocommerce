<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

/**
 * Relais Colis WooCommerce Shipping Constants
 *
 * @since     1.0.0
 */
abstract class WC_RC_Shipping_Constants {

    // Live / Test mode
    // Option key
    const OPTION_LIVE_TEST_MODE_NAME = 'rc_live_test_mode';
    const LIVE_MODE = 'yes';
    const TEST_MODE = 'no';

    // Interaction mode
    const OPTION_RC_INTERACTION_MODE = 'rc_interaction_mode';
    const C2C_INTERACTION_MODE = 'c2c';
    const B2C_INTERACTION_MODE = 'b2c';
    const ENS_ID_C2C_INTERACTION_MODE_VALUE = 'CC';

    // RC API access validity
    const OPTION_RC_API_ACCESS_VALID = 'rc_api_valid_access';

    // Offers
    const OFFER_RELAIS_COLIS = 'Relais Colis';
    const OFFER_HOME = 'Home';
    const OFFER_HOME_PLUS = 'Home+';

    const METHOD_NAME_RELAIS_COLIS = 'rc';
    const METHOD_NAME_HOME = 'h';
    const METHOD_NAME_HOME_PLUS = 'hp';

    // Options <- trigger -> offers
    const OFFER_RELAIS_COLIS_ACTIVE_VALUE = 'rc_delivery';
    const OFFER_HOME_ACTIVE_VALUE = 'home_delivery';
    const OFFER_HOME_PLUS_ACTIVE_VALUE = 'home_delivery';

    // Tariff criterias
    const TARIFF_CRITERIA_PRICE = 'price';
    const TARIFF_CRITERIA_WEIGHT = 'weight';

    // Units
    const OPTION_RC_WEIGHT_UNIT = 'woocommerce_weight_unit'; // Using / overriding WooCommerce native units
    const OPTION_RC_LENGTH_UNIT = 'woocommerce_dimension_unit'; // Using / overriding WooCommerce native units
    const OPTION_RC_LABEL_FORMAT = 'rc_label_format';

    const RC_OPTION_PREFIX = 'rc_';

    // Api activation key
    const OPTION_ACTIVATION_KEY = WC_RC_Shipping_Constants::RC_OPTION_PREFIX.'activation_key';

    // Api C2C hash token
    const OPTION_C2C_HASH_TOKEN = WC_RC_Shipping_Constants::RC_OPTION_PREFIX.'c2c_hash_token';

    // Configuration
    const CONFIGURATION_ENSEIGNE_ID = 'ens_id';
    const CONFIGURATION_ENSEIGNE_ID_LIGHT = 'ens_id_light';
    const CONFIGURATION_ENSEIGNE_NOM = 'ens_name';
    const CONFIGURATION_ACTIVATION_KEY = 'activation_key';
    const CONFIGURATION_ACTIVE = 'active';
    const CONFIGURATION_USEIDENS = 'useidens';
    const CONFIGURATION_ADDRESS_LINE1 = 'address1';
    const CONFIGURATION_POSTAL_CODE = 'postcode';
    const CONFIGURATION_CITY = 'city';
    const CONFIGURATION_LIVEMAPPING_API = 'livemapping_api';
    const CONFIGURATION_LIVEMAPPING_PID = 'livemapping_pid';
    const CONFIGURATION_LIVEMAPPING_KEY = 'livemapping_key';
    const CONFIGURATION_FOLDER = 'folder';
    const CONFIGURATION_RETURN_VERSION = 'return_version';
    const CONFIGURATION_RETURN_LOGIN = 'return_login';
    const CONFIGURATION_RETURN_PASS = 'return_pass';
    const CONFIGURATION_AGENCY_CODE = 'agency_code';
    const CONFIGURATION_RETURN_SITE = 'return_site';
    const CONFIGURATION_UPDATED_BY = 'updated_by';
    const CONFIGURATION_CREATED_AT = 'created_at';
    const CONFIGURATION_UPDATED_AT = 'updated_at';

    // Configuration options
    const CONFIGURATION_OPTION_ID = 'id';
    const CONFIGURATION_OPTION_NAME = 'name';
    const CONFIGURATION_OPTION_VALUE = 'value';
    const CONFIGURATION_OPTION_ACTIVE = 'active';

    // Informations
    const INFORMATION_RESULT_ID = 'id';
    const INFORMATION_FIRSTNAME = 'firstname';
    const INFORMATION_LASTNAME = 'lastname';
    const INFORMATION_EMAIL = 'email';
    const INFORMATION_BALANCE = 'balance';
    const INFORMATION_ACCOUNT_STATUS = 'accountStatus';
    const INFORMATION_ACCOUNT_TYPE = 'accountType';
    const INFORMATION_CODE_ENSEIGNE = 'codeEnseigne';

    /**
     * Get title from configuration slug
     * @param $rc_configuration_slug the slug of the configuration
     * @return string|void the human-readable title
     */
    public static function get_configuration_title( string $rc_configuration_slug ) {

        $titles = [
            self::CONFIGURATION_ENSEIGNE_ID => __( 'Enseigne ID', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_ENSEIGNE_ID_LIGHT => __( 'Enseigne ID Light', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_ENSEIGNE_NOM => __( 'Enseigne Name', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_ACTIVATION_KEY => __( 'Activation Key', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_ACTIVE => __( 'Active', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_USEIDENS => __( 'Use Enseigne ID', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_ADDRESS_LINE1 => __( 'Address Line 1', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_POSTAL_CODE => __( 'Postal Code', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_CITY => __( 'City', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_LIVEMAPPING_API => __( 'Live Mapping API', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_LIVEMAPPING_PID => __( 'Live Mapping PID', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_LIVEMAPPING_KEY => __( 'Live Mapping Key', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_FOLDER => __( 'Folder', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_RETURN_VERSION => __( 'Return Version', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_RETURN_LOGIN => __( 'Return Login', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_RETURN_PASS => __( 'Return Password', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_AGENCY_CODE => __( 'Agency Code', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_RETURN_SITE => __( 'Return Site', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_UPDATED_BY => __( 'Updated By', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_CREATED_AT => __( 'Created At', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_UPDATED_AT => __( 'Updated At', 'relais-colis-woocommerce' ),
        ];

        return $titles[ $rc_configuration_slug ] ?? __( 'Unknown Field', 'relais-colis-woocommerce' );
    }

    /**
     * Get title from configuration option slug
     * @param $option_slug the slug of the configuration option
     * @return string|void the human-readable title
     */
    public static function get_option_title( string $option_slug ) {

        $titles = [
            self::CONFIGURATION_OPTION_ID => __( 'Option ID', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_OPTION_NAME => __( 'Option Name', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_OPTION_VALUE => __( 'Option Value', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_OPTION_ACTIVE => __( 'Option Active', 'relais-colis-woocommerce' ),
        ];

        return $titles[ $option_slug ] ?? __( 'Unknown Option', 'relais-colis-woocommerce' );
    }

    /**
     * Get list of formats units
     * @return array the human-readable title
     */
    public static function get_format_units() {

        return array(
            'A4' => __( 'A4 Format', 'relais-colis-woocommerce' ),
            'A5' => __( 'A5 Format', 'relais-colis-woocommerce' ),
            '10x15' => __( '10x15 Format', 'relais-colis-woocommerce' ),
        );
    }

    /**
     * Get list of length units
     * @return array the human-readable title
     */
    public static function get_dimension_units() {

        return array(
            'mm' => __( 'Millimeters (mm)', 'relais-colis-woocommerce' ),
            'cm' => __( 'Centimeters (cm)', 'relais-colis-woocommerce' ), // Default unit for communication with RC API
            'dm' => __( 'Decimeters (dm)', 'relais-colis-woocommerce' ),
            'm' => __( 'Meters (m)', 'relais-colis-woocommerce' ),
        );
    }

    /**
     * Get list of weight units
     * @return array the human-readable title
     */
    public static function get_weight_units() {

        return array(
            'mg' => __( 'Milligrams (mg)', 'relais-colis-woocommerce' ),
            'cg' => __( 'Centigrams (mg)', 'relais-colis-woocommerce' ),
            'dg' => __( 'Decigrams (dg)', 'relais-colis-woocommerce' ),
            'g' => __( 'Grams (g)', 'relais-colis-woocommerce' ), // Default unit for communication with RC API
            'kg' => __( 'Kilograms (kg)', 'relais-colis-woocommerce' ),
        );
    }

    /**
     * Get title from information slug
     * @param $rc_information_slug the slug of the information
     * @return string|void the human-readable title
     */
    public static function get_information_title( string $information_slug ) {

        $titles = [
            self::INFORMATION_RESULT_ID => __( 'Result ID', 'relais-colis-woocommerce' ),
            self::INFORMATION_FIRSTNAME => __( 'First Name', 'relais-colis-woocommerce' ),
            self::INFORMATION_LASTNAME => __( 'Last Name', 'relais-colis-woocommerce' ),
            self::INFORMATION_EMAIL => __( 'Email', 'relais-colis-woocommerce' ),
            self::INFORMATION_BALANCE => __( 'Balance', 'relais-colis-woocommerce' ),
            self::INFORMATION_ACCOUNT_STATUS => __( 'Account Status', 'relais-colis-woocommerce' ),
            self::INFORMATION_ACCOUNT_TYPE => __( 'Account Type', 'relais-colis-woocommerce' ),
            self::INFORMATION_CODE_ENSEIGNE => __( 'Code Enseigne', 'relais-colis-woocommerce' ),
        ];

        return $titles[ $information_slug ] ?? __( 'Unknown Information', 'relais-colis-woocommerce' );
    }

    /**
     * Get offers
     * @return string[]
     */
    public static function get_offers() {

        return array(
            self::METHOD_NAME_HOME => self::OFFER_HOME,
            self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS,
            self::METHOD_NAME_RELAIS_COLIS => self::OFFER_RELAIS_COLIS,
        );
}

    /**
     * Getter for fixed services
     * @return array fixed services (prestations)
     */
    public static function get_fixed_services() {
        return array(
            'appointment_scheduling' => array(
                __( 'Appointment Scheduling', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME => self::OFFER_HOME, self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // Prise de Rendez-vous

            'delivery_to_floor' => array(
                __( 'Delivery to the Floor', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // Livraison à l’étage

            'two_person_delivery' => array(
                __( 'Two-Person Delivery', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // Livraison à deux

            'setup_large_appliances' => array(
                __( 'Setup of Large Appliances', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // M.E.S gros électroménager

            'quick_assembly' => array(
                __( 'Quick Assembly', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // Assemblage rapide

            'oversized_items' => array(
                __( 'Oversized Items', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME => self::OFFER_HOME, self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // Hors Norme

            'product_unpacking' => array(
                __( 'Product Unpacking', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // Déballage produit

            'packaging_removal' => array(
                __( 'Packaging Removal', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // Evacuation Emballage

            'removal_old_equipment' => array(
                __( 'Removal of Old Equipment', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME => self::OFFER_HOME, self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // Reprise de votre ancien matériel

            'delivery_desired_room' => array(
                __( 'Delivery to Desired Room', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME_PLUS => self::OFFER_HOME_PLUS],
            ), // Livraison dans la pièce souhaitée

            'curbside_delivery' => array(
                __( 'Curbside Delivery', 'relais-colis-woocommerce' ),
                [self::METHOD_NAME_HOME => self::OFFER_HOME],
            ), // Livraison au pas de porte
        );
    }
}
