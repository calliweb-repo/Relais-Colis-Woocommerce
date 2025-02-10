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
 * to interact with the Relais Colis system, such as activation keys,
 * module details, and CMS information.
 *
 * Example Parameters:
 * - activationKey (string): The activation key used for authentication (e.g., fCwdKsMGEAkRK0jrNSVXzAzjJt5qqx6v).
 * - moduleName (string): The name of the module (e.g., "relais colis").
 * - moduleVersion (string): The version of the module (e.g., "1.0.0").
 * - cmsName (string): The name of the CMS platform (e.g., "WordPress").
 * - cmsVersion (string): The version of the CMS platform (e.g., "1.0.0.0").
 *
 * @since 1.0.0
 */

class WP_RC_B2C_Get_Configuration extends WP_Relais_Colis_Request {

    const ACTIVATION_KEY = 'activationKey';
    const MODULE_NAME = 'moduleName';
    const MODULE_VERSION = 'moduleVersion';
    const CMS_NAME = 'cmsName';
    const CMS_VERSION = 'cmsVersion';

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
        $this->path = 'api/enseigne/getConfiguration';

        $activationKey = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );

        global $wp_version;
        $this->data = array(
            self::ACTIVATION_KEY => $activationKey,
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
