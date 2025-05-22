<?php

namespace RelaisColisWoocommerce\RCAPI;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Config_Manager;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Api\WP_API_Base;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use Exception;
use WP_Error;

/**
 * API for processing Relais Colis API requests.
 *
 * Cf https://developers.google.com/maps/documentation/places/web-service/place-details
 *
 * @since 1.0.0
 */
class WP_Relais_Colis_API extends WP_API_Base {

    // Use Trait Singleton
    use Singleton;

    // Request types
    const REQUEST_GET_B2C_CONFIGURATION = 'get_b2c_configuration';
    const REQUEST_GET_C2C_CONFIGURATION = 'get_c2c_configuration';
    const REQUEST_B2C_RELAY_PLACE_ADVERTISEMENT = 'b2c_relay_place_advertisement';
    const REQUEST_B2C_HOME_PLACE_ADVERTISEMENT = 'b2c_home_place_advertisement';
    const REQUEST_C2C_RELAY_PLACE_ADVERTISEMENT = 'c2c_relay_place_advertisement';
    const REQUEST_B2C_GENERATE = 'b2c_generate';
    const REQUEST_C2C_GENERATE = 'c2c_generate';
    const REQUEST_BULK_GENERATE = 'bulk_generate';
    const REQUEST_B2C_PLACE_RETURN_V2 = 'b2c_place_return_v2';
    const REQUEST_B2C_PLACE_RETURN_V3 = 'b2c_place_return_v3';
    const REQUEST_C2C_GET_INFOS = 'c2c_get_infos';
    const REQUEST_C2C_GET_PACKAGES_PRICE = 'c2c_get_packages_price';
    const REQUEST_TRANSPORT_GENERATE = 'transport_generate';
    const REQUEST_GET_PACKAGES_STATUS = 'get_packages_status';

    /** @var string[] */
    const REST_URLS = [
        WC_RC_Shipping_Constants::LIVE_MODE => 'https://ws-modules.relaiscolis.com/',
        WC_RC_Shipping_Constants::TEST_MODE => 'https://preprod-ws-modules.relaiscolis.com/'
    ];

    /** @var bool whether API is enabled */
    private $enabled = false;

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        $this->response_handler = 'WP_Relais_Colis_Response';
        $this->enabled = true;

        if ( !is_null( $this->request_uri ) ) return;

        // Get mode (option), can be LIVE_MODE or TEST_MODE
        $mode = WC_RC_Shipping_Config_Manager::instance()->get_request_mode();

        // Deduce request URI
        $this->request_uri = self::REST_URLS[ $mode ];

        //
        // Debug
        //
        add_filter( 'wp_http_request_args', array( $this, 'filter_wp_http_request_args' ), 10, 2 );
        add_filter( 'wp_api_request_uri', array( $this, 'filter_wp_api_request_uri' ), 10, 2 );

        /**
         * Filters whether SSL should be verified for non-local requests.
         *
         * @since 2.8.0
         * @since 5.1.0 The `$url` parameter was added.
         *
         * @param bool|string $ssl_verify Boolean to control whether to verify the SSL connection
         *                                or path to an SSL certificate.
         * @param string      $url        The request URL.
         */
        add_filter( 'https_ssl_verify', array( $this, 'filter_https_ssl_verify' ), 10, 2 );

        /**
         * Fires after an HTTP API response is received and before the response is returned.
         *
         * @since 2.8.0
         *
         * @param array|WP_Error $response    HTTP response or WP_Error object.
         * @param string         $context     Context under which the hook is fired.
         * @param string         $class       HTTP transport used.
         * @param array          $parsed_args HTTP request arguments.
         * @param string         $url         The request URL.
         */
        add_action( 'http_api_debug', array( $this, 'action_http_api_debug' ), 10, 5 );

        /**
         * Filters a successful HTTP API response immediately before the response is returned.
         *
         * @since 2.9.0
         *
         * @param array  $response    HTTP response.
         * @param array  $parsed_args HTTP request arguments.
         * @param string $url         The request URL.
         */
        add_filter( 'http_response', array( $this, 'filter_http_response' ), 10, 3 );

        /**
         * API Base Request Performed Action.
         *
         * Fired when an API request is performed via this base class. Plugins can
         * hook into this to log request/response data.
         *
         * @since 1.0.0
         * @param array $request_data {
         *     @type string $method request method, e.g. POST
         *     @type string $uri request URI
         *     @type string $user-agent
         *     @type string $headers request headers
         *     @type string $body request body
         *     @type string $duration in seconds
         * }
         * @param array $response data {
         *     @type string $code response HTTP code
         *     @type string $message response message
         *     @type string $headers response HTTP headers
         *     @type string $body response body
         * }
         * @param WP_API_Base $this instance
         */
        add_action( 'wp_api_request_performed', array( $this, 'action_wp_api_request_performed' ), 10, 3 );

