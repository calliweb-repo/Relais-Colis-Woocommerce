<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object.
 *
 * @since 1.0.0
 */
abstract class WP_RC_Generate extends WP_Relais_Colis_Request {

    const ACTIVATION_KEY = 'activationKey';
    const FORMAT = 'format';

    const FORMAT_A4 = 'A4';
    const FORMAT_ZEBRA = 'ZEBRA';
    //const FORMAT_A5 = 'A5';

    private $common_mandatory_params = array(
        self::ACTIVATION_KEY,
        self::FORMAT,
    );

    /**
     * Get mandatory properties
     * @return array list of mandatory params
     */
    protected function get_mandatory_params() {

        return array_merge( $this->common_mandatory_params, $this->get_specific_mandatory_params() );
    }
    /**
     * Template Method used to get specific mandatory properties
     * @return array list of mandatory params
     */
    abstract protected function get_specific_mandatory_params();

    /**
     * 01 - B2C - Récupération du compte enseigne
     * /etiquette/generate
     *
     * @since 1.0.0
     *
     * @param array $params optional parameters
     * @return mixed
     */
    public function prepare_request( array $params=null ) {

        $this->method = 'POST';
        $this->path = 'etiquette/generate';

        $activationKey = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );

        $this->data = array(
            self::ACTIVATION_KEY => $activationKey,
            self::FORMAT => self::FORMAT_A4, // Default A4
        );

        $this->data = array_merge( $this->data, $params );

        $this->validate();

        WP_Log::debug( __METHOD__, [ 'method' => $this->method, 'path' => $this->path, 'post_data' => $this->data ], 'relais-colis-woocommerce' );
        $this->data = json_encode( $this->data );

    }
}
