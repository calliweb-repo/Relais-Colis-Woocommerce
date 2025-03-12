<?php

namespace RelaisColisWoocommerce\RCAPI;

use Exception;

defined( 'ABSPATH' ) or exit;

class WP_Relais_Colis_API_Exception extends Exception {

    private $detail = '';

    // Response errors
    const RC_API_INVALID_ACTIVATIONKEY = 'rc_api_invalid_activationkey';
    const RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER = 'rc_api_missing_or_empty_required_parameter';
    const RC_API_INVALID_RESPONSE_CONTENT_TYPE = 'rc_api_invalid_response_content_type';
    const RC_API_PLACE_RETURN_ERROR = 'rc_api_place_return_error';
    const TARIFF_GRIDS_CRITERIA_CONFLICT = 'tariff_grids_criteria_conflict';

    // Error codes
    const ERROR_CODES = array(
        self::RC_API_INVALID_ACTIVATIONKEY => 100,
        self::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER => 101,
        self::RC_API_INVALID_RESPONSE_CONTENT_TYPE => 102,
        self::TARIFF_GRIDS_CRITERIA_CONFLICT => 103,
        self::RC_API_PLACE_RETURN_ERROR => 104,
    );

    const ERROR_MESSAGES = array(
        self::RC_API_INVALID_ACTIVATIONKEY => 'RC API: Invalid activation key',
        self::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER => 'Missing or empty required parameter: ',
        self::RC_API_INVALID_RESPONSE_CONTENT_TYPE => 'RC API: Invalid response content type: ',
        self::TARIFF_GRIDS_CRITERIA_CONFLICT => 'A pricing rule conflicts with an existing one.',
        self::RC_API_PLACE_RETURN_ERROR => 'Response status of place return request is error',
    );

    /**
     * Getter
     * @return string
     */
    public function get_detail() {

        return $this->detail;
    }

    /**
     * Constructor
     * @return mixed
     */
    public function __construct( $message, $code, $detail='' ) {

        $this->detail = $detail;
        parent::__construct( $message, $code, null );
    }
}