<?php

namespace RelaisColisWoocommerce\RCAPI;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_B2C_Configuration
 *
 * Class used to manage enseigne configuration
 *
 * Example XML Response Format
 *
 * This response provides a simplified structure containing a single entry.
 * The response is structured as follows:
 *
 * Root Element:
 * - <result>
 *     - Contains the main container for the response data.
 *
 * Sub-elements:
 * 1. <entry>
 *     - A single data field encapsulated in a <![CDATA[]]> section.
 *     - Represents a unique identifier or code.
 *
 *     Attributes of <entry>:
 *     - Value (string): Encapsulated within <![CDATA[]]> to allow for special characters.
 *
 * Example:
 * <?xml version="1.0" encoding="UTF-8"?>
 * <result>
 *     <entry>
 *         <![CDATA[4H013000006301]]>
 *     </entry>
 * </result>
 *
 * Details:
 * - The <entry> element contains a single string value.
 * - This structure is minimal, often used for returning identifiers or status codes.
 *
 * Example Usage:
 * - Parse this XML to extract the value inside the <entry> element.
 *
 * Expected Output:
 * - Value: "4H013000006301"
 *
 * @since 1.0.0
 */
class WP_RC_Place_Advertisement_Response extends WP_Relais_Colis_Response {

    private $mandatory_properties = array(
        'entry' => 'string/array',
    );

    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    protected function get_mandatory_properties() {

        return $this->mandatory_properties;
    }
}
