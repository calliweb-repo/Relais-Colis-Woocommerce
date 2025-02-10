<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;

defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object for 02 - C2C - Label Reservation - Relais.
 *
 * This class represents a request object for the Customer-to-Customer (C2C) "Label Reservation - Relais" operation in the WP_Relais_Colis API.
 * It encapsulates all the necessary parameters for generating a shipping label between a sender (expéditeur) and a recipient (destinataire)
 * for delivery to a Relais pickup point.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 * - hash_token (string): The hash token for secure operations (e.g., "sEcReTtOkEn123").
 *
 * Customer Details:
 * - customerId (string): The unique identifier of the recipient (e.g., "99").
 * - customerFullname (string): The full name of the recipient (e.g., "Tom Hatte").
 * - customerEmail (string): The email address of the recipient (e.g., "tom.hatte@example.com").
 * - customerPhone (string): The phone number of the recipient (e.g., "0412356789").
 * - customerMobile (string): The mobile number of the recipient (e.g., "0606060606").
 *
 * Sender Details (Expéditeur):
 * - address1Expediteur (string): The first line of the sender's address (e.g., "123 Rue Test").
 * - address2Expediteur (string): The second line of the sender's address (optional, e.g., "Appartement 2").
 * - emailExpediteur (string): The sender's email address (e.g., "expediteur@example.com").
 * - cityExpediteur (string): The city of the sender's address (e.g., "Lille").
 * - nameExpediteur (string): The sender's name or company name (e.g., "CompanyName").
 * - phoneExpediteur (string): The sender's phone number (e.g., "0320654789").
 * - postcodeExpediteur (string): The postcode of the sender's address (e.g., "59000").
 *
 * Shipping Details:
 * - orderReference (string): The reference ID of the order (e.g., "ORD123456789").
 * - shippingAddress1 (string): The first line of the recipient's shipping address (e.g., "45 Avenue Example").
 * - shippingAddress2 (string): The second line of the recipient's shipping address (optional, e.g., "Bâtiment B").
 * - shippingPostcode (string): The postcode of the recipient's shipping address (e.g., "59175").
 * - shippingCity (string): The city of the recipient's shipping address (e.g., "Templatemars").
 * - shippingCountryCode (string): The country code of the recipient's shipping address (e.g., "FR").
 * - shippmentWeight (int): The weight of the shipment in grams (e.g., 1000).
 * - weight (int): The weight of the package in grams (e.g., 1000).
 *
 * Additional Parameters:
 * - activityCode (string): The activity code for the operation (e.g., "05").
 * - agencyCode (string): The code of the agency responsible for processing (e.g., "AGENCY123").
 * - deliveryPaymentMethod (string): The payment method for delivery (e.g., "3").
 * - deliveryType (string): The type of delivery (e.g., "00").
 * - language (string): The language code for the request (e.g., "FR").
 * - pickingSite (string): The picking site for the delivery (e.g., "0").
 * - productFamily (string): The product family code (e.g., "08").
 * - orderType (string): The type of the order (e.g., "1").
 * - orderTypeSub (string): The subtype of the order (e.g., "1").
 * - sensitiveProduct (string): Indicates if the product is sensitive (e.g., "0").
 * - xeett (string): The Xeett identifier for the shipment (e.g., "I4040").
 *
 * Example JSON Request:
 * ```json
 * {
 *     "activationKey": "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v",
 *     "hash_token": "sEcReTtOkEn123",
 *     "customerId": "99",
 *     "customerFullname": "Tom Hatte",
 *     "customerEmail": "tom.hatte@example.com",
 *     "customerPhone": "0412356789",
 *     "customerMobile": "0606060606",
 *     "address1Expediteur": "123 Rue Test",
 *     "address2Expediteur": "Appartement 2",
 *     "emailExpediteur": "expediteur@example.com",
 *     "cityExpediteur": "Lille",
 *     "nameExpediteur": "CompanyName",
 *     "phoneExpediteur": "0320654789",
 *     "postcodeExpediteur": "59000",
 *     "orderReference": "ORD123456789",
 *     "shippingAddress1": "45 Avenue Example",
 *     "shippingAddress2": "Bâtiment B",
 *     "shippingPostcode": "59175",
 *     "shippingCity": "Templatemars",
 *     "shippingCountryCode": "FR",
 *     "shippmentWeight": 1000,
 *     "weight": 1000,
 *     "activityCode": "05",
 *     "agencyCode": "AGENCY123",
 *     "deliveryPaymentMethod": "3",
 *     "deliveryType": "00",
 *     "language": "FR",
 *     "pickingSite": "0",
 *     "productFamily": "08",
 *     "orderType": "1",
 *     "orderTypeSub": "1",
 *     "sensitiveProduct": "0",
 *     "xeett": "I4040"
 * }
 * ```
 *
 * Notes:
 * - Ensure the `activationKey` and `hash_token` are valid and authorized for the request.
 * - All required fields must be provided for successful label generation.
 * - This class is specifically designed for C2C label reservation for Relais deliveries.
 *
 * @since 1.0.0
 */
class WP_RC_C2C_Relay_Place_Advertisement extends WP_RC_Place_Advertisement_Request {

    const XEETT = 'xeett';
    const ADDRESS1_EXPEDITEUR = 'address1Expediteur';
    const ADDRESS2_EXPEDITEUR = 'address2Expediteur';
    const EMAIL_EXPEDITEUR = 'emailExpediteur';
    const CITY_EXPEDITEUR = 'cityExpediteur';
    const NAME_EXPEDITEUR = 'nameExpediteur';
    const PHONE_EXPEDITEUR = 'phoneExpediteur';
    const POSTCODE_EXPEDITEUR = 'postcodeExpediteur';
    const HASH_TOKEN = 'hash_token';

    private $specific_mandatory_params = array(
        self::XEETT,
        self::ADDRESS1_EXPEDITEUR,
        self::ADDRESS2_EXPEDITEUR,
        self::EMAIL_EXPEDITEUR,
        self::CITY_EXPEDITEUR,
        self::NAME_EXPEDITEUR,
        self::PHONE_EXPEDITEUR,
        self::POSTCODE_EXPEDITEUR,
        self::HASH_TOKEN,
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

        $c2c_hashtoken = get_option( WC_RC_Shipping_Constants::OPTION_C2C_HASH_TOKEN );

        return array(
            self::ACTIVITY_CODE => '05',
            self::HASH_TOKEN => $c2c_hashtoken,
        );
    }

    /**
     * 02 - C2C - Réservation d'étiquette - Relais
     * /api/package/placeAdvertisement
     *
     * Params
     * AGENCY_CODE,           // Code de l'agence
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
     * XEETT,                 // ID spécifique Xeett
     * ADDRESS1_EXPEDITEUR    // Adresse expediteur ligne 1
     * ADDRESS2_EXPEDITEUR    // Adresse expediteur ligne 2
     * EMAIL_EXPEDITEUR       // E-mail expediteur
     * CITY_EXPEDITEUR        // Ville de l'expediteur
     * NAME_EXPEDITEUR        // Nom de l'expediteur
     * PHONE_EXPEDITEUR       // Téléphone de l'expediteur
     * POSTCODE_EXPEDITEUR    // Code postal de l'expediteur
     *
     * @since 1.0.0
     *
     * @param array $params parameters
     */
    public function prepare_request( array $params=null ) {

        parent::prepare_request( $params );
    }
}
