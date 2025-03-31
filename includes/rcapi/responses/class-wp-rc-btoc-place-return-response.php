<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_B2C_Place_Return_Response
 *
 * Class used to manage Place return response
 *
 * Example XML Response Format
 *
 * This response provides detailed information about a return request, including delivery options, customer data, and error details.
 * The response is structured as follows:
 *
 * Root Element:
 * - <result>
 *     - Contains the main container for the response data.
 *
 * Sub-elements:
 * 1. <entry>
 *     - Encapsulates all the details of the response, such as enseigne information, customer details, and error metadata.
 *
 * Sub-elements of <entry>:
 * - <id> (int): Unique identifier for the entry.
 * - <enseigne>
 *     - Detailed information about the associated enseigne.
 *
 *     Sub-elements of <enseigne>:
 *     - <options>: List of delivery options, each represented as an <entry>.
 *         - <id> (int): Unique identifier for the delivery option.
 *         - <name> (string): Name of the delivery option (wrapped in <![CDATA[]]>).
 *         - <value> (string): Internal code or value for the delivery option.
 *         - <active> (boolean): Indicates if the option is active.
 *     - <modules>: Currently empty.
 *     - <id> (int): ID of the enseigne.
 *     - <ens_name> (string): Name of the enseigne.
 *     - <ens_id> (string): Identifier of the enseigne.
 *     - <ens_id_light> (string): Lightweight identifier.
 *     - <active> (boolean): Whether the enseigne configuration is active.
 *     - <useidens> (boolean): Flag indicating identifier usage.
 *     - <livemapping_api>, <livemapping_pid>, <livemapping_key> (string/int): API and mapping configuration details.
 *     - <return_version>, <return_login>, <return_pass> (string): Return configuration details.
 *     - <folder> (string): Associated folder name.
 *     - <address1>, <postcode>, <city>, <agency_code> (string): Address and agency details.
 *     - <return_site>, <activation_key> (string): Return site identifier and activation key.
 *     - <created_at>, <updated_at> (datetime): Creation and update timestamps.
 *     - <updated_by> (int): ID of the user who last updated the enseigne configuration.
 *
 * - Additional elements under <entry>:
 *     - <enseigne_id> (int): ID of the enseigne.
 *     - <order_id> (int): Associated order ID.
 *     - <customer_id> (int): Customer ID.
 *     - <customer_fullname>, <customer_phone>, <customer_mobile>, <customer_company> (string): Customer details.
 *     - <customer_address1>, <customer_address2>, <customer_postcode>, <customer_city>, <customer_country> (string): Customer address details.
 *     - <reference> (string): Reference for the return request.
 *     - <response_status> (string): Status of the response (e.g., "Error").
 *     - <error_type>, <error_description> (string): Details about the error, if any.
 *     - <return_number>, <number_cab> (string): Return and cab numbers.
 *     - <limit_date> (datetime): Deadline associated with the return.
 *     - <image_url>, <bordereau_smart_url> (string): URLs for related resources.
 *     - <created_at> (datetime): Timestamp of creation.
 *     - <token> (string): Security token associated with the entry.
 *
 * Example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <result>
 *     <entry>
 *         <id>2</id>
 *         <enseigne>
 *             <options>
 *                 <entry>
 *                     <id>1</id>
 *                     <name><![CDATA[Livraison en point relais]]></name>
 *                     <value><![CDATA[rc_delivery]]></value>
 *                     <active>true</active>
 *                 </entry>
 *             </options>
 *             <id>112</id>
 *             <ens_name><![CDATA[MODULES RC PROD]]></ens_name>
 *         </enseigne>
 *         <enseigne_id>112</enseigne_id>
 *         <order_id><![CDATA[99]]></order_id>
 *         <customer_fullname><![CDATA[TomHatte]]></customer_fullname>
 *         <response_status><![CDATA[Error]]></response_status>
 *         <error_type><![CDATA[InputParameters]]></error_type>
 *         <error_description><![CDATA[Le site de retour [00] n'est pas paramétré.]]></error_description>
 *         <token><![CDATA[YfDF7ze7zkcQxtfPW8rVaeKP4Ec9dUMz]]></token>
 *     </entry>
 * </result>
 *
 * Details:
 * - The <result> element serves as the root container.
 * - The <entry> element encapsulates all response details.
 * - Nested elements provide detailed metadata, customer data, and enseigne configuration.
 *
 * @since 1.0.0
 */
class WP_RC_B2C_Place_Return_Response extends WP_Relais_Colis_Response {

    // Uses Trait Relais Colis Enseigne to allow easy access to enseigne configuration data
    use WP_RC_Enseigne;

    private $mandatory_properties = array(
        //'enseigne' => 'object',
        'order_id' => 'string',
        'customer_id' => 'string',
        'enseigne_id' => 'string',
    );

    /**
     * Build an XML object from the raw response.
     *
     * @param string $raw_response The raw response XML
     * @since 1.0.0
     */
    public function __construct( $raw_response ) {

        // Call parent constructor to handle raw response parsing.
        parent::__construct( $raw_response );

        // Assign 'enseigne' data to the trait's configuration.
        if ( property_exists( $this->response_data, 'entry' ) && property_exists( $this->response_data->entry, 'enseigne' ) ) {

            WP_Log::debug( __METHOD__.' - property_exists entry->enseigne', [], 'relais-colis-woocommerce' );

            $this->rc_configuration = (object)$this->response_data->entry->enseigne;
        }
    }

    /**
     * Validate configuration datas
     *
     * @return bool Trueif data are valid, else false
     */
    public function validate() {

        $validate = true;
        $mandatory_properties = $this->get_mandatory_properties();
        foreach ( $mandatory_properties as $property => $type ) {

            $validate = $validate && $this->check_property( $property, $type, $this->response_data->entry );
            WP_Log::debug( __METHOD__, ['property'=>$property, 'type'=>$type, 'validate'=>($validate?'true':'false')], 'relais-colis-woocommerce' );

            if ( !$validate ) break;
        }
        return $validate;
    }

    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    protected function get_mandatory_properties() {

        return $this->mandatory_properties;
    }
    /**
     * Get the entry ID.
     *
     * @return int|null
     */
    public function get_entry_id() {

        return $this->response_data->entry->id ?? null;
    }

    /**
     * Get the enseigne ID.
     *
     * @return int|null
     */
    public function get_enseigne_id() {

        return $this->response_data->entry->enseigne_id ?? null;
    }

    /**
     * Get the order ID.
     *
     * @return string|null
     */
    public function get_order_id() {

        return $this->response_data->entry->order_id ?? null;
    }

    /**
     * Get the customer ID.
     *
     * @return string|null
     */
    public function get_customer_id() {

        return $this->response_data->entry->customer_id ?? null;
    }

    /**
     * Get the customer full name.
     *
     * @return string|null
     */
    public function get_customer_fullname() {

        return $this->response_data->entry->customer_fullname ?? null;
    }

    /**
     * Get the xeett.
     *
     * @return string|null
     */
    public function get_xeett() {

        return $this->response_data->entry->xeett ?? null;
    }

    /**
     * Get the xeett name.
     *
     * @return string|null
     */
    public function get_xeett_name() {

        return $this->response_data->entry->xeett_name ?? null;
    }

    /**
     * Get the customer phone.
     *
     * @return string|null
     */
    public function get_customer_phone() {

        return $this->response_data->entry->customer_phone ?? null;
    }

    /**
     * Get the customer mobile phone.
     *
     * @return string|null
     */
    public function get_customer_mobile() {

        return $this->response_data->entry->customer_mobile ?? null;
    }

    /**
     * Get the customer company.
     *
     * @return string|null
     */
    public function get_customer_company() {

        return $this->response_data->entry->customer_company ?? null;
    }

    /**
     * Get the customer address line 1.
     *
     * @return string|null
     */
    public function get_customer_address1() {

        return $this->response_data->entry->customer_address1 ?? null;
    }

    /**
     * Get the customer address line 2.
     *
     * @return string|null
     */
    public function get_customer_address2() {

        return $this->response_data->entry->customer_address2 ?? null;
    }

    /**
     * Get the customer postcode.
     *
     * @return string|null
     */
    public function get_customer_postcode() {

        return $this->response_data->entry->customer_postcode ?? null;
    }

    /**
     * Get the customer city.
     *
     * @return string|null
     */
    public function get_customer_city() {

        return $this->response_data->entry->customer_city ?? null;
    }

    /**
     * Get the customer country.
     *
     * @return string|null
     */
    public function get_customer_country() {

        return $this->response_data->entry->customer_country ?? null;
    }

    /**
     * Get the reference.
     *
     * @return string|null
     */
    public function get_reference() {

        return $this->response_data->entry->reference ?? null;
    }

    /**
     * Get the response status.
     *
     * @return string|null
     */
    public function get_response_status() {

        return $this->response_data->entry->response_status ?? null;
    }

    /**
     * Get the error type.
     *
     * @return string|null
     */
    public function get_error_type() {

        return $this->response_data->entry->error_type ?? null;
    }

    /**
     * Get the error description.
     *
     * @return string|null
     */
    public function get_error_description() {

        return $this->response_data->entry->error_description ?? null;
    }

    /**
     * Get the return number.
     *
     * @return string|null
     */
    public function get_return_number() {

        return $this->response_data->entry->return_number ?? null;
    }

    /**
     * Get the cab number.
     *
     * @return string|null
     */
    public function get_number_cab() {

        return $this->response_data->entry->number_cab ?? null;
    }

    /**
     * Get the limit date.
     *
     * @return string|null
     */
    public function get_limit_date() {

        return $this->response_data->entry->limit_date ?? null;
    }

    /**
     * Get the image URL.
     *
     * @return string|null
     */
    public function get_image_url() {

        return $this->response_data->entry->image_url ?? null;
    }

    /**
     * Get the bordereau smart URL.
     *
     * @return string|null
     */
    public function get_bordereau_smart_url() {

        return $this->response_data->entry->bordereau_smart_url ?? null;
    }

    /**
     * Get the created_at timestamp.
     *
     * @return string|null
     */
    public function get_created_at() {

        return $this->response_data->entry->created_at ?? null;
    }

    /**
     * Get the token.
     *
     * @return string|null
     */
    public function get_token() {

        return $this->response_data->entry->token ?? null;
    }

}
