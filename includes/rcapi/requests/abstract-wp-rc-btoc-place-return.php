<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Place_Return generic request
 *
 * @since 1.0.0
 */
abstract class WP_RC_Place_Return extends WP_Relais_Colis_Request {

    const ACTIVATION_KEY = 'activationKey';
    const REQUESTS = 'requests';

    const ORDER_ID = 'orderId';
    const CUSTOMER_ID = 'customerId';
    const CUSTOMER_FULLNAME = 'customerFullname';
    const XEETT = 'xeett';
    const XEETT_NAME = 'xeettName';
    const CUSTOMER_PHONE = 'customerPhone';
    const CUSTOMER_MOBILE = 'customerMobile';
    const CUSTOMER_COMPANY = 'customerCompany';
    const CUSTOMER_ADDRESS1 = 'customerAddress1';
    const CUSTOMER_ADDRESS2 = 'customerAddress2';
    const CUSTOMER_POSTCODE = 'customerPostcode';
    const CUSTOMER_CITY = 'customerCity';
    const CUSTOMER_COUNTRY = 'customerCountry';
    const PRESTATIONS = 'prestations';
    const REFERENCE = 'reference';

    private $mandatory_params = array(
        self::ACTIVATION_KEY,
        self::REQUESTS,
    );

    private $mandatory_request_params = array(
        self::ORDER_ID,
        self::CUSTOMER_ID,
        self::CUSTOMER_FULLNAME,
        self::XEETT,
        self::XEETT_NAME,
        self::CUSTOMER_PHONE,
        self::CUSTOMER_MOBILE,
        self::CUSTOMER_COMPANY,
        self::CUSTOMER_ADDRESS1,
        self::CUSTOMER_ADDRESS2,
        self::CUSTOMER_POSTCODE,
        self::CUSTOMER_CITY,
        self::CUSTOMER_COUNTRY,
        self::PRESTATIONS,
        self::REFERENCE,
    );

    /**
     * Get mandatory properties
     * @return array list of mandatory params
     */
    protected function get_mandatory_params() {

        return $this->mandatory_params;
    }

    /**
     * Template Method used to get the specific return path (V2 or V3...)
     * @return mixed
     */
    abstract protected function get_specific_path();

    /**
     * 04 - B2C - Demande de retour
     *
     * @since 1.0.0
     *
     * @param array $params parameters
     */
    public function prepare_request( array $params=null ) {

        $this->method = 'POST';
        $this->path = $this->get_specific_path(); // No / at beginning

        $activationKey = get_option( Relais_Colis_Woocommerce_Loader::instance()->get_options_suffix_param().'_activationKey' );

        $dedicated_data = array(
            self::ACTIVATION_KEY => $activationKey,
        );

        $this->data = array_merge( $dedicated_data, $params );

        $this->validate();
        $this->validate_request_params();

        WP_Log::debug( __METHOD__, [ 'method' => $this->method, 'path' => $this->path, 'post_data' => $this->data ], 'relais-colis-woocommerce' );
        $this->data = json_encode( $this->data );
    }

    /**
     * Validate all request params array
     *
     * @return bool True if data are valid, false otherwise
     */
    public function validate_request_params() {

        $request_params = $this->data[ self::REQUESTS ];
        foreach ( $request_params as $request_param ) {

            foreach ( $this->mandatory_request_params as $param ) {
                if ( !isset( $request_param[ $param ] ) || is_null( $request_param[ $param ] ) ) {

                    WP_Log::error( __METHOD__, [ '$param' => $param ], 'relais-colis-woocommerce' );
                    throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::ERROR_MESSAGES[ WP_Relais_Colis_API_Exception::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER ].$param, WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER ] );
                }
            }
        }
    }
}