<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_C2C_Get_Packages_Price API request object for 05 - C2C - Récupération du prix d'un colis
 *
 * This class represents a request object for the "Get Packages Price" operation
 * in the WP_Relais_Colis API. It handles the necessary parameters to securely retrieve
 * the pricing information for packages based on their weight in the C2C context.
 *
 * Example Parameters:
 * - hash_token (string): The hash token used for secure authentication in the C2C context (e.g., "sEcReTtOkEn123").
 * - packagesWeight (array): An array of package weights in grams (e.g., [2000]).
 *
 * Example JSON Request:
 * ```json
 * {
 *     "hash_token": "sEcReTtOkEn123",
 *     "packagesWeight": [
 *         2000
 *     ]
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to retrieve pricing information for one or more packages in the C2C system.
 * - The hash token (`hash_token`) ensures secure communication and authentication for the operation.
 * - The `packagesWeight` array allows specifying the weight of each package for which pricing is required.
 *
 * Notes:
 * - The `hash_token` parameter is mandatory and must be valid for the client requesting the pricing information.
 * - The `packagesWeight` array must include valid package weights in grams, and at least one weight must be specified.
 * - Ensure that the hash token is kept secure and is not exposed to unauthorized access.
 * - This operation is specific to the C2C context and is tailored for professional clients using the system.
 *
 * @since 1.0.0
 */
class WP_RC_C2C_Get_Packages_Price extends WP_Relais_Colis_Request {

    const C2C_HASHTOKEN = 'hash_token';
    const PACKAGES_WEIGHT = 'packagesWeight';

    private $mandatory_params = array(
        self::C2C_HASHTOKEN,
        self::PACKAGES_WEIGHT,
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
        $this->path = 'api/package/getPackagesPrice';

        $c2c_hashtoken = get_option( Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param().'_C2C_hashToken' );

        $this->data = array(
            self::C2C_HASHTOKEN => $c2c_hashtoken,
        );

        // Merge with params
        $this->data = array_merge( $this->data, $params );

        $this->validate();

        // Tips specific to RC API
//        $post_data = array( $this->data );
        $post_data = $this->data;

        WP_Log::debug( __METHOD__, [ 'method' => $this->method, 'path' => $this->path, 'post_data' => $this->data ], 'relais-colis-woocommerce' );
        $this->data = json_encode( $post_data );
    }
}
