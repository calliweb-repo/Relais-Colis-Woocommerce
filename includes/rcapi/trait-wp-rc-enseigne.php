<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;

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
 *     - <address1>, <address2>, <postcode>, <city>, <agency_code> (string): Address and agency details.
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
     * Get a value from rc_configuration by constant key.
     *
     * @param string $constant The constant key from WC_RC_Shipping_Constants.
     * @return mixed|null The value if found, otherwise null.
     */
    private function get_rc_value( $constant ) {

        return $this->rc_configuration->{$constant} ?? null;
    }

    /**
     * Get a boolean value from rc_configuration by constant key.
     *
     * @param string $constant The constant key from WC_RC_Shipping_Constants.
     * @return bool
     */
    private function get_rc_boolean( $constant ) {

        return filter_var( $this->rc_configuration->{$constant} ?? false, FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Get an integer value from rc_configuration by constant key.
     *
     * @param string $constant The constant key from WC_RC_Shipping_Constants.
     * @return int|null
     */
    private function get_rc_int( $constant ) {

        return isset( $this->rc_configuration->{$constant} ) ? absint( $this->rc_configuration->{$constant} ) : null;
    }

    // Methods rewritten to use constants

    public function get_id() {
        return $this->get_rc_int( WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_BY );
    }

    public function get_ens_name() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_NOM );
    }

    public function get_ens_id() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID );
    }

    public function get_ens_id_light() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID_LIGHT );
    }

    public function use_id_ens() {
        return $this->get_rc_boolean( WC_RC_Shipping_Constants::CONFIGURATION_USEIDENS );
    }

    public function is_active() {
        return $this->get_rc_boolean( WC_RC_Shipping_Constants::CONFIGURATION_ACTIVE );
    }

    public function get_livemapping_api() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_API );
    }

    public function get_livemapping_pid() {
        return $this->get_rc_int( WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_PID );
    }

    public function get_livemapping_key() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_KEY );
    }

    public function get_address1() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE1 );
    }

    public function get_address2() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE2 );
    }

    public function get_postcode() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_POSTAL_CODE );
    }

    public function get_city() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_CITY );
    }

    public function get_agency_code() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_AGENCY_CODE );
    }

    public function get_return_version() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_RETURN_VERSION );
    }

    public function get_return_login() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_RETURN_LOGIN );
    }

    public function get_return_pass() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_RETURN_PASS );
    }

    public function get_return_site() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_RETURN_SITE );
    }

    public function get_folder() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_FOLDER );
    }

    public function get_activation_key() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_ACTIVATION_KEY );
    }

    public function get_created_at() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_CREATED_AT );
    }

    public function get_updated_at() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_AT );
    }

    public function get_updated_by() {
        return $this->get_rc_int( WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_BY );
    }

    public function get_xeett() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_BY );
    }

    public function get_options() {
        return isset( $this->rc_configuration->options->entry )
            ? json_decode( json_encode( $this->rc_configuration->options->entry ), true )
            : [];
    }

    public function get_modules() {
        return isset( $this->rc_configuration->modules->entry )
            ? json_decode( json_encode( $this->rc_configuration->modules->entry ), true )
            : [];
    }

    public function get_osm_live_mapping_key() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_OSM_LIVEMAPPING_KEY );
    }

    public function get_osm_live_mapping_ens() {
        return $this->get_rc_value( WC_RC_Shipping_Constants::CONFIGURATION_OSM_LIVEMAPPING_ENS );
    }
}
