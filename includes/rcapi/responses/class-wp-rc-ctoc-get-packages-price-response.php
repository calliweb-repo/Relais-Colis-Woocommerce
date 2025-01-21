<?php

namespace RelaisColisWoocommerce\RCAPI;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_C2C_Get_Packages_Price_Response
 *
 * Class used to manage the response for retrieving the price of a package in the C2C context.
 *
 * Example XML Response Format:
 *
 * This response provides a simplified structure containing a single entry representing the price of the package.
 * The response is structured as follows:
 *
 * Root Element:
 * - <result>
 *     - Contains the main container for the response data.
 *
 * Sub-elements:
 * 1. <entry>
 *     - Encapsulates the price of the package as a string.
 *
 *     Attributes of <entry>:
 *     - Value (string/float): The price of the package, which can be parsed as a float for numeric operations.
 *
 * Example:
 * ```xml
 * <?xml version="1.0" encoding="UTF-8"?>
 * <result>
 *     <entry>5.63</entry>
 * </result>
 * ```
 *
 * Details:
 * - The <entry> element contains a single value representing the price of the package.
 * - This structure is minimal, often used for returning numeric results or key data points.
 *
 * Example Usage:
 * - Parse this XML to extract the value inside the <entry> element.
 * - Convert the value to a float for further calculations or display.
 *
 * Expected Output:
 * - Price: 5.63 (as a float).
 *
 * Notes:
 * - Ensure the `<entry>` element exists in the response before attempting to parse its value.
 * - Handle any potential parsing errors if the value is not in the expected numeric format.
 *
 * @since 1.0.0
 */
class WP_RC_C2C_Get_Packages_Price_Response extends WP_Relais_Colis_Response {

    private $mandatory_properties = array(
        'entry' => 'string',
    );

    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    protected function get_mandatory_properties() {

        return $this->mandatory_properties;
    }
}
