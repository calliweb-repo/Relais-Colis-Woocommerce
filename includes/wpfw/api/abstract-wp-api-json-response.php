<?php

namespace RelaisColisWoocommerce\WPFw\Api;

defined( 'ABSPATH' ) or exit;

/**
 * Base JSON API response class.
 *
 * @since 1.0.0
 */
abstract class WP_API_JSON_Response extends WP_API_Response {

    /**
     * Build the data object from the raw JSON.
     *
     * @since 1.0.0
     * @param string $raw_response The raw JSON
     */
    public function __construct( $raw_response ) {

        parent::__construct( $raw_response );

        $this->response_data = json_decode( $raw_response );
    }

    /**
     * Get the string representation of this response.
     *
     * @since 1.0.0
     * @see WP_API_Response::to_string()
     * @return string
     */
    public function to_string() {

        return $this->raw_response;
    }


    /**
     * Get the string representation of this response with any and all sensitive elements masked
     * or removed.
     *
     * @since 1.0.0
     * @see WP_API_Response::to_string_safe()
     * @return string
     */
    public function to_string_safe() {

        return $this->to_string();
    }
}
