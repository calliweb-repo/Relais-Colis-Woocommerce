<?php

namespace RelaisColisWoocommerce\RCAPI;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Configuration
 *
 * Class used to manage enseigne configuration response
 *
 * Example XML Response Format
 *
 * This response provides information about delivery options, metadata, and return-related configurations.
 * The response is structured as follows:
 *
 * Root Element:
 * - <result>
 *     - Contains the main container for the response data.
 *
 * Sub-elements:
 * 1. <options>
 *     - A list of delivery options available: rc_delivery, home_delivery, return, rc_max, rc_c2c, reset_carrier...
 *     - Each option is encapsulated in an <entry> element.
 *
 *     Attributes of <entry> under <options>:
 *     - <id> (int): The unique identifier for the delivery option.
 *     - <name> (string): The name of the delivery option (wrapped in <![CDATA[]]> for special characters).
 *     - <value> (string): The internal code or value associated with the delivery option.
 *     - <active> (boolean): Indicates whether the option is currently active.
 *
 *     Example:
 *     <options>
 *         <entry>
 *             <id>1</id>
 *             <name><![CDATA[Livraison en point relais]]></name>
 *             <value><![CDATA[rc_delivery]]></value>
 *             <active>true</active>
 *         </entry>
 *         <entry>
 *             <id>2</id>
 *             <name><![CDATA[Livraison à domicile]]></name>
 *             <value><![CDATA[home_delivery]]></value>
 *             <active>true</active>
 *         </entry>
 *     </options>
 *
 * 2. <modules>
 *     - This element is present but empty in this response.
 *
 * 3. Root-level attributes:
 *     - <id> (int): General identifier for the response.
 *     - <ens_name> (string): Name of the service (wrapped in <![CDATA[]]>).
 *     - <ens_id> (string): Identifier of the service.
 *     - <ens_id_light> (string): Lightweight identifier for the service.
 *     - <active> (boolean): Indicates if the overall configuration is active.
 *     - <useidens> (boolean): Flag to determine if an identifier is used.
 *     - <livemapping_api> (string): API mapping key.
 *     - <livemapping_pid> (int): API PID.
 *     - <livemapping_key> (string): API key for live mapping.
 *     - <return_version> (string): Return configuration version.
 *     - <return_login> (string): Login for return configuration (wrapped in <![CDATA[]]>).
 *     - <return_pass> (string): Password for return configuration (wrapped in <![CDATA[]]>).
 *     - <folder> (string): Related folder name.
 *     - <address1> (string): Address associated with the service.
 *     - <address2> (string): Address associated with the service.
 *     - <postcode> (string): Postal code of the address.
 *     - <city> (string): City of the address.
 *     - <agency_code> (string): Agency code associated with the service.
 *     - <return_site> (string): Return site identifier.
 *     - <activation_key> (string): Key for activation.
 *     - <created_at> (datetime): Creation timestamp of the entire configuration.
 *     - <updated_at> (datetime): Last update timestamp of the entire configuration.
 *     - <updated_by> (int): ID of the user who last updated the configuration.
 *
 * Example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <result>
 *     <options>
 *         <entry>
 *             <id>1</id>
 *             <name><![CDATA[Livraison en point relais]]></name>
 *             <value><![CDATA[rc_delivery]]></value>
 *             <active>true</active>
 *         </entry>
 *         <entry>
 *             <id>2</id>
 *             <name><![CDATA[Livraison à domicile]]></name>
 *             <value><![CDATA[home_delivery]]></value>
 *             <active>true</active>
 *         </entry>
 *     </options>
 *     <modules/>
 *     <id>112</id>
 *     <ens_name><![CDATA[MODULES RC PROD]]></ens_name>
 *     <ens_id><![CDATA[4H]]></ens_id>
 *     <ens_id_light><![CDATA[0130]]></ens_id_light>
 *     <active>true</active>
 *     <useidens>false</useidens>
 *     <livemapping_api><![CDATA[20170404133327012519091906]]></livemapping_api>
 *     <livemapping_pid>171334</livemapping_pid>
 *     <livemapping_key><![CDATA[171333]]></livemapping_key>
 *     <return_version><![CDATA[V3]]></return_version>
 *     <return_login><![CDATA[WSret4H]]></return_login>
 *     <return_pass><![CDATA[Wsret4Hprod]]></return_pass>
 *     <folder><![CDATA[Testweplusprepod]]></folder>
 *     <address1><![CDATA[123 rue du test]]></address1>
 *     <address2><![CDATA[123 rue du test]]></address2>
 *     <postcode><![CDATA[59000]]></postcode>
 *     <city><![CDATA[lille]]></city>
 *     <agency_code><![CDATA[P9]]></agency_code>
 *     <return_site><![CDATA[00]]></return_site>
 *     <activation_key><![CDATA[fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v]]></activation_key>
 *     <created_at><![CDATA[2023-05-25T08:37:32+00:00]]></created_at>
 *     <updated_at><![CDATA[2024-11-18T15:08:19+00:00]]></updated_at>
 *     <updated_by>3</updated_by>
 *     <xeett>123</xeett> ???
 * </result>
 *
 * @since 1.0.0
 */
class WP_RC_Get_Configuration_Response extends WP_Relais_Colis_Response {

    // Uses Trait Relais Colis Enseigne to allow easy access to enseigne configuration data
    use WP_RC_Enseigne;

    private $mandatory_properties = array(
        'ens_id' => 'string',
        'active' => 'boolean',
        'ens_name' => 'string',
    );

    /**
     * Build an XML object from the raw response.
     *
     * @since 1.0.0
     * @param string $raw_response The raw response XML
     */
    public function __construct( $raw_response ) {

        parent::__construct( $raw_response );

        // Used by Trait WP_RC_Enseigne
        $this->rc_configuration = $this->response_data;
    }

    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    protected function get_mandatory_properties() {

        return $this->mandatory_properties;
    }
}
