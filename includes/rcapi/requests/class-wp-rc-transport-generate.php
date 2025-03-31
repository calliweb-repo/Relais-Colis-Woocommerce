<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Transport_Generate API request object for 06 - Génération de la lettre de voiture.
 *
 * This class represents a request object for the "Génération de la lettre de voiture" operation
 * in the WP_Relais_Colis API. It handles the necessary parameters to securely generate a transport letter
 * for one or more packages in the Relais Colis system.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for secure authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 * - search_by (string): The search criteria for the operation (e.g., "TODO").
 * - letter_number (string): The identifier for the transport letter or shipment (e.g., "TODO").
 * - colis1 (string): The identifier or reference for the first package (e.g., "TODO").
 * - colis2 (string): The identifier or reference for the second package (optional, e.g., "TODO").
 *
 * Example JSON Request:
 * ```json
 * {
 *     "activationKey": "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v",
 *     "search_by": "order",
 *     "letter_number": "3",
 *     "colis1": "169", // Order id
 *     "colis2": "179"
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to generate a transport letter for multiple packages using a specified `search_by` criteria.
 * - Each package is identified by its unique identifier (e.g., `colis1`, `colis2`).
 * - The `letter_number` parameter represents the main identifier for the transport letter or shipment.
 *
 * Notes:
 * - The `activationKey` parameter is mandatory and ensures secure communication with the API.
 * - The `search_by` parameter defines the type of search to be performed, and must correspond to a valid API-supported criterion.
 * - The `colis1` and `colis2` parameters allow handling multiple packages in a single request.
 * - Additional package identifiers can be added dynamically if required by the API.
 *
 * @since 1.0.0
 */

class WP_RC_Transport_Generate extends WP_Relais_Colis_Request {

    const ACTIVATION_KEY = 'activationKey';
    const SEARCH_BY = 'search_by';
    const LETTER_NUMBER = 'letter_number';
    const COLIS0 = 'colis0';
    const COLIS1 = 'colis1';
    const COLIS2 = 'colis2';
    const COLIS = 'colis';

    private $mandatory_params = array(
        self::ACTIVATION_KEY,
    );

    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    protected function get_mandatory_params() {

        return $this->mandatory_params;
    }

    /**
     * 01 - B2C - Récupération du compte enseigne
     * /api/enseigne/getConfiguration
     *
     * @since 1.0.0
     *
     * @param array $params optional parameters
     * @return mixed
     */
    public function prepare_request( array $params=null ) {

        $this->method = 'POST';
        $this->path = 'transport/generate';

        $activationKey = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );

        //search_by -> la valeur est toujours à "order"
        //letter_number -> valeur fixe à 3
        //colis1 , 2 etc ce sont en fait les référence woocommerce des commandes que l'on veut voir apparaitre sur la lettre de voiture.
        $this->data = array(
            self::ACTIVATION_KEY => $activationKey,
            self::LETTER_NUMBER => '3',
            self::SEARCH_BY => 'order',
        );

        $this->data = array_merge( $this->data, $params );

        $this->validate();

        WP_Log::debug( __METHOD__, [ 'method' => $this->method, 'path' => $this->path, 'post_data' => $this->data ], 'relais-colis-woocommerce' );
        $this->data = json_encode( $this->data );
    }
}
