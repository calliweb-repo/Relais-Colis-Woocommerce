<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\WPFw\Api\WP_API_JSON_Request;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object.
 *
 * @since 1.0.0
 */
abstract class WP_Relais_Colis_Request extends WP_API_JSON_Request {

    /**
     * Validate data array (not params)
     *
     * @return bool Trueif data are valid, else false
     */
    public function validate() {

        $mandatory_params = $this->get_mandatory_params();

        foreach ( $mandatory_params as $param ) {

            if ( !isset( $this->data[ $param ] ) || is_null( $this->data[ $param ] ) ) {

                WP_Log::error( __METHOD__, ['$param'=>$param], 'relais-colis-woocommerce' );
                throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER)).' '.esc_html($param), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_MISSING_OR_EMPTY_REQUIRED_PARAMETER]) );
            }
        }
    }

    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    abstract protected function get_mandatory_params();

}
