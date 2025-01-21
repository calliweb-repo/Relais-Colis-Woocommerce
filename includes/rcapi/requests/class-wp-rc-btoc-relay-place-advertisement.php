<?php

namespace RelaisColisWoocommerce\RCAPI;

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
 * - activityCode (string): The activity code for the operation (e.g., "05").
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
 * - productFamily (string): The product family code (e.g., "08").
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
 *     "activationKey": "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v",
 *     "activityCode": "05",
 *     "agencyCode": "AGENCY123",
 *     "customerId": "99",
 *     "customerFullname": "Tom Hatte",
 *     "customerEmail": "tom.hatte@example.com",
 *     "customerPhone": "0412356789",
 *     "customerMobile": "0606060606",
 *     "deliveryPaymentMethod": "3",
 *     "deliveryType": "00",
 *     "language": "FR",
 *     "orderType": "1",
 *     "orderTypeSub": "1",
 *     "pickingSite": "0",
 *     "productFamily": "08",
 *     "pseudoRvc": "RVC123",
 *     "orderReference": "ORD123456789",
 *     "sensitiveProduct": "0",
 *     "shippingAddress1": "12 Rue Example",
 *     "shippingAddress2": "Bâtiment A",
 *     "shippingPostcode": "59000",
 *     "shippingCity": "Lille",
 *     "shippingCountryCode": "FR",
 *     "shippmentWeight": 1000,
 *     "weight": 1000,
 *     "xeett": "I4040"
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

    const PSEUDO_RVC = 'pseudoRvc';
    const XEETT = 'xeett';

    private $specific_mandatory_params = array(
        self::PSEUDO_RVC,
        self::XEETT,
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
            self::ACTIVITY_CODE => '05',
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

        parent::prepare_request( $params );
    }
}
