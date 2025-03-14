<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Get_Packages_Status API request object for 07 - Récupération des évènements des colis d'une enseigne.
 *
 * This class represents a request object for the "Récupération des évènements des colis d'une enseigne" operation
 * in the WP_Relais_Colis API. It handles the necessary parameter to securely retrieve events or tracking data
 * related to packages associated with a specific enseigne.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for secure authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 *
 * Example JSON Request:
 * ```json
 * {
 *     "activationKey": "{{activationKey}}"
 *      "parcelNumbers" : [
 *          "parcelNumbers1",
 *          "parcelNumbers2"
 *      ]
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to fetch detailed event or tracking data for all packages related to a particular enseigne.
 * - The `activationKey` parameter authenticates the request and ensures only authorized users can access the data.
 *
 * Notes:
 * - The `activationKey` parameter is mandatory and must be kept secure to prevent unauthorized access.
 * - This operation is scoped to retrieve data for packages linked to the enseigne associated with the provided activation key.
 * - Ensure the activation key corresponds to the correct enseigne in the Relais Colis system.
 *
 * @since 1.0.0
 */
class WP_RC_Get_Packages_Status extends WP_Relais_Colis_Request {

    const ACTIVATION_KEY = 'activationKey';
    const PARCEL_NUMBERS = 'parcelNumbers';

    private $mandatory_params = array(
        self::ACTIVATION_KEY,
        self::PARCEL_NUMBERS,
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
     * /api/package/getPackagesStatus
     *
     * @since 1.0.0
     *
     * @param array $params optional parameters
     * @return mixed
     */
    public function prepare_request( array $params=null ) {

        // Le endpoint  /api/package/getDataEvts va être abandonné la semaine prochaine, au profit d'un nouveau qui est :
        // api/package/getPackagesStatus

        $this->method = 'POST';
        $this->path = 'api/package/getPackagesStatus';

        $activationKey = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );

        $this->data = array(
            self::ACTIVATION_KEY => $activationKey,
        );

        $this->data = array_merge( $this->data, $params );

        $this->validate();

        WP_Log::debug( __METHOD__, [ 'method' => $this->method, 'path' => $this->path, 'post_data' => $this->data ], 'relais-colis-woocommerce' );
        $this->data = json_encode( $this->data );
    }
}
