<?php

namespace RelaisColisWoocommerce\RCAPI;

defined( 'ABSPATH' ) or exit;

/**
 * Trait Relais Colis Enseigne
 * @see https://www.php.net/manual/fr/language.oop5.traits.php
 *
 * This trait provides methods to access all data within the <result> element of the RC Configuration XML response
 *
 * Attributes:
 * 1. <options>
 *     - A list of available delivery options.
 *     - Each delivery option is represented by an <entry> element.
 *
 *     Attributes of <entry> under <options>:
 *     - <id> (int): Unique identifier for the delivery option.
 *     - <name> (string): The display name of the delivery option (wrapped in <![CDATA[]]> for special characters).
 *     - <value> (string): Internal code or identifier for the delivery option (wrapped in <![CDATA[]]>).
 *     - <active> (boolean): Indicates if the option is currently active.
 *
 *     Example:
 *     <options>
 *         <entry>
 *             <id>1</id>
 *             <name><![CDATA[Livraison en point relais]]></name>
 *             <value><![CDATA[rc_delivery]]></value>
 *             <active>true</active>
 *         </entry>
 *     </options>
 *
 * 2. <modules>
 *     - This element is empty in this response but reserved for future expansion or module data.
 *
 * 3. Root-level Attributes:
 *     - <id> (int): General identifier for the configuration or response.
 *     - <ens_name> (string): Name of the service or configuration (wrapped in <![CDATA[]]>).
 *     - <ens_id> (string): Identifier of the enseigne (wrapped in <![CDATA[]]>).
 *     - <ens_id_light> (string): Lightweight identifier (wrapped in <![CDATA[]]>).
 *     - <active> (boolean): Indicates if the overall configuration is active.
 *     - <useidens> (boolean): Determines whether identifiers are used in the configuration.
 *     - <livemapping_api>, <livemapping_pid>, <livemapping_key> (string/int): Mapping and API configuration data.
 *     - <return_version>, <return_login>, <return_pass> (string): Return-related configuration details.
 *     - <folder> (string): Name of the folder associated with the configuration.
 *     - <address1>, <postcode>, <city>, <agency_code> (string): Address and agency details.
 *     - <return_site> (string): Return site identifier.
 *     - <activation_key> (string): Activation key for the configuration.
 *     - <created_at>, <updated_at> (datetime): Timestamps indicating creation and last update times.
 *     - <updated_by> (int): Identifier of the user who last updated the configuration.
 *
 * Example of RC Configuration XML response:
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
 *     <modules>
 *         <entry>
 *             <id>46</id>
 *             <module_name><![CDATA[relais colis]]></module_name>
 *             <module_version><![CDATA[1.0.9]]></module_version>
 *             <cms_name><![CDATA[Prestashop]]></cms_name>
 *             <cms_version><![CDATA[1.6.1.16]]></cms_version>
 *             <created_at><![CDATA[2017-11-24T12:01:10+00:00]]></created_at>
 *             <updated_at><![CDATA[2017-11-24T12:01:10+00:00]]></updated_at>
 *             <updated_by>0</updated_by>
 *         </entry>
 *         <entry>
 *             <id>742</id>
 *             <module_name><![CDATA[relais colis for Prestashop]]></module_name>
 *             <module_version><![CDATA[3.1.1]]></module_version>
 *             <cms_name><![CDATA[Prestashop]]></cms_name>
 *             <cms_version><![CDATA[8.1.3]]></cms_version>
 *             <created_at><![CDATA[2025-01-02T13:14:45+00:00]]></created_at>
 *             <updated_at><![CDATA[2025-01-02T13:14:45+00:00]]></updated_at>
 *             <updated_by>0</updated_by>
 *         </entry>
 *     </modules>
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
 *     <postcode><![CDATA[59000]]></postcode>
 *     <city><![CDATA[lille]]></city>
 *     <agency_code><![CDATA[P9]]></agency_code>
 *     <return_site><![CDATA[00]]></return_site>
 *     <activation_key><![CDATA[fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v]]></activation_key>
 *     <created_at><![CDATA[2023-05-25T08:37:32+00:00]]></created_at>
 *     <updated_at><![CDATA[2024-11-18T15:08:19+00:00]]></updated_at>
 *     <updated_by>3</updated_by>
 * </result>
 *
 * Notes:
 * - The <options> section provides a flexible list of delivery options, each with its own unique ID and metadata.
 * - The <modules> section is currently empty but can be used for additional functionality.
 * - Metadata such as `livemapping_api` and `activation_key` can be used for API or configuration-related purposes.
 *
 * @since 1.0.0
 */
