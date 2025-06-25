<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\WPFw\Api\WP_API_XML_Response;

defined( 'ABSPATH' ) or exit;

/**
 *  Relais Colis Response
 *
 * @since 1.0.0
 */
abstract class WP_Relais_Colis_Response extends WP_API_XML_Response {

    /**
     * Validate configuration datas
     *
     * @return bool Trueif data are valid, else false
     */
    public function validate() {

        $validate = true;
        $mandatory_properties = $this->get_mandatory_properties();

        foreach ( $mandatory_properties as $property => $type ) {

            $validate = $validate && $this->check_property( $property, $type );
        }

        return $validate;
    }

    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    abstract protected function get_mandatory_properties();
}
