<?php

namespace RelaisColisWoocommerce\RCAPI;

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
    const PSEUDO_RVC = 'pseudoRvc';

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

        // These params are always the sames
        //"activityCode" correspond au type d'envoi "05" relais "08" pour le home et "07" pour le drive (à venir dnas quelque mois)
        //"customerId" c'est bien l'id du customer dans le cms
        //"orderReference" c'est le numéro de commande dans woocommerce
        $dedicated_data = array(
            self::ACTIVATION_KEY => $activationKey,
            // "deliveryPaymentMethod" c'est une valeur constante "3"
            self::DELIVERY_PAYMENT_METHOD => '3',
            // "deliveryType" c'est une valeur constante "00"
            self::DELIVERY_TYPE => '00',
            self::LANGUAGE => 'FR',
            //"orderType" c'est une valeur constante "1"
            self::ORDER_TYPE => '1',
            //"orderTypeSub" c'est une valeur constante "1"
            self::ORDER_TYPE_SUB => '1',
            //"pickingSite"  c'est une valeur constante "0"
            self::PICKING_SITE => '0',
            //"productFamily" pour un relais la valeur est "08" et pour un home ou home + "55"
            self::PRODUCT_FAMILY => '08',
            //"sensitiveProduct" c'est une valeur constante "0"
            self::SENSITIVE_PRODUCT => '0',
            //"pseudoRvc" c'est un champ vide, l'api le comble après avec le pseudo_rvc du relais
            self::PSEUDO_RVC => '',
            // Agency code BUG FIXME le place doit recevoir C3 pour que le print fonctionne ensuite, donc valeur FIXE
            self::AGENCY_CODE => 'C3',
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
