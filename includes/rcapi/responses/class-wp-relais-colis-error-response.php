<?php

namespace RelaisColisWoocommerce\RCAPI;


use RelaisColisWoocommerce\WPFw\Api\WP_API_JSON_Response;

defined( 'ABSPATH' ) or exit;

/**
 *  Error response (JSON encoded)
 *
 * @since 1.0.0
 */
class WP_Relais_Colis_Error_Response extends WP_API_JSON_Response {

    // [response_raw] => {
    //  "title":"An Error occurred",
    //  "status":400,
    //  "detail":"Object(App\\Entity\\PackageDetail).pseudoRvc:\n    Le pseudo RVC ne peut \u00eatre plus longue que 5 caract\u00e8res (code d94b19cc-114f-4f44-9cc4-4138e80a87b9)\n"
    //}

    const TITLE = 'title';
    const STATUS = 'status';
    const DETAIL = 'detail';

    /**
     * Validate configuration datas
     *
     * @return bool Trueif data are valid, else false
     */
    public function validate() {

        $validate = true;
        $mandatory_properties = array(
            self::STATUS => 'integer',
            self::TITLE => 'string',
        );
        foreach ( $mandatory_properties as $property => $type ) {

            $validate = $validate && $this->check_property( $property, $type );
        }
        return $validate;
    }

}
