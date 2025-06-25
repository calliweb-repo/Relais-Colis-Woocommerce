<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;


defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object.
 *
 * This class represents a request object for the WP_Relais_Colis API.
 * It encapsulates all the necessary parameters and metadata to interact
 * with the Relais Colis system, including customer details, shipping information,
 * and order-specific attributes for various operations.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 * - activityCode (string): The activity code for the operation (e.g., "05") -> "05" relais "08" pour le home et "07" pour le drive (à venir dnas quelque mois)
 * - agencyCode (string): The code of the agency responsible for the delivery (e.g., "AGENCY123").
 * - customerId (string): The unique identifier of the customer (e.g., "99").
 * - customerFullname (string): The full name of the customer (e.g., "Tom Hatte").
 * - customerEmail (string): The email address of the customer (e.g., "tom.hatte@example.com").
 * - customerPhone (string): The phone number of the customer (e.g., "0412356789").
 * - customerMobile (string): The mobile number of the customer (e.g., "0606060606").
 * - deliveryPaymentMethod (string): The payment method for delivery (e.g., "3").
 * - deliveryType (string): The type of delivery (e.g., "00").
 * - language (string): The language code for the request (e.g., "FR").
 * - orderType (string): The type of the order (e.g., "1").
 * - orderTypeSub (string): The subtype of the order (e.g., "1").
 * - pickingSite (string): The picking site for the delivery (e.g., "0").
 * - productFamily (string): The product family code (e.g., "08"). -> pour un relais la valeur est "08" et pour un home ou home + "55"
 * - pseudoRvc (string): The pseudo RVC identifier (e.g., "RVC123").
 * - orderReference (string): The reference ID of the order (e.g., "ORD123456789").
 * - sensitiveProduct (string): Indicates if the product is sensitive (e.g., "0").
 * - shippingAddress1 (string): The first line of the shipping address (e.g., "12 Rue Example").
 * - shippingAddress2 (string): The second line of the shipping address (optional, e.g., "Bâtiment A").
 * - shippingPostcode (string): The postcode of the shipping address (e.g., "59000").
 * - shippingCity (string): The city of the shipping address (e.g., "Lille").
 * - shippingCountryCode (string): The country code of the shipping address (e.g., "FR").
 * - shippmentWeight (int): The weight of the shipment in grams (e.g., 1000).
 * - weight (int): The weight of the package in grams (e.g., 1000).
 * - xeett (string): The Xeett identifier for the shipment (e.g., "I4040").
 *
 * Example JSON Request:
 * ```json
 * {
 *       "activationKey" : "{{activationKey}}", // mandatory
 *       "activityCode": "05", // constant value
 *       "agencyCode": "{{DATA_agencyCode}}", // mandatory agency relay code selected
 *       "customerId": "{{DATA_CLT_customerId}}", // mandatory customer id in csm
 *       "customerFullname": "{{DATA_CLT_firstname}} {{DATA_CLT_lastname}}", // mandatory
 *       "customerEmail": "{{DATA_CLT_email}}", // mandatory but can be empty
 *       "customerPhone": "{{DATA_CLT_phoneNumber}}", //mandatory but can be empty
 *       "customerMobile": "{{DATA_CLT_mobileNumber}}",// mandatory but can be empty
 *       "deliveryPaymentMethod": "3", // constant value
 *       "deliveryType": "00", // constant value
 *       "language": "FR", //constant value
 *       "orderType": "1", // constant value
 *       "orderTypeSub": "1", //constant value
 *       "pickingSite": "0", //constant value
 *       "productFamily": "08", // constant value
 *       "pseudoRvc": "{{DATA_pseudoRvc}}", // mandatory, retrned in relay information
 *       "orderReference": "{{DATA_CLT_orderId}}", // order reference in cms
 *       "sensitiveProduct": "0", // constant value
 *       "shippingAddress1": "{{DATA_CLT_address1}}", // mandatory relay address
 *       "shippingAddress2": "{{DATA_CLT_address2}}", // mandatory but can be empty
 *       "shippingPostcode": "{{DATA_CLT_postcode}}",  // mandatory relay address
 *       "shippingCity": "{{DATA_CLT_city}}", // mandatory relay address
 *       "shippingCountryCode": "FR", // constant value
 *       "shippmentWeight": "1000", // mandatory total weight of the order
 *       "weight": "1000", // mandatory weight of the package
 *       "xeett": "{{DATA_CLT_XeettId}}" // mandatory returned in relay information
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to interact with the Relais Colis API for a variety of operations, including label reservation, order management, and delivery tracking.
 * - The parameters must be populated based on the specific operation being performed.
 *
 * Notes:
 * - The `activationKey` is mandatory for all API operations.
 * - Ensure all required fields (e.g., `customerFullname`, `shippingAddress1`, `shippingPostcode`) are provided to avoid errors.
 * - Optional fields like `shippingAddress2` and `sensitiveProduct` can be included based on the specific needs of the request.
 *
 * @since 1.0.0
 */
class WP_RC_B2C_Relay_Place_Advertisement extends WP_RC_Place_Advertisement_Request {

    //    "activationKey" : "{{activationKey}}", // mandatory
    //    "activityCode": "05", // constant value
    //    "agencyCode": "{{DATA_agencyCode}}", // mandatory agency relay code selected
    //    "customerId": "{{DATA_CLT_customerId}}", // mandatory customer id in csm
    //    "customerFullname": "{{DATA_CLT_firstname}} {{DATA_CLT_lastname}}", // mandatory
    //    "customerEmail": "{{DATA_CLT_email}}", // mandatory but can be empty
    //    "customerPhone": "{{DATA_CLT_phoneNumber}}", //mandatory but can be empty
    //    "customerMobile": "{{DATA_CLT_mobileNumber}}",// mandatory but can be empty
    //    "deliveryPaymentMethod": "3", // constant value
    //    "deliveryType": "00", // constant value
    //    "language": "FR", //constant value
    //    "orderType": "1", // constant value
    //    "orderTypeSub": "1", //constant value
    //    "pickingSite": "0", //constant value
    //    "productFamily": "08", // constant value
    //    "pseudoRvc": "{{DATA_pseudoRvc}}", // mandatory, retrned in relay information
    //    "orderReference": "{{DATA_CLT_orderId}}", // order reference in cms
    //    "sensitiveProduct": "0", // constant value
    //    "shippingAddress1": "{{DATA_CLT_address1}}", // mandatory relay address
    //    "shippingAddress2": "{{DATA_CLT_address2}}", // mandatory but can be empty
    //    "shippingPostcode": "{{DATA_CLT_postcode}}",  // mandatory relay address
    //    "shippingCity": "{{DATA_CLT_city}}", // mandatory relay address
    //    "shippingCountryCode": "FR", // constant value
    //    "shippmentWeight": "1000", // mandatory total weight of the order
    //    "weight": "1000", // mandatory weight of the package
    //    "xeett": "{{DATA_CLT_XeettId}}" // mandatory returned in relay information
    const XEETT = 'xeett';

    private $specific_mandatory_params = array(
        self::ACTIVATION_KEY,
        self::AGENCY_CODE,
        self::CUSTOMER_ID,
        self::CUSTOMER_FULLNAME,
        self::CUSTOMER_EMAIL,
        self::CUSTOMER_PHONE,
        self::CUSTOMER_MOBILE,
        self::PSEUDO_RVC,
        self::SHIPPING_ADDRESS_1,
        self::SHIPPING_ADDRESS_2,
        self::SHIPPING_POSTCODE,
        self::SHIPPING_CITY,
        self::SHIPPMENT_WEIGHT,
        self::WEIGHT,
        // "xeett" mandatory returned in relay information
        self::XEETT,
        self::DELIVERY_TYPE
    );


    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    protected function get_specific_mandatory_params() {

        return $this->specific_mandatory_params;
    }

    /**
     * Template Method used to get specific dedicated data
     * @return array list of dedicated params
     */
    protected function get_specific_dedicated_params() {

        return array(
            // "05" relais "08" pour le home et "07" pour le drive (à venir dnas quelque mois)
            self::ACTIVITY_CODE => '05',
            // "deliveryPaymentMethod" c'est une valeur constante "3"
            self::DELIVERY_PAYMENT_METHOD => '3',
            // "deliveryType" c'est une valeur constante "00"
            //self::DELIVERY_TYPE => '00',
            self::LANGUAGE => 'FR',
            //"orderType" c'est une valeur constante "1"
            self::ORDER_TYPE => '1',
            //"orderTypeSub" c'est une valeur constante "1"
            self::ORDER_TYPE_SUB => '1',
            //"pickingSite"  c'est une valeur constante "0"
            self::PICKING_SITE => '0',
            //"productFamily" pour un relais la valeur est "08" et pour un home ou home + "55"
            self::PRODUCT_FAMILY => '08',
            self::SHIPPING_COUNTRY_CODE => 'FR',
        );
    }

    /**
     * 02 - B2C - Réservation d'étiquette - Relais
     * /api/package/placeAdvertisement
     *
     * Params
     * AGENCY_CODE,            // Code de l'agence
     * CUSTOMER_ID,           // ID du client
     * CUSTOMER_FULLNAME,     // Nom complet du client
     * CUSTOMER_EMAIL,        // Email du client
     * CUSTOMER_PHONE,        // Numéro de téléphone du client
     * CUSTOMER_MOBILE,       // Numéro de mobile du client
     * PSEUDO_RVC,            // Pseudo RVC
     * ORDER_REFERENCE,       // Référence de commande
     * SHIPPING_ADDRESS_1,    // Adresse de livraison ligne 1
     * SHIPPING_ADDRESS_2,    // Adresse de livraison ligne 2 (facultatif)
     * SHIPPING_POSTCODE,     // Code postal de livraison
     * SHIPPING_CITY,         // Ville de livraison
     * SHIPPING_COUNTRY_CODE, // Code pays de livraison
     * XEETT,                 // ID spécifique Xeett
     *
     * @since 1.0.0
     *
     * @param array $params parameters
     */
    public function prepare_request( array $params=null ) {

        $this->method = 'POST';
        $this->path = 'api/package/placeAdvertisement'; // No / at beginning

        $activationKey = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );

        // These params are always the sames
        //"activityCode" correspond au type d'envoi "05" relais "08" pour le home et "07" pour le drive (à venir dnas quelque mois)
        //"customerId" c'est bien l'id du customer dans le cms
        //"orderReference" c'est le numéro de commande dans woocommerce
        $dedicated_data = array(
            self::ACTIVATION_KEY => $activationKey,
        );
        $datas = [];

        foreach ($params as $param) {
            $data = array_merge($dedicated_data, $this->get_specific_dedicated_params(), $param);
            $datas[] = $data;
        }

        $this->data = $datas;

        
        $this->validate();
        
        // May convert weight to grams
        $woocommerce_weight_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT, 'g' );

        foreach ($this->data as $key => &$data) {

            $shippment_weight_grams = WP_Helper::convert_to_grams( $data[ self::SHIPPMENT_WEIGHT ], $woocommerce_weight_unit );
            if ( !is_null( $shippment_weight_grams ) ) $data[ self::SHIPPMENT_WEIGHT ] = $shippment_weight_grams;
    
            $weight_grams = WP_Helper::convert_to_grams( $data[ self::WEIGHT ], $woocommerce_weight_unit );
            if ( !is_null( $weight_grams ) ) $data[ self::WEIGHT ] = $weight_grams;

        }

        unset($data);

        
 

        $post_data = array_values($this->data);


        WP_Log::debug( __METHOD__, [ 'method' => $this->method, 'path' => $this->path, 'post_data' => $this->data ], 'relais-colis-woocommerce' );
        $this->data = json_encode( $post_data );
    }

    public function validate() {
        $mandatory_params = $this->get_mandatory_params();
        

        foreach ($this->data as $key => $data) {

            foreach ( $mandatory_params as $param ) {
                if ( !isset( $data[ $param ] ) || is_null( $data[ $param ] ) ) {
    
                    WP_Log::error( __METHOD__, ['$param'=>$param], 'relais-colis-woocommerce' );
                    throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER)).' '.esc_html($param), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER]) );
                }
            }
        }

    }
}
