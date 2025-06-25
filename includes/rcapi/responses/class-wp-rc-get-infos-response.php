<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Get_Infos_Response
 *
 * Class used to manage the response for retrieving customer information.
 *
 * Example XML Response Format:
 *
 * This response provides detailed information about a customer's account, including personal details, balance,
 * account status, and account type.
 *
 * Root Element:
 * - <result>
 *     - Contains the main container for the response data.
 *
 * Sub-elements:
 * 1. <id> (int): Unique identifier of the customer (e.g., "529291").
 * 2. <firstname> (string): First name of the customer (e.g., "TestCalliweb").
 * 3. <lastname> (string): Last name of the customer (e.g., "TestingCalli").
 * 4. <email> (string): Email address of the customer (e.g., "testcalliweb@gmail.com").
 * 5. <balance> (float): Current balance of the customer's account (e.g., "979.84").
 * 6. <accountStatus> (string): Status of the customer's account (e.g., "active").
 * 7. <accountType> (string): Type of the customer's account (e.g., "prepaid").
 * 8. <codeEnseigne> (string, optional): Code associated with the enseigne (e.g., empty in this response).
 *
 * Example:
 * ```xml
 * <result>
 *     <id>529291</id>
 *     <firstname>TestCalliweb</firstname>
 *     <lastname>TestingCalli</lastname>
 *     <email>testcalliweb@gmail.com</email>
 *     <balance>979.84</balance>
 *     <accountStatus>active</accountStatus>
 *     <accountType>prepaid</accountType>
 *     <codeEnseigne/>
 * </result>
 * ```
 *
 * Example Usage:
 * - Use this class to parse and manage customer account details retrieved from the API.
 * - Allows retrieval of personal and financial information for further processing or display.
 *
 * Notes:
 * - Ensure that all required fields (`id`, `firstname`, `lastname`, `email`, `balance`, `accountStatus`, `accountType`) are included in the response.
 * - The `<codeEnseigne>` element is optional and may be empty, depending on the context of the request.
 * - Handle the `<balance>` field as a float to correctly represent monetary values.
 *
 * @since 1.0.0
 */
class WP_RC_Get_Infos_Response extends WP_Relais_Colis_Response {

    private $mandatory_properties = [
        WC_RC_Shipping_Constants::INFORMATION_RESULT_ID => 'string',
        WC_RC_Shipping_Constants::INFORMATION_BALANCE => 'string',
        WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_STATUS => 'string',
    ];

    /**
     * Template Method used to get specific mandatory properties.
     *
     * @return array List of mandatory properties.
     */
    protected function get_mandatory_properties() {
        return $this->mandatory_properties;
    }

    /**
     * Get the customer ID.
     *
     * @return int|null Customer ID, or null if not available.
     */
    public function get_id() {
        return $this->response_data->{WC_RC_Shipping_Constants::INFORMATION_RESULT_ID} ?? null;
    }

    /**
     * Get the customer's first name.
     *
     * @return string|null Customer's first name, or null if not available.
     */
    public function get_firstname() {
        return $this->response_data->{WC_RC_Shipping_Constants::INFORMATION_FIRSTNAME} ?? null;
    }

    /**
     * Get the customer's last name.
     *
     * @return string|null Customer's last name, or null if not available.
     */
    public function get_lastname() {
        return $this->response_data->{WC_RC_Shipping_Constants::INFORMATION_LASTNAME} ?? null;
    }

    /**
     * Get the customer's email address.
     *
     * @return string|null Customer's email address, or null if not available.
     */
    public function get_email() {
        return $this->response_data->{WC_RC_Shipping_Constants::INFORMATION_EMAIL} ?? null;
    }

    /**
     * Get the customer's account balance.
     *
     * @return float|null Customer's account balance, or null if not available.
     */
    public function get_balance() {
        return isset( $this->response_data->{WC_RC_Shipping_Constants::INFORMATION_BALANCE} )
            ? (float)$this->response_data->{WC_RC_Shipping_Constants::INFORMATION_BALANCE}
            : null;
    }

    /**
     * Get the customer's account status.
     *
     * @return string|null Customer's account status, or null if not available.
     */
    public function get_account_status() {
        return $this->response_data->{WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_STATUS} ?? null;
    }

    /**
     * Get the customer's account type.
     *
     * @return string|null Customer's account type, or null if not available.
     */
    public function get_account_type() {
        return $this->response_data->{WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_TYPE} ?? null;
    }

    /**
     * Get the enseigne code.
     *
     * @return string|null Enseigne code, or null if not available.
     */
    public function get_code_enseigne() {
        return $this->response_data->{WC_RC_Shipping_Constants::INFORMATION_CODE_ENSEIGNE} ?? null;
    }
}
