<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object for B2C Home Label Reservation.
 *
 * This class represents a request object for the B2C "Home Label Reservation" operation in the WP_Relais_Colis API.
 * It encapsulates all necessary parameters and metadata required for generating a shipping label
 * for home delivery in a B2C context.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 * - activityCode (string): The activity code for the operation (e.g., "08").
 * - agencyCode (string): The code of the agency responsible for processing the delivery (e.g., "AGENCY123").
 * - customerId (string): The unique identifier of the customer (e.g., "99").
 * - customerFullname (string): The full name of the customer (e.g., "Tom Hatte").
 * - customerEmail (string): The email address of the customer (e.g., "tom.hatte@example.com").
 * - customerPhone (string): The phone number of the customer (e.g., "0412356789").
 * - customerMobile (string): The mobile number of the customer (e.g., "0606060606").
 * - deliveryPaymentMethod (string): The payment method for delivery (e.g., "3").
 * - deliveryType (string): The type of delivery (e.g., "00").
 * - language (string): The language code for the request (e.g., "FR").
 * - orderReference (string): The reference ID of the order (e.g., "ORD123456789").
 * - orderType (string): The type of the order (e.g., "1").
 * - orderTypeSub (string): The subtype of the order (e.g., "1").
 * - pickingSite (string): The picking site for the delivery (e.g., "0").
 * - productFamily (string): The product family code (e.g., "08").
 * - shippingAddress1 (string): The first line of the shipping address (e.g., "12 Rue Test").
 * - shippingAddress2 (string): The second line of the shipping address (optional, e.g., "Appartement 3").
 * - shippingPostcode (string): The postcode of the shipping address (e.g., "59000").
 * - shippingCity (string): The city of the shipping address (e.g., "Lille").
 * - shippingCountryCode (string): The country code of the shipping address (e.g., "FR").
 * - shippmentWeight (int): The weight of the shipment in grams (e.g., 1000).
 * - weight (int): The weight of the package in grams (e.g., 1000).
 * - sensitiveProduct (string): Indicates if the product is sensitive (e.g., "0").
 *
 * Example JSON Request:
 * ```json
 * {
 *     "activationKey": "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v",
 *     "activityCode": "08",
 *     "agencyCode": "AGENCY123",
 *     "customerId": "99",
 *     "customerFullname": "Tom Hatte",
 *     "customerEmail": "tom.hatte@example.com",
 *     "customerPhone": "0412356789",
 *     "customerMobile": "0606060606",
 *     "deliveryPaymentMethod": "3",
 *     "deliveryType": "00",
 *     "language": "FR",
 *     "orderReference": "ORD123456789",
 *     "orderType": "1",
 *     "orderTypeSub": "1",
 *     "pickingSite": "0",
 *     "productFamily": "08",
 *     "shippingAddress1": "12 Rue Test",
 *     "shippingAddress2": "Appartement 3",
 *     "shippingPostcode": "59000",
 *     "shippingCity": "Lille",
 *     "shippingCountryCode": "FR",
 *     "shippmentWeight": 1000,
 *     "weight": 1000,
 *     "sensitiveProduct": "0"
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to generate and reserve a shipping label for home delivery in a B2C context.
 * - Provide all required customer and shipping details to ensure successful label generation.
 *
 * Notes:
 * - The `activationKey` is mandatory and must correspond to an authorized user.
 * - Ensure the `shippingAddress1`, `shippingPostcode`, and `shippingCity` fields are correctly populated to avoid errors.
 * - The `sensitiveProduct` field indicates whether additional handling is required for the package.
 *
 * @since 1.0.0
 */
class WP_RC_B2C_Home_Place_Advertisement extends WP_RC_Place_Advertisement_Request {

    // NEWS 2025 03 28
    //      "activationKey" : "{{activationKey}}", // mandatory
    //       "activityCode": "08", // contant value
    //       "customerId": "{{DATA_CLT_customerId}}", // mandatory
    //       "customerFullname": "{{DATA_CLT_firstname}} {{DATA_CLT_lastname}}", // mandatory
    //       "customerEmail": "{{DATA_CLT_email}}", // mandatory
    //       "customerPhone": "{{DATA_CLT_phoneNumber}}", // mandatory but can be empty
    //       "customerMobile": "{{DATA_CLT_mobileNumber}}", // mandatory but can be empty
    //       "deliveryPaymentMethod": "3", // constant value
    //       "deliveryType": "00", // constant value
    //       "language": "FR", // constant value
    //       "orderReference": "{{DATA_CLT_orderId}}",  // mandatory
    //       "orderType": "1", // constant value
    //       "orderTypeSub": "1", // constant value
    //       "pickingSite": "0", // cosntant value
    //       "productFamily": "08", // constant value
    //       "shippingAddress1": "{{DATA_CLT_address1}}", // mandatory same as customer datas
    //       "shippingAddress2": "{{DATA_CLT_address2}}",// mandatory same as customer datas
    //       "shippingPostcode": "{{DATA_CLT_postcode}}",// mandatory same as customer datas
    //       "shippingCity": "{{DATA_CLT_city}}",// mandatory same as customer datas
    //       "shippmentWeight": "1000", // mandatory total weight of the order
    //       "shippingCountryCode": "{{DATA_CLT_countryCode}}", // mandatory same as customer datas
    //       "sensitiveProduct": "0", // constant value
    //       "weight": "1000", // mandatory weight of the package
    //       "digicode": "{{DATA_CLT_digicode}}", // optional
    //       "floor": "{{DATA_CLT_floor}}", // optional
    //       "housingType": "{{DATA_CLT_housing}}", // optional; 0 = house and 1 = apartment
    //       "homePlus": "{{DATA_CLT_homePlus}}", // optional. 0 = false and  1 = true,
    //       "urgent": "{{DATA_CLT_urgent}}", // optional. 0 = false and  1 = true,
    //       "prestations": {{DATA_CLT_prestations}}  // Optional. It's use for home and home plus
    //          '1' => 'cpSchedule',
    //          '3' => 'cpDeliveryOnTheFloor',
    //          '4' => 'cpDeliveryAtTwo',
    //          '5' => 'cpTurnOnHomeAppliance',
    //          '6' => 'cpMountFurniture',
    //          '7' => 'cpNonStandard',
    //          '8' => 'cpUnpacking',
    //           '9' => 'cpEvacuationPackaging',
    //          '10' => 'cpRecovery',
    //          '11' => 'cpDeliveryDesiredRoom',
    //           '18' => 'cpDeliveryEco',

    const DIGICODE = 'digicode';
    const FLOOR = 'floor';
    const HOUSING_TYPE = 'housingType';
    const LIFT = 'lift';
    const URGENT = 'urgent';
    const HOME_PLUS = 'homePlus';

    const PRESTATIONS = 'prestations';
    private $specific_mandatory_params = array(
        self::ACTIVATION_KEY,
        self::CUSTOMER_ID,
        self::CUSTOMER_FULLNAME,
        self::CUSTOMER_EMAIL,
        self::CUSTOMER_PHONE,
        self::CUSTOMER_MOBILE,
        self::ORDER_REFERENCE,
        self::SHIPPING_ADDRESS_1,
        self::SHIPPING_ADDRESS_2,
        self::SHIPPING_POSTCODE,
        self::SHIPPING_CITY,
        self::SHIPPING_COUNTRY_CODE,
        self::SHIPPMENT_WEIGHT,
        self::WEIGHT,
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
            self::ACTIVITY_CODE => '08',
            self::DELIVERY_PAYMENT_METHOD => '3',
            self::DELIVERY_TYPE => '00',
            self::LANGUAGE => 'FR',
            self::ORDER_TYPE => '1',
            self::ORDER_TYPE_SUB => '1',
            self::PICKING_SITE => '0',
            self::PRODUCT_FAMILY => '08',
            self::SENSITIVE_PRODUCT => '0',

        );
    }

    /**
     * 02 - B2C - Réservation d'étiquette - Domicile
     * /api/package/placeAdvertisement
     *
     * Params
     * AGENCY_CODE,            // Code de l'agence
     * CUSTOMER_ID,           // ID du client
     * CUSTOMER_FULLNAME,     // Nom complet du client
     * CUSTOMER_EMAIL,        // Email du client
     * CUSTOMER_PHONE,        // Numéro de téléphone du client
     * CUSTOMER_MOBILE,       // Numéro de mobile du client
     * ORDER_REFERENCE,       // Référence de commande
     * SHIPPING_ADDRESS_1,    // Adresse de livraison ligne 1
     * SHIPPING_ADDRESS_2,    // Adresse de livraison ligne 2 (facultatif)
     * SHIPPING_POSTCODE,     // Code postal de livraison
     * SHIPPING_CITY,         // Ville de livraison
     * SHIPPING_COUNTRY_CODE, // Code pays de livraison
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
