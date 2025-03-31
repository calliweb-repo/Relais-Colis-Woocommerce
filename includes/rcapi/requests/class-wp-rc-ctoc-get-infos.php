<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object for 05 - C2C - Professional Client Balance Retrieval.
 *
 * This class represents a request object for the "Professional Client Balance Retrieval" operation
 * in the WP_Relais_Colis API. It handles the necessary parameters to securely retrieve the balance
 * information of a professional client in the C2C context.
 *
 * Example Parameters:
 * - hash (string): The hash token used for secure authentication in the C2C context (e.g., "sEcReTtOkEn123").
 *
 * Example JSON Request:
 * ```json
 * {
 *     "hash": "sEcReTtOkEn123"
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to fetch the balance information of a professional client in the C2C system.
 * - The hash token (`hash`) ensures secure communication and authentication for the operation.
 *
 * Notes:
 * - The `hash` parameter is mandatory and must be valid for the client requesting the balance.
 * - Ensure that the hash token is kept secure and is not exposed to unauthorized access.
 * - This operation is specific to the C2C context and is tailored for professional clients using the system.
 *
 * @since 1.0.0
 */
class WP_RC_C2C_Get_Infos extends WP_Relais_Colis_Request {

    const C2C_HASHTOKEN = 'hash';

    private $mandatory_params = array(
        self::C2C_HASHTOKEN,
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
        $this->path = 'api/customer/getinfos';

        $c2c_hashtoken = get_option( WC_RC_Shipping_Constants::OPTION_C2C_HASH_TOKEN );

        global $wp_version;
        $this->data = array(
            self::C2C_HASHTOKEN => $c2c_hashtoken,
        );

        // No params

        $this->validate();

        WP_Log::debug( __METHOD__, [ 'method' => $this->method, 'path' => $this->path, 'post_data' => $this->data ], 'relais-colis-woocommerce' );
        $this->data = json_encode( $this->data );
    }
}