        WP_Log::info( __METHOD__, [ 'request_uri' => $this->request_uri ], 'relais-colis-woocommerce' );
    }

    /**
     * API Base Request Performed Action.
     *
     * Fired when an API request is performed via this base class. Plugins can
     * hook into this to log request/response data.
     *
     * @since 1.0.0
     * @param array $request_data {
     *     @type string $method request method, e.g. POST
     *     @type string $uri request URI
     *     @type string $user-agent
     *     @type string $headers request headers
     *     @type string $body request body
     *     @type string $duration in seconds
     * }
     * @param array $response data {
     *     @type string $code response HTTP code
     *     @type string $message response message
     *     @type string $headers response HTTP headers
     *     @type string $body response body
     * }
     * @param WP_API_Base $this instance
     */
    public function action_wp_api_request_performed( $request_data, $response_data, $wp_api_base ) {

        WP_Log::info( __METHOD__, [ 'request_data' => $request_data,  'response_data' => $response_data ], 'relais-colis-woocommerce' );
    }

    /**
     * Filters whether SSL should be verified for non-local requests.
     *
     * @since 2.8.0
     * @since 5.1.0 The `$url` parameter was added.
     *
     * @param bool|string $ssl_verify Boolean to control whether to verify the SSL connection
     *                                or path to an SSL certificate.
     * @param string      $url        The request URL.
     */
    public function filter_https_ssl_verify( $verify, $url ) {

        return false;
    }

    /**
     * Fires after an HTTP API response is received and before the response is returned.
     *
     * @since 2.8.0
     *
     * @param array|WP_Error $response    HTTP response or WP_Error object.
     * @param string         $context     Context under which the hook is fired.
     * @param string         $class       HTTP transport used.
     * @param array          $parsed_args HTTP request arguments.
     * @param string         $url         The request URL.
     */
    public function action_http_api_debug( array|WP_Error $response, string $context, string $class, array $parsed_args, string $url ) {

        WP_Log::debug( __METHOD__, ['response'=>$response, 'context'=>$context, 'class'=>$class, 'parsed_args'=>$parsed_args, 'url'=>$url ], 'relais-colis-woocommerce' );
    }

    /**
     * Filters a successful HTTP API response immediately before the response is returned.
     *
     * @since 2.9.0
     *
     * @param array  $response    HTTP response.
     * @param array  $parsed_args HTTP request arguments.
     * @param string $url         The request URL.
     */
    public function filter_http_response( array $response, array $parsed_args, string $url ) {

        WP_Log::debug( __METHOD__, ['response'=>$response, 'parsed_args'=>$parsed_args, 'url'=>$url ], 'relais-colis-woocommerce' );
        return $response;
    }

    /**
     * Request arguments.
     *
     * Allow other actors to filter the request arguments. Note that
     * child classes can override this method, which means this filter may
     * not be invoked, or may be invoked prior to the overridden method
     *
     * @since 1.0.0
     * @param array $args request arguments
     * @param WP_API_Base class instance
     */
    public function filter_wp_http_request_args( $args, $api ) {

        WP_Log::debug( __METHOD__, ['$args'=>$args], 'relais-colis-woocommerce' );
        return $args;
    }

    /**
     * Request URI Filter.
     *
     * Allow actors to filter the request URI. Note that child classes can override
     * this method, which means this filter may be invoked prior to the overridden
     * method.
     *
     * @since 4.1.0
     *
     * @param string $uri current request URI
     * @param WP_API_Base class instance
     */
    public function filter_wp_api_request_uri( $uri, $api ) {

        WP_Log::debug( __METHOD__, ['$uri'=>$uri], 'relais-colis-woocommerce' );
        return $uri;
    }

    /**
     * Get the plugin instance (implements parent method).
     *
     * @since 1.0.0
     *
     * @return Relais_Colis_Woocommerce The extension main instance
     */
    protected function get_plugin() {

        return Relais_Colis_Woocommerce::instance();
    }


    /**
     * Check whether Google API is enabled.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function is_enabled() {
        return $this->enabled;
    }


    /**
     * Return a new request object
     *
     * Child classes must implement this to return an object that implements
     * \WP_API_Request which should be used in the child class API methods
     * to build the request. The returned WP_API_Request should be passed
     * to self::perform_request() by your concrete API methods
     *
     * @since 1.0.0
     *
     * @param array $args optional request arguments
     */
    protected function get_new_request( $args = array() ) {
        // Not adapted for multi parameters
    }

    /**
     * Generic treatment for a request type
     *
     * @param string $request_type one of REQUEST_GET_B2C_CONFIGURATION, REQUEST_GET_C2C_CONFIGURATION ...
     * @param array $params may contains specific parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return WP_Relais_Colis_Response|string|null
     * @throws WP_Relais_Colis_API_Exception
     */
    private function rc_api_request( string $request_type, array $params=null, $raw=false ) {

        if (!$this->is_enabled()) {
            return null;
        }
        // Init
        $this->init();


        try {
            // Build request
            $request = WP_Relais_Colis_Request_Factory::instance()->get_rc_api_reuest( $request_type );
            if ( is_null( $request ) ) return null;

            // Prepare request
            $request->prepare_request( $params );

            // Add API key as header
            //-H 'Content-Type: application/json' -H 'X-Goog-Api-Key: API_KEY' \
            $this->set_request_header( 'Content-Type', 'application/json' );
            //$this->set_request_header( 'Content-Type', 'multipart/form-data' );

            // Send request
            $response_raw = $this->perform_request( $request );
            WP_Log::debug( __METHOD__, ['response_raw'=>$response_raw], 'relais-colis-woocommerce' );


            // Check response code
            $response_code = $this->get_response_code();
            if ( $response_code !== 200 ) {

                // Pb occurred... HTML response code in error
                throw new WP_Relais_Colis_API_Exception( $this->get_response_message(), $this->get_response_code() );
            }


            // Use a Relais Colis specific XML response factory
            return WP_Relais_Colis_Response_Factory::instance()->get_rc_api_response( $request_type, $response_raw, $this->get_response_headers(), $raw );

        } catch ( Exception $e ) {

            WP_Log::debug( __METHOD__.' : Exception ', [ 'code' => $e->getCode(), 'message' => $e->getMessage() ], 'relais-colis-woocommerce' );
            // Pb occurred... HTML response code in error
            throw new WP_Relais_Colis_API_Exception( esc_html($e->getMessage()), esc_html($e->getCode()) );
        }
    }

    /**
     * 01 - B2C - Récupération du compte enseigne
     *
     *
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Get_Configuration_Response
     * @throws Exception
     */
    public function get_b2c_configuration( $raw = false ) {

        return $this->rc_api_request( self::REQUEST_GET_B2C_CONFIGURATION, null, $raw );
    }

    /**
     * 01 - C2C - Récupération du compte enseigne
     *
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Get_Configuration_Response
     * @throws Exception
     */
    public function get_c2c_configuration( $raw = false ) {

        return $this->rc_api_request( self::REQUEST_GET_C2C_CONFIGURATION, null, $raw );
    }

    /**
     * 02 - B2C - Réservation d'étiquette - Relais
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Place_Advertisement_Response
     * @throws Exception
     */
    public function b2c_relay_place_advertisement( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_B2C_RELAY_PLACE_ADVERTISEMENT, $params, $raw );
    }

    /**
     * 02 - B2C - Réservation d'étiquette - Home
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Place_Advertisement_Response
     * @throws Exception
     */
    public function b2c_home_place_advertisement( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_B2C_HOME_PLACE_ADVERTISEMENT, $params, $raw );
    }

    /**
     * 02 - C2C - Réservation d'étiquette - Relais
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Place_Advertisement_Response
     * @throws Exception
     */
    public function c2c_relay_place_advertisement( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_C2C_RELAY_PLACE_ADVERTISEMENT, $params, $raw );
    }

    /**
     * 03 - B2C - Impression d'étiquette
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Etiquette_Generate_Response
     * @throws Exception
     */
    public function b2c_generate( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_B2C_GENERATE, $params, $raw );
    }

    /**
     * 03 - C2C - Impression d'étiquette
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Etiquette_Generate_Response
     * @throws Exception
     */
    public function c2c_generate( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_C2C_GENERATE, $params, $raw );
    }

    /**
     * 03 - Impression d'étiquettes en masse
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Etiquette_Generate_Response
     * @throws Exception
     */
    public function bulk_generate( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_BULK_GENERATE, $params, $raw );
    }

    /**
     * 04 - B2C - Demande de retour V2
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_B2C_Place_Return_Response
     * @throws Exception
     */
    public function b2c_place_return( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_B2C_PLACE_RETURN_V2, $params, $raw );
    }

    /**
     * 04 - B2C - Demande de retour V3
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_B2C_Place_Return_Response
     * @throws Exception
     */
    public function b2c_place_return_v3( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_B2C_PLACE_RETURN_V3, $params, $raw );
    }

    /**
     * 05 - C2C - Récupération du solde du client pro
     *
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_B2C_Place_Return_Response
     * @throws Exception
     */
    public function c2c_get_infos( $raw = false ) {

        return $this->rc_api_request( self::REQUEST_C2C_GET_INFOS, null, $raw );
    }

    /**
     * 05 - C2C - Récupération du prix d'un colis
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_C2C_Get_Packages_Price_Response
     * @throws Exception
     */
    public function c2c_get_packages_price( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_C2C_GET_PACKAGES_PRICE, $params, $raw );
    }

    /**
     * 06 - Génération de la lettre de voiture
     *
     * @param $params see the specific WP_Relais_Colis_Request for more informations about parameters
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Transport_Generate_Response
     * @throws Exception
     */
    public function transport_generate( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_TRANSPORT_GENERATE, $params, $raw );
    }

    /**
     * 07 - Récupération des évènements des colis d'une enseigne
     *
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|WP_RC_Transport_Generate_Response
     * @throws Exception
     */
    public function get_packages_status( $params=array(), $raw = false ) {

        return $this->rc_api_request( self::REQUEST_GET_PACKAGES_STATUS, $params, $raw );
    }
}
