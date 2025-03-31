<?php

namespace RelaisColisWoocommerce\RCAPI;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Place_Return_V3 API request object for 04 - B2C - Return Request V3.
 *
 * This class represents a request object for the B2C "Return Request V3" operation in the WP_Relais_Colis API.
 * It manages the parameters required to initiate a return request for one or more orders in a structured format.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 * - requests (array): A list of return requests, each containing detailed information about the customer, order, and additional prestations.
 *
 * Fields for Each Request:
 * - orderId (string): The unique identifier of the order (e.g., "99").
 * - customerId (string): The unique identifier of the customer (e.g., "99").
 * - customerFullname (string): The full name of the customer (e.g., "Tom Hatte").
 * - xeett (string): The Xeett identifier for the shipment (e.g., "I4040").
 * - xeettName (string): The name associated with the Xeett identifier (e.g., "La Poste").
 * - customerPhone (string): The phone number of the customer (e.g., "0412356789").
 * - customerMobile (string): The mobile number of the customer (e.g., "0606060606").
 * - reference (string): The reference for the return request (e.g., "TE12ST34").
 * - customerCompany (string): The company name of the customer, if applicable (e.g., "WE+").
 * - customerAddress1 (string): The first line of the customer's address (e.g., "12 rue de l'épinoy").
 * - customerAddress2 (string): The second line of the customer's address (optional, e.g., "").
 * - customerPostcode (string): The postcode of the customer's address (e.g., "59175").
 * - customerCity (string): The city of the customer's address (e.g., "Templatemars").
 * - customerCountry (string): The country of the customer's address (e.g., "France").
 * - prestations (string): Additional prestations or services associated with the return (e.g., "1").
 *
 * Example JSON Request:
 * ```json
 * {
 *     "activationKey": "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v",
 *     "requests": [
 *         {
 *             "orderId": "99",
 *             "customerId": "99",
 *             "customerFullname": "Tom Hatte",
 *             "xeett": "I4040",
 *             "xeettName": "La Poste",
 *             "customerPhone": "0412356789",
 *             "customerMobile": "0606060606",
 *             "reference": "TE12ST34",
 *             "customerCompany": "WE+",
 *             "customerAddress1": "12 rue de l'épinoy",
 *             "customerAddress2": "",
 *             "customerPostcode": "59175",
 *             "customerCity": "Templatemars",
 *             "customerCountry": "France",
 *             "prestations": "1"
 *         }
 *     ]
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to create and send return requests for multiple orders in a single API call.
 * - Each request must include the customer's information, order details, and optional prestations.
 *
 * Notes:
 * - The `activationKey` is mandatory and should be valid for the authenticated user.
 * - Ensure that all fields in the `requests` array are properly populated to avoid validation errors.
 * - The `prestations` field is optional and can be used to specify additional services required for the return.
 *
 * @since 1.0.0
 */
class WP_RC_Place_Return_V3 extends WP_RC_Place_Return {

    //           "orderId": "{{DATA_CLT_orderId}}", // mandatory
    //           "customerId": "{{DATA_CLT_customerId}}", // mandatory
    //           "customerFullname": "{{DATA_CLT_firstname}} {{DATA_CLT_lastname}}", // mandatory
    //           "xeett": "{{DATA_CLT_XeettId}}", // mandatory informations of th order relay
    //           "xeettName": "{{DATA_CLT_XeettName}}", // informations of th order relay
    //           "customerPhone": "{{DATA_CLT_phoneNumber}}", // mandatory but can be empty
    //           "customerMobile": "{{DATA_CLT_mobileNumber}}", // mandatory but can be empty
    //           "reference": "{{DATA_CLT_orderId}}", // mandatory
    //           "customerCompany": "{{DATA_CLT_company}}", // mandatory, can be the name of shop
    //           "customerAddress1": "{{DATA_CLT_address1}}", // mandatory
    //           "customerAddress2": "{{DATA_CLT_address2}}", // mandatory but can be empty
    //           "customerPostcode": "{{DATA_CLT_postcode}}",// mandatory
    //           "customerCity": "{{DATA_CLT_city}}", // mandatory
    //           "customerCountry": "FR", // constant value
    //           "prestations": "{{DATA_CLT_prestations}}" // optional

    /**
     * Template Method used to get the specific return path (V2 or V3...)
     * @return mixed
     */
    protected function get_specific_path() {

        return 'api/return/placeReturnV3';
    }

    /**
     * 04 - B2C - Demande de retour V3
     * /api/return/placeReturnV3
     *
     * {
     *     "activationKey" : "{{activationKey}}",
     *     "requests" : [
     *         {
     *             "orderId": "{{DATA_CLT_orderId}}",
     *             "customerId": "{{DATA_CLT_customerId}}",
     *             "customerFullname": "{{DATA_CLT_firstname}} {{DATA_CLT_lastname}}",
     *             "xeett": "{{DATA_CLT_XeettId}}",
     *             "xeettName": "{{DATA_CLT_XeettName}}",
     *             "customerPhone": "{{DATA_CLT_phoneNumber}}",
     *             "customerMobile": "{{DATA_CLT_mobileNumber}}",
     *             "reference": "{{DATA_CLT_orderReference}}",
     *             "customerCompany": "{{DATA_CLT_company}}",
     *             "customerAddress1": "{{DATA_CLT_address1}}",
     *             "customerAddress2": "{{DATA_CLT_address2}}",
     *             "customerPostcode": "{{DATA_CLT_postcode}}",
     *             "customerCity": "{{DATA_CLT_city}}",
     *             "customerCountry": "{{DATA_CLT_country}}",
     *             "prestations": "{{DATA_homePlusPrestations}}"
     *         }
     *     ]
     *     }
     *
     * @since 1.0.0
     *
     * @param array $params parameters
     */
    public function prepare_request( array $params=null ) {

        parent::prepare_request( $params );
    }
}