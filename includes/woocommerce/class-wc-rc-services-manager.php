<?php

namespace RelaisColisWoocommerce;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Services_DAO;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce RC Services Manage all things related to services.
 *
 * @since     1.0.0
 */
class WC_RC_Services_Manager {

    // Use Trait Singleton
    use Singleton;

    // Define constants for service slugs
    const APPOINTMENT_SCHEDULING = 'appointment_scheduling';
    const DELIVERY_TO_FLOOR = 'delivery_to_floor';
    const TWO_PERSON_DELIVERY = 'two_person_delivery';
    const SETUP_LARGE_APPLIANCES = 'setup_large_appliances';
    const QUICK_ASSEMBLY = 'quick_assembly';
    const OVERSIZED_ITEMS = 'oversized_items';
    const PRODUCT_UNPACKING = 'product_unpacking';
    const PACKAGING_REMOVAL = 'packaging_removal';
    const REMOVAL_OLD_EQUIPMENT = 'removal_old_equipment';
    const DELIVERY_DESIRED_ROOM = 'delivery_desired_room';
    const CURBSIDE_DELIVERY = 'curbside_delivery';

    const HTML_SERVICES_ID_PREFIX = 'rc_service_';

    // Constants used to save / restore services to / from WC session
    const SELECTED_SERVICES = 'selected_services';
    const SERVICE_HOMEPLUS_ELEVATOR = 'elevator';
    const SERVICE_HOMEPLUS_DIGICODE = 'digicode';
    const SERVICE_HOMEPLUS_FLOOR = 'floor';
    const SERVICE_HOMEPLUS_TYPE_OF_RESIDENCE = 'type_habitat';
    const SERVICE_HOMEPLUS_ADDITIONAL_INFOS = 'informations_complementaires';

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // Alter a WooCommerce form field label
        add_filter( 'woocommerce_form_field', array( $this, 'filter_woocommerce_form_field' ), 10, 4 );
    }


    /**
     * Get a service name by slug
     */
    public function get_fixed_service_name( $slug ) {

        $fixed_services = $this->get_fixed_services();
        if ( !array_key_exists( $slug, $fixed_services ) ) return '';

        return $fixed_services[ $slug ][ 0 ];
    }

    /**
     * Getter for fixed services
     * @return array Fixed services (prestations)
     */
    public function get_fixed_services() {
        return array(
            self::APPOINTMENT_SCHEDULING => array(
                __( 'Appointment Scheduling', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME => WC_RC_Shipping_Constants::OFFER_HOME,
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::DELIVERY_TO_FLOOR => array(
                __( 'Delivery to the Floor', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::TWO_PERSON_DELIVERY => array(
                __( 'Two-Person Delivery', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::SETUP_LARGE_APPLIANCES => array(
                __( 'Setup of Large Appliances', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::QUICK_ASSEMBLY => array(
                __( 'Quick Assembly', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::OVERSIZED_ITEMS => array(
                __( 'Oversized Items', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::PRODUCT_UNPACKING => array(
                __( 'Product Unpacking', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::PACKAGING_REMOVAL => array(
                __( 'Packaging Removal', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::REMOVAL_OLD_EQUIPMENT => array(
                __( 'Removal of Old Equipment', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME => WC_RC_Shipping_Constants::OFFER_HOME,
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::DELIVERY_DESIRED_ROOM => array(
                __( 'Delivery to Desired Room', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS => WC_RC_Shipping_Constants::OFFER_HOME_PLUS
                ],
            ),

            self::CURBSIDE_DELIVERY => array(
                __( 'Curbside Delivery', 'relais-colis-woocommerce' ),
                [
                    WC_RC_Shipping_Constants::METHOD_NAME_HOME => WC_RC_Shipping_Constants::OFFER_HOME
                ],
            ),
        );
    }

    /**
     * Get a WooCommerce form field formatted list for information necessary to Home+ (Digicode, Floor...)
     * @return array
     */
    public function get_homeplus_addon_infos_fields() {

        return array(
            self::SERVICE_HOMEPLUS_DIGICODE => array(
                'type' => 'text',
                'class' => array( self::SERVICE_HOMEPLUS_DIGICODE ),
                'label' => __( 'Digicode', 'relais-colis-woocommerce' ),
            ),
            self::SERVICE_HOMEPLUS_FLOOR => array(
                'type' => 'select',
                'class' => array( self::SERVICE_HOMEPLUS_FLOOR ),
                'label' => __( 'Floor', 'relais-colis-woocommerce' ),
                'options' => array(
                    "" => __( 'Please select a floor', 'relais-colis-woocommerce' ),
                    "rdc" => __( 'Ground floor (RDC)', 'relais-colis-woocommerce' ),
                    "1" => __( '1st floor', 'relais-colis-woocommerce' ),
                    "2" => __( '2nd floor', 'relais-colis-woocommerce' ),
                    "3" => __( '3rd floor', 'relais-colis-woocommerce' ),
                    "4" => __( '4th floor', 'relais-colis-woocommerce' ),
                    "5" => __( '5th floor and above', 'relais-colis-woocommerce' ),
                ),
            ),
            self::SERVICE_HOMEPLUS_TYPE_OF_RESIDENCE => array(
                'type' => 'select',
                'class' => array( self::SERVICE_HOMEPLUS_TYPE_OF_RESIDENCE ),
                'label' => __( 'Type of residence', 'relais-colis-woocommerce' ),
                'options' => array(
                    "" => __( 'Please select a type', 'relais-colis-woocommerce' ),
                    "house" => __( 'House', 'relais-colis-woocommerce' ),
                    "apartment" => __( 'Apartment', 'relais-colis-woocommerce' ),
                ),
            ),
            self::SERVICE_HOMEPLUS_ELEVATOR => array(
                'type' => 'checkbox',
                'class' => array( self::SERVICE_HOMEPLUS_ELEVATOR ),
                'label' => __( 'Elevator', 'relais-colis-woocommerce' ),
            ),
            self::SERVICE_HOMEPLUS_ADDITIONAL_INFOS => array(
                'type' => 'textarea',
                'class' => array( self::SERVICE_HOMEPLUS_ADDITIONAL_INFOS ),
                'label' => __( 'Additional delivery instructions', 'relais-colis-woocommerce' ),
            ),
        );
    }

    /**
     * Alter a WooCommerce form service field label
     * Used to remove optional from label
     * @param $field
     * @param $key
     * @param $args
     * @param $value
     * @return array|mixed|string|string[]|void
     */
    public function filter_woocommerce_form_field( $field, $key, $args, $value ) {

        // Enqueued only in concerned checkout page
        // if ( !is_checkout() ) return;

        // Check if it is a service field
        foreach ( $this->get_fixed_services() as $service_key => $services ) {

            if ( $key === self::HTML_SERVICES_ID_PREFIX.$service_key ) {

                WP_Log::debug( __METHOD__, [ '$field' => $field, '$key' => $key ], 'relais-colis-woocommerce' );
                $optional = '<span class="optional">('.esc_html__( 'optional', 'relais-colis-woocommerce' ).')</span>';
                $field = str_replace( $optional, '', $field );
                return $field;
            }
        }

        // Else check if it is a service homeplus addon field
        $addon_homeplus_service = array(
            self::HTML_SERVICES_ID_PREFIX.self::SERVICE_HOMEPLUS_ELEVATOR,
            self::HTML_SERVICES_ID_PREFIX.self::SERVICE_HOMEPLUS_DIGICODE,
            self::HTML_SERVICES_ID_PREFIX.self::SERVICE_HOMEPLUS_FLOOR,
            self::HTML_SERVICES_ID_PREFIX.self::SERVICE_HOMEPLUS_TYPE_OF_RESIDENCE,
            self::HTML_SERVICES_ID_PREFIX.self::SERVICE_HOMEPLUS_ADDITIONAL_INFOS,
        );
        if ( in_array( $key, $addon_homeplus_service ) ) {

            WP_Log::debug( __METHOD__, [ '$field' => $field, '$key' => $key ], 'relais-colis-woocommerce' );
            $optional = '<span class="optional">('.esc_html__( 'optional', 'relais-colis-woocommerce' ).')</span>';
            $field = str_replace( $optional, '', $field );
            return $field;
        }

        return $field;
    }

    /**
     * Get configured services from DB, check DB coherency, and add name and related methods
     * @return void
     */
    public function get_configured_services() {

        // Fetch services from the database
        $services = WP_Services_DAO::instance()->get_services();
        $fixed_services = $this->get_fixed_services();
        WP_Log::debug( __METHOD__, [ '$services' => $services ], 'relais-colis-woocommerce' );

        $valid_services = array();

        // Loop through each service and update it
        foreach ( $services as $service ) {

            // Retrieve slug from fixed services ref
            $slug = $service[ 'slug' ];
            if ( !isset( $fixed_services[ $slug ] ) ) {

                WP_Log::error( __METHOD__." - Service not found in fixed services", [ 'slug' => $slug ], 'relais-colis-woocommerce' );
                continue;
            }
            // Add name and delivery_methods
            $service[ 'name' ] = $fixed_services[ $slug ][ 0 ];
            $service[ 'delivery_methods' ] = $fixed_services[ $slug ][ 1 ];
            $valid_services[ $slug ] = $service;
        }
        WP_Log::debug( __METHOD__, [ '$valid_services' => $valid_services ], 'relais-colis-woocommerce' );
        return $valid_services;
    }

    /**
     * Determine available services for products currently in cart, for a given offer
     * @param $offer one of WC_RC_Shipping_Constants METHOD_NAME_RELAIS_COLIS, METHOD_NAME_HOME, METHOD_NAME_HOME_PLUS
     * @return mixed services from DB, otherwise empty array
     */
    public function get_available_services_from_cart( $offer = WC_RC_Shipping_Constants::METHOD_NAME_HOME ) {

        // Verify param
        if ( ( $offer !== WC_RC_Shipping_Constants::METHOD_NAME_HOME ) && ( $offer !== WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS ) && ( $offer !== WC_RC_Shipping_Constants::METHOD_NAME_RELAIS_COLIS ) ) {

            return array();
        }

        // Get product ids from cart
        if ( is_null( WC()->cart ) ) return;
        $cart_items = WC()->cart->get_cart();
        WP_Log::debug( __METHOD__, [ '$cart_items' => $cart_items ], 'relais-colis-woocommerce' );
        $product_ids_in_cart = [];

        foreach ( $cart_items as $cart_item ) {
            $product_ids_in_cart[] = $cart_item[ 'product_id' ];
        }

        // Get available services from DAO
        $services = WP_Services_DAO::instance()->get_available_services( $offer, $product_ids_in_cart );

        $fixed_services = $this->get_fixed_services();
        WP_Log::debug( __METHOD__, [ '$services' => $services ], 'relais-colis-woocommerce' );

        $valid_services = array();

        // Loop through each service and update it
        foreach ( $services as $service ) {

            // Retrieve slug from fixed services ref
            $slug = $service[ 'slug' ];
            if ( !isset( $fixed_services[ $slug ] ) ) {

                WP_Log::error( __METHOD__." - Service not found in fixed services", [ 'slug' => $slug ], 'relais-colis-woocommerce' );
                continue;
            }
            // Add name and delivery_methods
            $service[ 'name' ] = $fixed_services[ $slug ][ 0 ];
            $service[ 'delivery_methods' ] = $fixed_services[ $slug ][ 1 ];
            $valid_services[ $slug ] = $service;
        }
        WP_Log::debug( __METHOD__, [ '$valid_services' => $valid_services ], 'relais-colis-woocommerce' );
        return $valid_services;
    }
}
