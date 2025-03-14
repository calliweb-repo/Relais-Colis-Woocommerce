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
    const RC_API_INVALID_RESPONSE = 'rc_api_invalid_response';
    const RC_API_NO_RESPONSE = 'rc_api_no_response';
    const RC_API_INVALID_C2C_MODE = 'rc_api_invalid_c2c_mode';
    const RC_API_INVALID_B2C_MODE = 'rc_api_invalid_b2c_mode';
    const RC_API_INCOHERENCY_STATE = 'rc_api_incoherency_state';
    const RC_API_PLACE_ADVERTISEMENT_C2C_HOME_NOT_SUPPORTED = 'rc_api_place_advertisement_c2c_home_not_supported';

    // Error codes
    const ERROR_CODES = array(
        self::RC_API_INVALID_ACTIVATIONKEY => 100,
        self::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER => 101,
        self::RC_API_INVALID_RESPONSE_CONTENT_TYPE => 102,
        self::TARIFF_GRIDS_CRITERIA_CONFLICT => 103,
        self::RC_API_PLACE_RETURN_ERROR => 104,
        self::RC_API_INVALID_RESPONSE => 105,
        self::RC_API_NO_RESPONSE => 106,
        self::RC_API_INVALID_C2C_MODE => 107,
        self::RC_API_INVALID_B2C_MODE => 108,
        self::RC_API_INCOHERENCY_STATE => 109,
        self::RC_API_PLACE_ADVERTISEMENT_C2C_HOME_NOT_SUPPORTED => 110,
    );

    const ERROR_MESSAGES = array(
        self::RC_API_INVALID_ACTIVATIONKEY => 'RC API: Invalid activation key',
        self::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER => 'Missing or empty required parameter: ',
        self::RC_API_INVALID_RESPONSE_CONTENT_TYPE => 'RC API: Invalid response content type: ',
        self::TARIFF_GRIDS_CRITERIA_CONFLICT => 'A pricing rule conflicts with an existing one.',
        self::RC_API_PLACE_RETURN_ERROR => 'Response status of place return request is error',
        self::RC_API_INVALID_RESPONSE => 'RC API: Invalid response ',
        self::RC_API_NO_RESPONSE => 'RC API: No response ',
        self::RC_API_INVALID_C2C_MODE => 'Invalid mode: only B2C is authorized',
        self::RC_API_INVALID_B2C_MODE => 'Invalid mode: only C2C is authorized',
        self::RC_API_INCOHERENCY_STATE => 'Order invalid state',
        self::RC_API_PLACE_ADVERTISEMENT_C2C_HOME_NOT_SUPPORTED => 'Shipping label generation is not supported for C2C home/home+ delivery mode',
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