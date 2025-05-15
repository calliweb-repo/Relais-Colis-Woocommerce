<?php

namespace RelaisColisWoocommerce\WPFw\Api;

defined( 'ABSPATH' ) or exit;

/**
 *  Abstract Relais Colis response
 *
 * @since 1.0.0
 */
abstract class WP_API_Response {

    /** @var string string representation of this response */
    protected $raw_response;

    /** @var mixed decoded response data */
    public $response_data;

    /**
     * Constructor
     *
     * @since 1.0.0
     *
     * @param string|object $raw_response a response as JSON object or JSON encoded string
     */
    public function __construct( $raw_response ) {

        $this->raw_response = $raw_response;
    }
    
    /**
     * Returns the string representation of this request
     *
     * @since 1.0.0
     * @return string the request
     */
    abstract public function to_string();


    /**
     * Returns the string representation of this request with any and all
     * sensitive elements masked or removed
     *
     * @since 1.0.0
     * @return string the request, safe for logging/displaying
     */
    abstract public function to_string_safe();
    
    /**
     * Validate a response
     * 
     * @return boolean true is valid item, else false
     */
    abstract public function validate();

    /**
     * Magic accessor for response data attributes
     *
     * @since 1.0.0
     * @param string $name The attribute name to get.
     * @return mixed The attribute value
     */
    public function __get( $key ) {

        return $this->check_property( $key ) ? $this->response_data->$key : null;
    }

    /**
     * Getters
     */
    public function get_raw_response() {
        
        return $this->raw_response;
    }

    /**
     * Vérifie la présence et le type d'un champ dans l'objet raw_response.
     */
    protected function check_property( $property, $type=null, $response_data=null ) {
        
        if ( is_null( $response_data ) ) $response_data = $this->response_data;

        if ( !property_exists( $response_data, $property ) ) return false;

        if ( is_null( $type ) ) return true;

        switch ( $type ) {
            case 'double':
                return ( ( gettype( $response_data->$property ) === 'double' ) || ( gettype( $response_data->$property ) === 'integer' ) );
            case 'boolean':
                $value = $response_data->$property;
                return in_array(strtolower((string) $value), ['true', 'false', '1', '0'], true);
            default:
                $array_type = explode('/', $type);
                return ( in_array(gettype( $response_data->$property ), $array_type) );
        }
    }
}
