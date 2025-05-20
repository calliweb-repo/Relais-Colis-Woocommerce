<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Products_DAO;
use RelaisColisWoocommerce\DAO\WP_Services_DAO;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WC_RC_Services_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping Settings for Services (Prestations) section
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Services_Settings {

    const SECTION_SERVICES = 'services';

    // Use Trait Singleton
    use Singleton;

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // Register settings section
        add_filter( 'woocommerce_get_sections_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'filter_woocommerce_get_sections_rc' ) );

        // Register settings section
        add_action( 'woocommerce_settings_tabs_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_settings_rc_services' ) );

        // Update settings section
        add_action( 'woocommerce_update_options_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS.'_'.self::SECTION_SERVICES, array( $this, 'action_woocommerce_update_options_rc_services' ) );
    }

    /**
     * Add section to the tab Relais Colis
     * @param $sections
     * @return mixed
     */
    public function filter_woocommerce_get_sections_rc( $sections ) {

        // Only for B2C interaction mode
        if ( WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode() ) return $sections;

        $sections[ self::SECTION_SERVICES ] = __( 'Services', 'relais-colis-woocommerce' );
        return $sections;
    }

    /**
     * Add properties to the current section
     * @param $sections
     */
    public function action_woocommerce_settings_rc_services() {

        global $current_section;
        if ( $current_section !== self::SECTION_SERVICES ) return;

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Update the services settings in the database.
     */
    public function action_woocommerce_update_options_rc_services() {
        global $current_section;

        if ( $current_section !== self::SECTION_SERVICES ) {
            return;
        }

        WP_Log::debug( __METHOD__, ['$_POST'=>$_POST], 'relais-colis-woocommerce' );

        // Fetch services from the database
        $services = WC_RC_Services_Manager::instance()->get_configured_services();

        // Loop through each service and update it
        foreach ( $services as $service ) {

            // Get updated values from the form
            $slug = $service['slug'];
            $client_choice = isset( $_POST[ self::SECTION_SERVICES.'_'.$slug.'_client_choice' ] ) ? $_POST[ self::SECTION_SERVICES.'_'.$slug.'_client_choice' ] : $service[ 'client_choice' ];
            $delivery_method = isset( $_POST[ self::SECTION_SERVICES.'_'.$slug.'_delivery_method' ] ) ? $_POST[ self::SECTION_SERVICES.'_'.$slug.'_delivery_method' ] : $service[ 'delivery_method' ];
            $enabled = isset( $_POST[ self::SECTION_SERVICES.'_'.$slug.'_enabled' ] ) ? $_POST[ self::SECTION_SERVICES.'_'.$slug.'_enabled' ] : $service[ 'enabled' ];
            $price = floatval( $_POST[ self::SECTION_SERVICES.'_'.$slug.'_price' ] ?? $service[ 'price' ] );

            // Update the service in the database
            WP_Services_DAO::instance()->update_service(
                $service[ 'id' ],
                $service[ 'name' ],
                $slug,
                $client_choice,
                $delivery_method,
                $enabled,
                $price
            );

            // Get products ids from the form
            $products = isset( $_POST[ self::SECTION_SERVICES.'_'.$slug.'_products' ] ) ? $_POST[ self::SECTION_SERVICES.'_'.$slug.'_products' ] : array();
            WP_Services_DAO::instance()->update_service_relations( $service[ 'id' ], $products );
        }
    }

    /**
     * Get the settings for the services section.
     *
     * @return array Settings fields.
     */
    private function get_settings() {

        // Other tabs loaded only if RC API access is valid
        if ( !WC_RC_Shipping_Config_Manager::instance()->is_rc_api_valid_access() ) {

            return WC_RC_Shipping_Settings_Manager::instance()->get_invalid_licence_settings();
        }

        // Fetch services from the database using the DAO
        $services = WC_RC_Services_Manager::instance()->get_configured_services();

        $settings = [
            [
                'title' => __( 'Service Configuration', 'relais-colis-woocommerce' ),
                'type' => 'title',
                'id' => 'relais_colis_service_settings',
                'desc' => __( 'Configure all services.', 'relais-colis-woocommerce' ),
            ],
        ];

        // Loop through services to generate settings
        foreach ( $services as $service ) {
            WP_Log::debug( __METHOD__, ['$service'=>$service], 'relais-colis-woocommerce' );

            // Retrieve slug from fixed services ref
            $slug = $service['slug'];
            $service_name = $service['name'];
            $delivery_methods = $service['delivery_methods'];

            $settings[] = [
                'title' => $service_name,
                'type' => 'title',
                'id' => self::SECTION_SERVICES.'_'.$slug.'_title',
            ];
            WP_Log::debug( __METHOD__.' - For multiselect', ['$delivery_methods'=>$delivery_methods, 'DB delivery_method'=>$service['delivery_method']], 'relais-colis-woocommerce' );

            $settings[] = [
                'type' => 'text',
                'title' => __('Delivery Method', 'relais-colis-woocommerce'),
                'id' => self::SECTION_SERVICES.'_'.$slug.'_delivery_method_display',
                'custom_attributes' => [
                    'readonly' => 'readonly'
                ],
                'value' => implode(', ', $delivery_methods),
                'desc' => __('Available delivery methods for this service', 'relais-colis-woocommerce'),
                //'desc_tip' => true,
                'class' => 'regular-input'
            ];

            $settings[] = [
                'type' => 'hidden',
                'id' => self::SECTION_SERVICES.'_'.$slug.'_delivery_method',
                'value' => $service['delivery_method']
            ];

            $settings[] = [
                'title' => __( 'Client Choice', 'relais-colis-woocommerce' ),
                'id' => self::SECTION_SERVICES.'_'.$slug.'_client_choice',
                'type' => WC_RC_Shipping_Field_Enable::FIELD_RC_ENABLE_CHECKBOX,
                'default' => $service['client_choice'],
                'desc' => __('This option will be visible to the customer in the checkout', 'relais-colis-woocommerce'),
                'readonly' => true,
                'disabled' => true
            ];

            if( $slug === "two_person_delivery" || 
            $slug === "setup_large_appliances" ||
            $slug === "oversized_items" ||
            $slug === "removal_old_equipment"){
                $productChoice = 'yes';
            }else{
                $productChoice = 'no';
            }


            $settings[] = [
                'title' => __( 'Product Choice', 'relais-colis-woocommerce' ),
                'id' => self::SECTION_SERVICES.'_'.$slug.'_product_choice',
                'type' => WC_RC_Shipping_Field_Enable::FIELD_RC_ENABLE_CHECKBOX,
                'default' => $productChoice,
                'desc' => __('Activate: You can choose the products associated with this service / Deactivate: The service will be available for all products.', 'relais-colis-woocommerce'),
                'readonly' => true,
                'disabled' => true
            ];

            if( $slug === "two_person_delivery" || 
            $slug === "setup_large_appliances" ||
            $slug === "oversized_items" ||
            $slug === "removal_old_equipment"
            ) {
                $settings[] = [
                    'type'          => WC_RC_Shipping_Field_Multiselect_Products::FIELD_RC_MULTISELECT_PRODUCTS,
                    'title'         => __( 'Assigned Products', 'relais-colis-woocommerce' ),
                    'id'            => self::SECTION_SERVICES.'_'.$slug.'_products',
                    'default'       => WP_Services_DAO::instance()->get_selected_products( $service['id'] ),
                    'desc'          => __( 'Select products for this service', 'relais-colis-woocommerce' ),
                    'service_id'    => $service['id'],
                    'class'         => WC_RC_Shipping_Field_Multiselect_Products::FIELD_RC_MULTISELECT_PRODUCTS,
                    ];
            }

            $settings[] = [
                'type' => WC_RC_Shipping_Field_Enable::FIELD_RC_ENABLE_CHECKBOX,
                'title' => __( 'Active', 'relais-colis-woocommerce' ),
                'id' => self::SECTION_SERVICES.'_'.$slug.'_enabled',
                'default' => $service['enabled'],
            ];

            $settings[] = [
                'type' => 'hidden',
                'title' => __( 'Price', 'relais-colis-woocommerce' ),
                'id' => self::SECTION_SERVICES.'_'.$slug.'_price',
                'default' => $service['price'],
            ];

            // End of section
            $settings[] = [
                'type' => 'sectionend',
                'id' => self::SECTION_SERVICES.'_'.$slug.'_end',
            ];

        }

        return $settings;
    }
}
