<?php

namespace RelaisColisWoocommerce;

use RelaisColisWoocommerce\Shipping\WC_Relacoof_Customer_Orders_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Emails_Orders_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Order_Packages_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_B2c_Bulk_Auto_Distribute_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_B2c_Bulk_Generate_Way_Bills_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_B2c_Bulk_Place_Labels_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_B2c_Bulk_Print_Shipping_Labels_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_C2c_Bulk_Actions_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_C2c_Bulk_Place_Labels_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_C2c_Bulk_Print_Shipping_Labels_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_C2c_Bulk_Auto_Distribute_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_C2c_Csv_Export_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_List_Table_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Orders_RC_Status_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Home_Choose_Services_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Homeplus_Choose_Services_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Relay_Choose_Relay_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Shipping_Config_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Shipping_Method_Manager;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Shipping_Settings_Manager;
use RelaisColisWoocommerce\Cron\WP_Relacoof_Cron_Manager;
// use RelaisColisWoocommerce\Tests\Relais_Colis_Woocommerce_Tests;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\WP_Plugin;
use RelaisColisWoocommerce\Shipping\WC_Relacoof_Order_Shipping_Infos_Manager;

defined( 'ABSPATH' ) or exit;

/**
 * Relais Colis Woocommerce main class.
 *
 * @since 1.0.0
 */
class Relais_Colis_Woocommerce extends WP_Plugin {

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

        // Managers
        WC_Relacoof_Shipping_Method_Manager::instance();
        WC_Relacoof_Shipping_Config_Manager::instance();
        WC_Relacoof_Shipping_Settings_Manager::instance();
        WC_Relacoof_WooCommerce_Manager::instance();
        WC_Relacoof_Orders_Manager::instance();
        WC_Relacoof_Orders_List_Table_Manager::instance();
        WC_Relacoof_Services_Manager::instance();
        WC_Relacoof_Home_Choose_Services_Manager::instance();
        WC_Relacoof_Homeplus_Choose_Services_Manager::instance();
        WC_Relacoof_Relay_Choose_Relay_Manager::instance();
        WC_Relacoof_Order_Packages_Manager::instance();
        WC_Relacoof_Orders_RC_Status_Manager::instance();
        WC_Relacoof_Orders_C2c_Csv_Export_Manager::instance();
        WC_Relacoof_Customer_Orders_Manager::instance();
        WC_Relacoof_Emails_Orders_Manager::instance();
        WC_Relacoof_Orders_B2c_Bulk_Auto_Distribute_Manager::instance();
        WC_Relacoof_Orders_B2c_Bulk_Place_Labels_Manager::instance();
        WC_Relacoof_Orders_B2c_Bulk_Print_Shipping_Labels_Manager::instance();
        WC_Relacoof_Orders_B2c_Bulk_Generate_Way_Bills_Manager::instance();
        WP_Relacoof_Cron_Manager::instance();
        WC_Relacoof_Orders_C2c_Bulk_Auto_Distribute_Manager::instance();
        WC_Relacoof_Orders_C2c_Bulk_Place_Labels_Manager::instance();
        WC_Relacoof_Orders_C2c_Bulk_Print_Shipping_Labels_Manager::instance();

                
        // Ajout de l'instanciation de WC_Relacoof_Order_Shipping_Infos_Manager
        WC_Relacoof_Order_Shipping_Infos_Manager::instance();

        // TESTS
        // Relais_Colis_Woocommerce_Tests::instance();
    }

    /**
     * Initializes the custom post types.
     * Called on init and on activation hooks
     *
     * Must be override to create custom post types for the plugin
     *
     * @since 1.0.0
     */
    public function init_custom_post_types() {

        // Place here your code to init your custom post types or taxonomies...
    }
}
