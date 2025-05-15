<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

/**
 * Relais Colis WooCommerce Shipping Constants
 *
 * @since     1.0.0
 */
abstract class WC_RC_Shipping_Constants {

    // States for orders packaging
    // State is store in meta data WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE
    // Just after order checkout, state is ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED
    // When distribution of items in packages is finished, state becomes ORDER_STATE_ITEMS_DISTRIBUTED
    // From state ORDER_STATE_ITEMS_DISTRIBUTED, when shipping labels have been placed (generated), state becomes ORDER_STATE_SHIPPING_LABELS_PLACED
    // From state ORDER_STATE_SHIPPING_LABELS_PLACED, when way bills have been generated, state becomes ORDER_STATE_WAY_BILLS_GENERATED
    const ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED = 'order_state_items_to_be_distributed';
    const ORDER_STATE_ITEMS_DISTRIBUTED = 'order_state_items_distributed';
    const ORDER_STATE_SHIPPING_LABELS_PLACED = 'order_state_shipping_labels_placed';
    const ORDER_STATE_WAY_BILLS_GENERATED = 'order_state_way_bills_generated';

    // List or order meta data
    // -> Misc. infos about shipping
    const ORDER_META_DATA_RC_COLIS = '_rc_colis';
    const ORDER_META_DATA_RC_SERVICE_INFOS = 'rc_service_infos';
    const ORDER_META_DATA_RC_SERVICES = 'rc_services';
    const ORDER_META_DATA_RC_RELAY_DATA = 'rc_relay_data';
    const ORDER_META_DATA_RC_SHIPPING_METHOD = 'rc_shipping_method';
    const ORDER_META_DATA_RC_STATE = 'rc_state';
    const ORDER_META_DATA_RC_IS_MAX = 'rc_is_max';
    // -> For return
    const ORDER_META_DATA_RC_RETURN_BORDEREAU_SMART_URL = 'rc_return_bordereau_smart_url';
    const ORDER_META_DATA_RC_RETURN_RETURN_NUMBER = 'rc_return_return_number';
    const ORDER_META_DATA_RC_RETURN_NUMBER_CAB = 'rc_return_number_cab';
    const ORDER_META_DATA_RC_RETURN_LIMIT_DATE = 'rc_return_limit_date';
    const ORDER_META_DATA_RC_RETURN_IMAGE_URL = 'rc_return_image_url';
    const ORDER_META_DATA_RC_RETURN_TOKEN = 'rc_return_token';
    const ORDER_META_DATA_RC_RETURN_CREATED_AT = 'rc_return_created_at';
    // -> For waybill
    const ORDER_META_DATA_RC_WAY_BILL = 'rc_way_bill';

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

    // RC Status
    // RC_Colis_Annoncé - Le statut s'applique une fois que les étiquettes de la commande ont été générées.
    // RC_Expédié - Quand le produit est enregistré par le relais de départ.
    // RC_Livraison_en_cours - Produit récupéré par le transporteur.
    // RC_Déposé_en_Relais - Colis déposé au relais.
    // RC_Livré - Colis retiré au point relais.
    // RC_Echec_Livraison - Colis non récupérés.
    // RC_Pending - Status introduis pour l'attente d'un premier retour de status
    const STATUS_RC_PENDING = 'status_rc_pending';
    const STATUS_RC_COLIS_ANNONCE = 'status_rc_colis_annonce';
    const STATUS_RC_EXPEDIE = 'status_rc_expedie';
    const STATUS_RC_LIVRAISON_EN_COURS = 'status_rc_livraison_en_cours';
    const STATUS_RC_DEPOSE_EN_RELAIS = 'status_rc_depose_en_relais';
    const STATUS_RC_LIVRE = 'status_rc_livre';
    const STATUS_RC_ECHEC_LIVRAISON = 'status_rc_echec_livraison';
    const STATUS_RC_RETOURNE = 'status_rc_retourne';
    const STATUS_RC_EN_COURS_DE_RETOUR = 'status_rc_en_cours_de_retour';


    // RC API access validity
    const OPTION_RC_API_ACCESS_VALID = 'rc_api_valid_access';

    // Offers
    const OFFER_RELAIS_COLIS = 'Livraison en Relais';
    const OFFER_HOME = 'Livraison à domicile';
    const OFFER_HOME_PLUS = 'Livraison à domicile +';
    const OFFER_RELAIS_COLIS_MAX = 'rc_max';

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

