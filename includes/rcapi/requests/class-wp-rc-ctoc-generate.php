<?php

namespace RelaisColisWoocommerce\RCAPI;

defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object for 03 - C2C - Label Printing.
 *
 * This class represents a request object for the Customer-to-Customer (C2C) "Label Printing" operation in the WP_Relais_Colis API.
 * It manages the parameters required to generate and retrieve a shipping label in a specified format for C2C shipments.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 * - format (string): The format of the label to be printed (e.g., "A5", "A4").
 * - pdf (string): The ID of the label to be printed (e.g., "4H013000239501").
 *
 * Example JSON Request:
 * ```json
 * {
 *     "activationKey": "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v",
 *     "format": "A5",
 *     "pdf": "4H013000239501"
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to generate and retrieve shipping labels for Customer-to-Customer shipments.
 * - The `format` parameter allows specifying the desired size (e.g., A5, A4) of the printed label.
 * - The `pdf` parameter is used to specify the ID of the label to be retrieved and printed.
 *
 * Notes:
 * - Ensure the `activationKey` is valid and authorized for the request.
 * - The `pdf` parameter must be a valid label ID that exists in the system.
 * - The format specified must align with the sizes supported by the API.
 * - This operation is specifically designed for C2C use cases and differs from B2C label generation in scope.
 *
 * @since 1.0.0
 */
class WP_RC_C2C_Generate extends WP_RC_Generate {

    const ETIQUETTE1 = 'etiquette1';

    private $specific_mandatory_params = array(
        self::ETIQUETTE1,
    );

    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    protected function get_specific_mandatory_params() {

        return $this->specific_mandatory_params;
    }
}
