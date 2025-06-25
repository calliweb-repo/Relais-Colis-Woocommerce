<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
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
        self::REFERENCE,
        self::CUSTOMER_COMPANY,
        self::CUSTOMER_ADDRESS1,
        self::CUSTOMER_ADDRESS2,
        self::CUSTOMER_POSTCODE,
        self::CUSTOMER_CITY,
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

        $activationKey = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );

        //"orderId" cela correspond à la commande woocommerce
        //"customerId" c'est l'id woocommerce du client
        //"xeett" c'est le xeett du relais choisi par le clint lors de la commande (qui fait l'objet du retour)
        //"xeettName" idem que pour le xeett
        //"reference" c'est la référence de la commande dans woocommerce
        //"prestations" c'est la liste des prestation choisi lors de la commande
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
                    throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER )).' '.esc_html($param), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER ]) );
                }
            }
        }
    }
}