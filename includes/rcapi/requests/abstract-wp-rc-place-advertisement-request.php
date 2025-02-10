<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_Relais_Colis API request object.
 *
 * @since 1.0.0
 */
abstract class WP_RC_Place_Advertisement_Request extends WP_Relais_Colis_Request {

    const ACTIVATION_KEY = 'activationKey';
    const ACTIVITY_CODE = 'activityCode';
    const AGENCY_CODE = 'agencyCode';
    const CUSTOMER_ID = 'customerId';
    const CUSTOMER_FULLNAME = 'customerFullname';
    const CUSTOMER_EMAIL = 'customerEmail';
    const CUSTOMER_PHONE = 'customerPhone';
    const CUSTOMER_MOBILE = 'customerMobile';
    const DELIVERY_PAYMENT_METHOD = 'deliveryPaymentMethod';
    const DELIVERY_TYPE = 'deliveryType';
    const LANGUAGE = 'language';
    const ORDER_TYPE = 'orderType';
    const ORDER_TYPE_SUB = 'orderTypeSub';
    const PICKING_SITE = 'pickingSite';
    const PRODUCT_FAMILY = 'productFamily';
    const ORDER_REFERENCE = 'orderReference';
    const SENSITIVE_PRODUCT = 'sensitiveProduct';
    const SHIPPING_ADDRESS_1 = 'shippingAddress1';
    const SHIPPING_ADDRESS_2 = 'shippingAddress2';
    const SHIPPING_POSTCODE = 'shippingPostcode';
    const SHIPPING_CITY = 'shippingCity';
    const SHIPPING_COUNTRY_CODE = 'shippingCountryCode';
    const SHIPPMENT_WEIGHT = 'shippmentWeight';
    const WEIGHT = 'weight';

    private $common_mandatory_params = array(
        self::ACTIVATION_KEY,
        self::ACTIVITY_CODE,
        self::AGENCY_CODE,
        self::CUSTOMER_ID,
        self::CUSTOMER_FULLNAME,
        self::CUSTOMER_EMAIL,
        self::CUSTOMER_PHONE,
        self::CUSTOMER_MOBILE,
        self::DELIVERY_PAYMENT_METHOD,
        self::DELIVERY_TYPE,
        self::LANGUAGE,
        self::ORDER_TYPE,
        self::ORDER_TYPE_SUB,
        self::PICKING_SITE,
        self::PRODUCT_FAMILY,
        self::ORDER_REFERENCE,
        self::SENSITIVE_PRODUCT,
        self::SHIPPING_ADDRESS_1,
        self::SHIPPING_POSTCODE,
        self::SHIPPING_CITY,
        self::SHIPPING_COUNTRY_CODE,
        self::SHIPPMENT_WEIGHT,
        self::WEIGHT,
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
     * Template Method used to get specific dedicated data
     * @return array list of dedicated params
     */
    abstract protected function get_specific_dedicated_params();

    /**
     * 02 - Réservation d'étiquette - Generic
     * /api/package/placeAdvertisement
     *
     *
     * @since 1.0.0
     *
     * @param array $params parameters
     */
    public function prepare_request( array $params=null ) {

        $this->method = 'POST';
        $this->path = 'api/package/placeAdvertisement'; // No / at beginning

        $activationKey = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );

        $dedicated_data = array(
            self::ACTIVATION_KEY => $activationKey,
            self::DELIVERY_PAYMENT_METHOD => '3',
            self::DELIVERY_TYPE => '00',
            self::LANGUAGE => 'FR',
            self::ORDER_TYPE => '1',
            self::ORDER_TYPE_SUB => '1',
            self::PICKING_SITE => '0',
            self::PRODUCT_FAMILY => '08',
            self::SENSITIVE_PRODUCT => '0',
            self::SHIPPMENT_WEIGHT => '1000',
            self::WEIGHT => '1000',
        );

        $this->data = array_merge( $dedicated_data, $this->get_specific_dedicated_params(), $params );

        $this->validate();

        // May convert weight to grams
        $woocommerce_weight_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT, 'g' );

        $shippment_weight_grams = WP_Helper::convert_to_grams( $this->data[ self::SHIPPMENT_WEIGHT ], $woocommerce_weight_unit );
        if ( !is_null( $shippment_weight_grams ) ) $this->data[ self::SHIPPMENT_WEIGHT ] = $shippment_weight_grams;

        $weight_grams = WP_Helper::convert_to_grams( $this->data[ self::WEIGHT ], $woocommerce_weight_unit );
        if ( !is_null( $weight_grams ) ) $this->data[ self::WEIGHT ] = $weight_grams;

        // Tips specific to RC API
        $post_data = array( $this->data );

        WP_Log::debug( __METHOD__, [ 'method' => $this->method, 'path' => $this->path, 'post_data' => $this->data ], 'relais-colis-woocommerce' );
        $this->data = json_encode( $post_data );
    }
}