    // Configuration stored as option
    const CONFIGURATION_ENSEIGNE_ID = 'ens_id';
    const CONFIGURATION_ENSEIGNE_ID_LIGHT = 'ens_id_light';
    const CONFIGURATION_ENSEIGNE_NOM = 'ens_name';
    const CONFIGURATION_ACTIVATION_KEY = 'activation_key';
    const CONFIGURATION_ACTIVE = 'active';
    const CONFIGURATION_USEIDENS = 'useidens';
    const CONFIGURATION_ADDRESS_LINE1 = 'address1';
    const CONFIGURATION_ADDRESS_LINE2 = 'address2';
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
    const CONFIGURATION_OSM_LIVEMAPPING_KEY = 'osm_live_mapping_key';
    const CONFIGURATION_OSM_LIVEMAPPING_ENS = 'osm_live_mapping_ens';

    // Configuration options
    const CONFIGURATION_OPTION_ID = 'id';
    const CONFIGURATION_OPTION_NAME = 'name';
    const CONFIGURATION_OPTION_VALUE = 'value';
    const CONFIGURATION_OPTION_ACTIVE = 'active';
    const CONFIGURATION_OPTION_MAX = 'rc_max';
    const CONFIGURATION_OPTION_MAX_NAME = 'Relais Max';

    // Informations
    const INFORMATION_RESULT_ID = 'id';
    const INFORMATION_FIRSTNAME = 'firstname';
    const INFORMATION_LASTNAME = 'lastname';
    const INFORMATION_EMAIL = 'email';
    const INFORMATION_BALANCE = 'balance';
    const INFORMATION_ACCOUNT_STATUS = 'accountStatus';
    const INFORMATION_ACCOUNT_TYPE = 'accountType';
    const INFORMATION_CODE_ENSEIGNE = 'codeEnseigne';

    // Packages status
    const PACKAGES_STATUS_PARCEL_NUMBER = 'parcelNumber';
    const PACKAGES_STATUS_RANG_PAQUET = 'RangPaquet';
    const PACKAGES_STATUS_LIBELLE = 'Libelle';
    const PACKAGES_STATUS_LIBELLE_DETAILLE = 'LibelleDetaille';
    const PACKAGES_STATUS_CATEGORIE = 'Categorie';
    const PACKAGES_STATUS_DATE = 'Date';
    const PACKAGES_STATUS_GMT = 'GMT';
    const PACKAGES_STATUS_ETAPE = 'Etape';
    const PACKAGES_STATUS_CODE_EVT = 'CodeEVT';
    const PACKAGES_STATUS_CODE_JUS = 'CodeJUS';
    const PACKAGES_STATUS_CODE_TRACK = 'CodeTrack';

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
            self::CONFIGURATION_ADDRESS_LINE2 => __( 'Address Line 2', 'relais-colis-woocommerce' ),
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
            self::CONFIGURATION_OSM_LIVEMAPPING_KEY => __( 'OSM Live Mapping Key', 'relais-colis-woocommerce' ),
            self::CONFIGURATION_OSM_LIVEMAPPING_ENS => __( 'OSM Live Mapping Enseigne ID', 'relais-colis-woocommerce' ),
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
            'A4' => __( '10 X 15 : 4 PER PAGE', 'relais-colis-woocommerce' ),
            //'A5' => __( 'A5', 'relais-colis-woocommerce' ),
            
            WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode() ? 'A6' : 'ZEBRA' => __( '10 X 15 : ZEBRA', 'relais-colis-woocommerce' ),
        );
    }

    /**
     * Get list of length units
     * @return array the human-readable title
     */
    public static function get_dimension_units() {

        return array(
            //'mm' => __( 'Millimeters (mm)', 'relais-colis-woocommerce' ),
            'cm' => __( 'Centimeters (cm)', 'relais-colis-woocommerce' ), // Default unit for communication with RC API
            //'dm' => __( 'Decimeters (dm)', 'relais-colis-woocommerce' ),
            'm' => __( 'Meters (m)', 'relais-colis-woocommerce' ),
        );
    }

    /**
     * Get list of weight units
     * @return array the human-readable title
     */
    public static function get_weight_units() {

        return array(
            //'mg' => __( 'Milligrams (mg)', 'relais-colis-woocommerce' ),
            //'cg' => __( 'Centigrams (mg)', 'relais-colis-woocommerce' ),
            //'dg' => __( 'Decigrams (dg)', 'relais-colis-woocommerce' ),
            'g' => __( 'Grams (g)', 'relais-colis-woocommerce' ), // Default unit for communication with RC API
            'kg' => __( 'Kilograms (kg)', 'relais-colis-woocommerce' ),
        );
    }

    /**
     * Get list of RC statuses
     * @return array the human-readable title
     */
    public static function get_rc_statuses() {

        return array(
            self::STATUS_RC_COLIS_ANNONCE => __( 'The shipping labels for the order {index} have been generated', 'relais-colis-woocommerce' ),
            self::STATUS_RC_EXPEDIE => __( 'The product has been registered by the departure relay', 'relais-colis-woocommerce' ),
            self::STATUS_RC_LIVRAISON_EN_COURS => __( 'The product has been picked up by the carrier', 'relais-colis-woocommerce' ),
            self::STATUS_RC_DEPOSE_EN_RELAIS => __( 'The package has been dropped off at the relay point', 'relais-colis-woocommerce' ),
            self::STATUS_RC_LIVRE => __( 'The package has been collected from the relay point', 'relais-colis-woocommerce' ),
            self::STATUS_RC_ECHEC_LIVRAISON => __( 'The package was not picked up', 'relais-colis-woocommerce' ),
            self::STATUS_RC_RETOURNE => __( 'The package has been returned to the sender', 'relais-colis-woocommerce' ),
            self::STATUS_RC_EN_COURS_DE_RETOUR => __( 'The package is currently being returned to the sender', 'relais-colis-woocommerce' ),
        );
    }

    /**
     * Get title from status
     * @param $rc_information_slug the slug of the information
     * @return string|void the human-readable title
     */
    public static function get_rc_status_title( string $rc_status ) {

        $titles = array(
            self::STATUS_RC_COLIS_ANNONCE => __( 'The shipping labels for the order {index} have been generated', 'relais-colis-woocommerce' ),
            self::STATUS_RC_EXPEDIE => __( 'The product has been registered by the departure relay', 'relais-colis-woocommerce' ),
            self::STATUS_RC_LIVRAISON_EN_COURS => __( 'The product has been picked up by the carrier', 'relais-colis-woocommerce' ),
            self::STATUS_RC_DEPOSE_EN_RELAIS => __( 'The package has been dropped off at the relay point', 'relais-colis-woocommerce' ),
            self::STATUS_RC_LIVRE => __( 'The package has been collected from the relay point', 'relais-colis-woocommerce' ),
            self::STATUS_RC_ECHEC_LIVRAISON => __( 'The package was not picked up', 'relais-colis-woocommerce' ),
            self::STATUS_RC_RETOURNE => __( 'The package has been returned to the sender', 'relais-colis-woocommerce' ),
            self::STATUS_RC_EN_COURS_DE_RETOUR => __( 'The package is currently being returned to the sender', 'relais-colis-woocommerce' ),
        );

        return $titles[ $rc_status ] ?? __( 'Unknown status', 'relais-colis-woocommerce' );
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
     * Get title from order state
     * @param $order_state the slug of the order state
     * @return string|void the human-readable title
     */
    public static function get_order_state_title( string $order_state ) {

        $titles = [
            self::ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED => __( 'Items to be distributed', 'relais-colis-woocommerce' ),
            self::ORDER_STATE_ITEMS_DISTRIBUTED => __( 'Items distributed', 'relais-colis-woocommerce' ),
            self::ORDER_STATE_SHIPPING_LABELS_PLACED => __( 'Shipping labels placed', 'relais-colis-woocommerce' ),
            self::ORDER_STATE_WAY_BILLS_GENERATED => __( 'Way bills generated', 'relais-colis-woocommerce' ),
        ];

        return $titles[ $order_state ] ?? __( 'Unknown order state', 'relais-colis-woocommerce' );
    }

    /**
     * Get order states
     * @return string[]
     */
    public static function get_order_states() {

        return array(
            self::ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED => __( 'Items to be distributed', 'relais-colis-woocommerce' ),
            self::ORDER_STATE_ITEMS_DISTRIBUTED => __( 'Items distributed', 'relais-colis-woocommerce' ),
            self::ORDER_STATE_SHIPPING_LABELS_PLACED => __( 'Shipping labels placed', 'relais-colis-woocommerce' ),
            self::ORDER_STATE_WAY_BILLS_GENERATED => __( 'Way bills generated', 'relais-colis-woocommerce' ),
        );
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
}
