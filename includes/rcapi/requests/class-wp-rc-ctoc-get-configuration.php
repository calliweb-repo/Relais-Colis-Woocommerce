<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object.
 *
 * This class represents a request object for the WP_Relais_Colis API.
 * It encapsulates all the necessary parameters and metadata required
 * to interact with the Relais Colis system, including activation keys,
 * module details, and CMS information.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for authentication (e.g., "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v").
 * - C2C_hashToken (string): The hash token used for secure operations in the C2C context (e.g., "sEcReTtOkEn123").
 * - moduleName (string): The name of the module interacting with the Relais Colis system (e.g., "relais colis").
 * - moduleVersion (string): The version of the module (e.g., "1.0.0").
 * - cmsName (string): The name of the CMS platform (e.g., "WordPress").
 * - cmsVersion (string): The version of the CMS platform (e.g., "6.1.1").
 *
 * Example JSON Request:
 * ```json
 * {
 *     "activationKey": "fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v",
 *     "C2C_hashToken": "sEcReTtOkEn123",
 *     "moduleName": "relais colis",
 *     "moduleVersion": "1.0.0",
 *     "cmsName": "WordPress",
 *     "cmsVersion": "6.1.1"
 * }
 * ```
 *
 * Example Usage:
 * - Use this class to initiate API requests with the Relais Colis system for operations such as label reservations, tracking, or module setup.
 * - Ensure that the module details (`moduleName`, `moduleVersion`, `cmsName`, `cmsVersion`) match the specifications of your integration.
 *
 * Notes:
 * - The `activationKey` is mandatory for all interactions with the Relais Colis API.
 * - The `C2C_hashToken` is specific to operations in the C2C (Customer-to-Customer) context.
 * - Module and CMS details provide context for the environment in which the API is being used, ensuring compatibility and traceability.
 * - Keep the `activationKey` and `C2C_hashToken` secure to avoid unauthorized access to the API.
 *
 * @since 1.0.0
 */
class WP_RC_C2C_Get_Configuration extends WP_Relais_Colis_Request {

    const ACTIVATION_KEY = 'activationKey';
    const MODULE_NAME = 'moduleName';
    const MODULE_VERSION = 'moduleVersion';
    const CMS_NAME = 'cmsName';
    const CMS_VERSION = 'cmsVersion';
    const C2C_HASHTOKEN = 'C2C_hashToken';

    private $mandatory_params = array(
        self::ACTIVATION_KEY,
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
     * 01 - C2C - Récupération du compte pro
     * /api/enseigne/getConfiguration
     *
     * @since 1.0.0
     *
     * @param array $params optional parameters
     * @return mixed
     */
    public function prepare_request( array $params=null ) {

        $this->method = 'POST';
        $this->path = 'api/enseigne/getConfiguration';

        $activationKey = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );
        $c2c_hashtoken = get_option( WC_RC_Shipping_Constants::OPTION_C2C_HASH_TOKEN );

        global $wp_version;
        $this->data = array(
            self::ACTIVATION_KEY => $activationKey,
            self::C2C_HASHTOKEN => $c2c_hashtoken,
            self::MODULE_NAME => Relais_Colis_Woocommerce_Loader::instance()->get_name(),
            self::MODULE_VERSION => Relais_Colis_Woocommerce_Loader::instance()->get_version(),
            self::CMS_NAME => Relais_Colis_Woocommerce_Loader::CMS_WORDPRESS,
            self::CMS_VERSION => $wp_version,
        );

        // No params

        $this->validate();

        WP_Log::debug( __METHOD__, [ 'method' => $this->method, 'path' => $this->path, 'post_data' => $this->data ], 'relais-colis-woocommerce' );
        $this->data = json_encode( $this->data );
    }
}
