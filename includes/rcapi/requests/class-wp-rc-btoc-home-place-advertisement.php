<?php

namespace RelaisColisWoocommerce\RCAPI;

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

    // News 2025 03 26
    //Bonjour Ludovic, comme promis je te fait un retour sur les prestations.
    //Comme annoncé lors de notre call, l'annonce d'une commande en Home ou Home + avec le body suivant:
    //[
    //      {
    //            "activationKey" : "{{activationKey}}",
    //            "activityCode": "08",
    //            "agencyCode" : "{{DATA_agencyCode}}",
    //            "customerId": "{{DATA_CLT_customerId}}",
    //            "customerFullname": "{{DATA_CLT_firstname}} {{DATA_CLT_lastname}}",
    //            "customerEmail": "{{DATA_CLT_email}}",
    //            "customerPhone": "{{DATA_CLT_phoneNumber}}",
    //            "customerMobile": "{{DATA_CLT_mobileNumber}}",
    //            "deliveryPaymentMethod": "3",
    //            "deliveryType": "00",
    //            "language": "FR",
    //            "orderReference": "{{DATA_CLT_orderId}}",
    //            "orderType": "1",
    //            "orderTypeSub": "1",
    //            "pickingSite": "0",
    //            "productFamily": "08",
    //            "shippingAddress1": "{{DATA_CLT_address1}}",
    //            "shippingAddress2": "{{DATA_CLT_address2}}",
    //            "shippingPostcode": "{{DATA_CLT_postcode}}",
    //            "shippingCity": "{{DATA_CLT_city}}",
    //            "shippmentWeight": "{{DATA_CLT_total_weight}}", # poids total des différents colis si la commandes en contient plusieurs
    //            "shippingCountryCode": "{{DATA_CLT_countryCode}}",
    //            "sensitiveProduct": "0",
    //            "weight": "{{DATA_CLT_weight}}", # poids du colis en cours

    // NEW --->
    ////// Home+
    //            "digicode": "{{DATA_CLT_digicode}}", # code de 0 à 8 caractères
    //            "floor": "{{DATA_CLT_floor}}",
    //            "housingType": "{{DATA_CLT_housing}}" # valeurs possible "maison" ou "appartement",
    //            "lift": "{{DATA_CLT_lift}}" # présence d'un ascenceur "1" ou "0",
    //            "urgent": "{{DATA_CLT_urgent}}" # valeurs possible "1" ou "0",
    //            "homePlus": "{{DATA_CLT_plus}}", # valeurs possible "1" ou "0"
    /////// les lignes suivantes ne sont utilisés que pour le home+
    //            "cpSchedule": "{{DATA_CLT_schedule}}" # valeurs possible "1" ou "0" (livraison programmée)
    //            "cpDeliveryOnTheFloor": "{{DATA_CLT_onthefloor}}", # valeurs possible "1" ou "0" (livraison sur le palier)
    //            "cpDeliveryAtTwo": "{{DATA_CLT_atTwo}}", # valeurs possible "1" ou "0" (livraison à deux)
    //            "cpTurnOnHomeAppliance": "{{DATA_CLT_turnOn}}", # valeurs possible "1" ou "0" (mise en route)
    //            "cpMountFurniture": "{{DATA_CLT_mount}}", # valeurs possible "1" ou "0" (montage)
    //            "cpNonStandart": "{{DATA_CLT_nonStandart}}", # valeurs possible "1" ou "0" (hors norme)
    //            "cpUnpacking": "{{DATA_CLT_unpacking}}", # valeurs possible "1" ou "0"
    //            "cpEvacuationPackaging": "{{DATA_CLT_evacuation}}", # valeurs possible "1" ou "0"
    //            "cpRecovery": "{{DATA_CLT_recovery}}", # valeurs possible "1" ou "0"
    //            "cpDeliveryDesiredRoom": "{{DATA_CLT_desiredRoom}}", # valeurs possible "1" ou "0"
    //            "cpDeliveryEco": "{{DATA_CLT_eco}}", # valeurs possible "1" ou "0"
    //      }
    //]

    // 26 mars 2025 Pour les changement:
    //Les champs vides ne doivent plus être présents dans le corps de l'appel.
    //housingType indique maintenant 0 pour Maison et 1 pour appartement

    const DIGICODE = 'digicode';
    const FLOOR = 'floor';
    const HOUSING_TYPE = 'housingType';
    const LIFT = 'lift';
    const URGENT = 'urgent';
    const HOME_PLUS = 'homePlus';
    const CP_SCHEDULE = 'cpSchedule';
    const CP_DELIVERY_ON_THE_FLOOR = 'cpDeliveryOnTheFloor';
    const CP_DELIVERY_AT_TWO = 'cpDeliveryAtTwo';
    const CP_TURN_ON_HOME_APPLIANCE = 'cpTurnOnHomeAppliance';
    const CP_MOUNT_FURNITURE = 'cpMountFurniture';
    const CP_NON_STANDART = 'cpNonStandart';
    const CP_UNPACKING = 'cpUnpacking';
    const CP_EVACUATION_PACKAGING = 'cpEvacuationPackaging';
    const CP_RECOVERY = 'cpRecovery';
    const CP_DELIVERY_DESIRED_ROOM = 'cpDeliveryDesiredRoom';
    const CP_DELIVERY_ECO = 'cpDeliveryEco';

    private $specific_mandatory_params = array();

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
            // pour un relais la valeur est "08" et pour un home ou home + "55"
            self::PRODUCT_FAMILY => '55',

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

        parent::prepare_request( $params );
    }
}