trait WP_RC_Enseigne {

    private $rc_configuration = null;

    /**
     * Get the ID of the result.
     *
     * @return int|null
     */
    public function get_id() {
        return $this->rc_configuration->id ?? null;
    }

    /**
     * Get the name of the enseigne.
     *
     * @return string|null
     */
    public function get_ens_name() {
        return $this->rc_configuration->ens_name ?? null;
    }

    /**
     * Get the enseigne ID.
     *
     * @return string|null
     */
    public function get_ens_id() {
        return $this->rc_configuration->ens_id ?? null;
    }

    /**
     * Get the lightweight enseigne ID.
     *
     * @return string|null
     */
    public function get_ens_id_light() {
        return $this->rc_configuration->ens_id_light ?? null;
    }

    /**
     * Use ID ens
     *
     * @return boolean
     */
    public function use_id_ens() {

        return filter_var( $this->rc_configuration->useidens ?? false, FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Check if the enseigne is active.
     *
     * @return bool
     */
    public function is_active() {

        return filter_var( $this->rc_configuration->active ?? false, FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Get all options.
     *
     * Extracts all option entries from the <options> element in the XML response.
     *
     * Each option contains the following fields:
     * - id (int): The unique identifier of the option.
     * - name (string): The display name of the option (e.g., "Livraison en point relais").
     * - value (string): The internal code or identifier for the option (e.g., "rc_delivery").
     * - active (bool): Indicates whether the option is active.
     *
     * Example of the returned array:
     * [
     *     [
     *         'id' => 1,
     *         'name' => 'Livraison en point relais',
     *         'value' => 'rc_delivery',
     *         'active' => true
     *     ],
     *     [
     *         'id' => 2,
     *         'name' => 'Livraison à domicile',
     *         'value' => 'home_delivery',
     *         'active' => true
     *     ],
     *     [
     *         'id' => 3,
     *         'name' => 'Retour Web',
     *         'value' => 'return',
     *         'active' => true
     *     ],
     *     [
     *         'id' => 6,
     *         'name' => 'Relais Max',
     *         'value' => 'rc_max',
     *         'active' => true
     *     ]
     * ]
     *
     * @return array Returns an array of options. Each option is represented as an associative array.
     */
    public function get_options() {

        return isset( $this->rc_configuration->options->entry )
            ? json_decode( json_encode( $this->rc_configuration->options->entry ), true )
            : [];
    }

    /**
     * Get all modules.
     *
     * Extracts all module entries from the <modules> element in the XML response.
     *
     * Each module contains the following fields:
     * - id (int): The unique identifier of the module.
     * - module_name (string): The name of the module.
     * - module_version (string): The version of the module.
     * - cms_name (string): The name of the CMS associated with the module (e.g., Prestashop).
     * - cms_version (string): The version of the CMS.
     * - created_at (string): The creation timestamp of the module (ISO 8601 format).
     * - updated_at (string): The last update timestamp of the module (ISO 8601 format).
     * - updated_by (int): The ID of the user who last updated the module.
     *
     * Example of the returned array:
     * [
     *     [
     *         'id' => 46,
     *         'module_name' => 'relais colis',
     *         'module_version' => '1.0.9',
     *         'cms_name' => 'Prestashop',
     *         'cms_version' => '1.6.1.16',
     *         'created_at' => '2017-11-24T12:01:10+00:00',
     *         'updated_at' => '2017-11-24T12:01:10+00:00',
     *         'updated_by' => 0
     *     ],
     *     [
     *         'id' => 742,
     *         'module_name' => 'relais colis for Prestashop',
     *         'module_version' => '3.1.1',
     *         'cms_name' => 'Prestashop',
     *         'cms_version' => '8.1.3',
     *         'created_at' => '2025-01-02T13:14:45+00:00',
     *         'updated_at' => '2025-01-02T13:14:45+00:00',
     *         'updated_by' => 0
     *     ]
     * ]
     *
     * @return array Returns an array of modules. Each module is represented as an associative array.
     */
    public function get_modules() {

        // Check if <modules>-><entry> exists and convert to an array if present
        return isset($this->rc_configuration->modules->entry)
            ? json_decode(json_encode($this->rc_configuration->modules->entry), true)
            : [];
    }

    /**
     * Get the livemapping API key.
     *
     * @return string|null
     */
    public function get_livemapping_api() {

        return $this->rc_configuration->livemapping_api ?? null;
    }

    /**
     * Get the livemapping PID.
     *
     * @return int|null
     */
    public function get_livemapping_pid() {

        return $this->rc_configuration->livemapping_pid ?? null;
    }

    /**
     * Get the livemapping key.
     *
     * @return string|null
     */
    public function get_livemapping_key() {

        return $this->rc_configuration->livemapping_key ?? null;
    }

    /**
     * Get the first line of the address.
     *
     * @return string|null
     */
    public function get_address1() {

        return $this->rc_configuration->address1 ?? null;
    }

    /**
     * Get the second line of the address.
     *
     * @return string|null
     */
    public function get_address2() {

        return $this->rc_configuration->address2 ?? null;
    }

    /**
     * Get the postcode.
     *
     * @return string|null
     */
    public function get_postcode() {

        return $this->rc_configuration->postcode ?? null;
    }

    /**
     * Get the city.
     *
     * @return string|null
     */
    public function get_city() {

        return $this->rc_configuration->city ?? null;
    }

    /**
     * Get the agency code.
     *
     * @return string|null
     */
    public function get_agency_code() {

        return $this->rc_configuration->agency_code ?? null;
    }

    /**
     * Get the return version.
     *
     * @return string|null
     */
    public function get_return_version() {

        return $this->rc_configuration->return_version ?? null;
    }

    /**
     * Get the return login.
     *
     * @return string|null
     */
    public function get_return_login() {

        return $this->rc_configuration->return_login ?? null;
    }

    /**
     * Get the return password.
     *
     * @return string|null
     */
    public function get_return_pass() {

        return $this->rc_configuration->return_pass ?? null;
    }

    /**
     * Get the return site.
     *
     * @return string|null
     */
    public function get_return_site() {

        return $this->rc_configuration->return_site ?? null;
    }

    /**
     * Get the folder.
     *
     * @return string|null
     */
    public function get_folder() {

        return $this->rc_configuration->folder ?? null;
    }

    /**
     * Get the activation key.
     *
     * @return string|null
     */
    public function get_activation_key() {

        return $this->rc_configuration->activation_key ?? null;
    }

    /**
     * Get the created_at timestamp.
     *
     * @return string|null
     */
    public function get_created_at() {

        return $this->rc_configuration->created_at ?? null;
    }

    /**
     * Get the updated_at timestamp.
     *
     * @return string|null
     */
    public function get_updated_at() {

        return $this->rc_configuration->updated_at ?? null;
    }

    /**
     * Get the updated_by ID.
     *
     * @return int|null
     */
    public function get_updated_by() {

        return $this->rc_configuration->updated_by ?? null;
    }
}
