<?php

namespace RelaisColisWoocommerce\RCAPI;

defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object for 03 - B2C - Label Printing.
 *
 * This class represents a request object for the B2C "Label Printing" operation in the WP_Relais_Colis API.
 * It manages the necessary parameters to retrieve and print a shipping label in a specified format for B2C shipments.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 * - format (string): The format of the label to be printed (e.g., "A5", "A4").
 * - pdf (string): The ID of the label to be printed (e.g., "12345").
 *
 * Example JSON Request:
 * ```json
 * {
 *     "activationKey": "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v",
 *     "format": "A5",
 *     "pdf": "12345"
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to retrieve and print a shipping label for B2C shipments in the specified format (e.g., A5 or A4).
 * - The `pdf` parameter is used to specify the ID of the label to be retrieved and printed.
 *
 * Notes:
 * - Ensure the `activationKey` is valid and corresponds to an authorized user.
 * - The `pdf` parameter must be a valid label ID that exists in the system.
 * - The `format` parameter must match one of the supported label sizes in the API.
 * - This operation is designed specifically for B2C label printing and may differ from other contexts like C2C.
 *
 * @since 1.0.0
 */
class WP_RC_B2C_Generate extends WP_RC_Generate {

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
