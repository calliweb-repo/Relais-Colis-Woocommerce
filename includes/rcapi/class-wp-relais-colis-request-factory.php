<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 *  Request factory.
 *
 * @since 1.0.0
 */
class WP_Relais_Colis_Request_Factory {

    use Singleton;

    /**
     * Generic treatment for a response handling
     *
     * @param string $request_type one of REQUEST_GET_B2C_CONFIGURATION, REQUEST_GET_C2C_CONFIGURATION ...
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|mixed|object|WP_Relais_Colis_Request|null
     * @throws WP_Relais_Colis_API_Exception
     */
    public function get_rc_api_reuest( string $request_type ) {

        WP_Log::debug( __METHOD__, [ 'request_type' => $request_type ], 'relais-colis-woocommerce' );

        if ( empty( $request_type ) ) return null;

        // Build request
        $request = null;
        switch( $request_type ) {
            case WP_Relais_Colis_API::REQUEST_GET_B2C_CONFIGURATION:
                $request = new WP_RC_B2C_Get_Configuration();
                break;
            case WP_Relais_Colis_API::REQUEST_GET_C2C_CONFIGURATION:
                $request = new WP_RC_C2C_Get_Configuration();
                break;
            case WP_Relais_Colis_API::REQUEST_B2C_RELAY_PLACE_ADVERTISEMENT:
                $request = new WP_RC_B2C_Relay_Place_Advertisement();
                break;
            case WP_Relais_Colis_API::REQUEST_B2C_HOME_PLACE_ADVERTISEMENT:
                $request = new WP_RC_B2C_Home_Place_Advertisement();
                break;
            case WP_Relais_Colis_API::REQUEST_C2C_RELAY_PLACE_ADVERTISEMENT:
                $request = new WP_RC_C2C_Relay_Place_Advertisement();
                break;
            case WP_Relais_Colis_API::REQUEST_B2C_GENERATE:
                $request = new WP_RC_B2C_Generate();
                break;
            case WP_Relais_Colis_API::REQUEST_C2C_GENERATE:
                $request = new WP_RC_C2C_Generate();
                break;
            case WP_Relais_Colis_API::REQUEST_BULK_GENERATE:
                $request = new WP_RC_Bulk_Generate();
                break;
            case WP_Relais_Colis_API::REQUEST_B2C_PLACE_RETURN_V2:
                $request = new WP_RC_Place_Return_V2();
                break;
            case WP_Relais_Colis_API::REQUEST_B2C_PLACE_RETURN_V3:
                $request = new WP_RC_Place_Return_V3();
                break;
            case WP_Relais_Colis_API::REQUEST_C2C_GET_INFOS:
                $request = new WP_RC_C2C_Get_Infos();
                break;
            case WP_Relais_Colis_API::REQUEST_C2C_GET_PACKAGES_PRICE:
                $request = new WP_RC_C2C_Get_Packages_Price();
                break;
            case WP_Relais_Colis_API::REQUEST_TRANSPORT_GENERATE:
                $request = new WP_RC_Transport_Generate();
                break;
            case WP_Relais_Colis_API::REQUEST_GET_PACKAGES_STATUS:
                $request = new WP_RC_Get_Packages_Status();
                break;
            default:
                break;
        }
        return $request;
    }
}
