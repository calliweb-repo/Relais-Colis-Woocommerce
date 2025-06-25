<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Bulk_Generate API request object for 03 - Bulk Label Printing.
 *
 * This class represents a request object for the "Bulk Label Printing" operation in the WP_Relais_Colis API.
 * It handles the necessary parameters to generate and print multiple shipping labels in a specified format.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 * - format (string): The format of the labels to be printed (e.g., "A5", "A4").
 * - etiquette1 (string): The ID of the first label to be printed (e.g., "12345").
 * - etiquette2 (string): The ID of the second label to be printed (e.g., "67890").
 * - ... Additional etiquettes can be dynamically added as required.
 *
 * Example JSON Request:
 * ```json
 * {
 *     "activationKey": "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v",
 *     "format": "A5",
 *     "etiquette1": "12345",
 *     "etiquette2": "67890"
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to generate shipping labels in bulk for multiple shipments.
 * - Supports a specified format for the labels (e.g., A5 or A4) to meet operational requirements.
 * - Labels are identified by their IDs, which are submitted in the request.
 *
 * Notes:
 * - Ensure the `activationKey` is valid and corresponds to an authorized user.
 * - The `format` parameter must match one of the supported label sizes in the API.
 * - Additional etiquettes can be included by dynamically extending the request (e.g., `etiquette3`, `etiquette4`, etc.).
 * - This operation is designed for batch processing, making it suitable for handling multiple shipments efficiently.
 *
 * @since 1.0.0
 */

class WP_RC_Bulk_Generate extends WP_RC_Generate {

    const ETIQUETTE = 'etiquette';

    /**
     * Template Method used to get specific mandatory properties
     * @return array list of mandatory params
     */
    protected function get_specific_mandatory_params() {

        return array();
    }

    /**
     * 03 - Impression d'étiquettes en masse
     * /etiquette/generate
     *
     * Params
     * format,
     * etiquette1
     * etiquette2
     * ...
     * etiquetteN
     *
     * @since 1.0.0
     *
     * @param array $params optional parameters
     * @return mixed
     */
    public function prepare_request( array $params=null ) {

        parent::prepare_request( $params );
    }
}
