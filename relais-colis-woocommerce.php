<?php
/**
 * Plugin Name: Relais Colis Woocommerce
 * Plugin URI: https://www.relaiscolis.com/
 * Description: Adds Relais Colis shipping method to WooCommerce.
 * Version: 2.0.3
 * Requires at least: 6.6.2
 * Requires PHP: 8.1
 * Author: Calliweb
 * Author URI: https://www.calliweb.fr/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: relais-colis-woocommerce
 * Domain Path: /languages
 *
 * Copyright: (c) 2025, Calliweb
 *
 * @package   Relais-Colis-Woocommerce
 * @author    Calliweb
 * @category  Admin
 * @copyright Copyright (c) 2024 Calliweb
 */

namespace RelaisColisWoocommerce;

defined( 'ABSPATH' ) or exit;

// Require vendor autoloads to be able to Use all frameworks namespaces
// require_once __DIR__.'/vendor/autoload.php';

// Require autoload for this current plugin
require_once __DIR__.'/autoload.php';

// WordPress Framework
use RelaisColisWoocommerce\DAO\WP_Services_DAO;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use RelaisColisWoocommerce\WPFw\WP_PLoad;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Config_Manager;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\DAO\WP_Configuration_DAO;

use RelaisColisWoocommerce\Cron\WP_Cron_Manager;

/**
 * The loader class.
 *
 * @since 1.0.0
 */
final class Relais_Colis_Woocommerce_Loader extends WP_PLoad {

    // Use Trait Singleton
    use Singleton;

    const CMS_WORDPRESS = 'WordPress';

    // WooCommerce constraint
    protected $min_wc_version = '9.2.3';
    protected $woocommerce_required = true;

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // Set Logger level
        //    'logger_level_emergency' => 'EMERGENCY',
        //    'logger_level_alert' => 'ALERT',
        //    'logger_level_critical' => 'CRITICAL',
        //    'logger_level_error' => 'ERROR',
        //    'logger_level_warning' => 'WARNING',
        //    'logger_level_notice' => 'NOTICE',
        //    'logger_level_info' => 'INFO',
        //    'logger_level_debug' => 'DEBUG',
        update_option( WP_Log::WP_SUKELLOS_FW_LOGGER_LEVEL_OPTION_PREFIX.'relais-colis-woocommerce', 'logger_level_notice' );

        add_action( 'before_woocommerce_init', function () {

            WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

            /**
             * Declare compatibility with a given feature for a given plugin.
             *
             * This method MUST be executed from inside a handler for the 'before_woocommerce_init' hook and
             * SHOULD be executed from the main plugin file passing __FILE__ or 'my-plugin/my-plugin.php' for the
             * $plugin_file argument.
             *
             * @param string $feature_id Unique feature id.
             * @param string $plugin_file The full plugin file path.
             * @param bool $positive_compatibility True if the plugin declares being compatible with the feature, false if it declares being incompatible.
             * @return bool True on success, false on error (feature doesn't exist or not inside the required hook).
             */
            if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
                $compatibility = \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'custom_order_tables',
                    //Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_path(),
                    __FILE__,
                    true
                );
                WP_Log::debug( __METHOD__, [ '$compatibility' => $compatibility ? 'true' : 'false' ], 'relais-colis-woocommerce' );
            }


            // Remove the 'products_enabled' column if it exists
            global $wpdb;
            $table_services = $wpdb->prefix.'rc_services';
            $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM $table_services LIKE 'products_enabled'" );
            if ( !empty( $column_exists ) ) {

                $wpdb->query( "ALTER TABLE $table_services DROP COLUMN products_enabled" );
                WP_Log::debug( __METHOD__, [ 'products_enabled column removed from rc_services' => true ], 'relais-colis-woocommerce' );
            }
        } );

        parent::init();

    }

    /**
     * Must be called in child Loader to get data from the file itself
     */
    public function get_plugin_file() {

        return __FILE__;
    }

    /**
     * Get the plugin instance
     *
     * @return The plugin main instance
     * @since 1.0.0
     *
     */
    public function get_plugin() {

        return Relais_Colis_Woocommerce::instance();
    }

    /**
     * Gets the plugin update URL
     * This is used to link user when plugin need to be updated
     *
     * @return string plugin update URL
     * @since 1.0.0
     *
     */
    public function get_update_url() {

        return $this->get_plugin_uri();
    }

    /**
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @return string plugin settings URL
     * @since 1.0.0
     *
     */
    public function get_settings_url() {

        // FIXME link to WooC
        // return $this->get_plugin_uri();
        return \admin_url( 'admin.php?page=wc-settings&tab=wc_rc_shipping_settings' );
    }

    /**
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @return string documentation URL
     * @since 1.0.0
     *
     */
    public function get_documentation_url() {

        return $this->get_plugin_uri().'mon-compte-professionnel/';
    }

    /**
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @return string
     * @since 1.0.0
     *
     */
    public function get_support_url() {

        return $this->get_plugin_uri().'support-technique/';
    }

    /**
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @return string
     * @since 1.0.0
     *
     */
    public function get_sales_page_url() {

        return $this->get_plugin_uri();
    }

    /**
     *          ===============
     *      =======================
     *  ============ HOOKS ===========
     *      =======================
     *          ===============
     */

    /**
     * Used to enqueue styles and scripts
     */
    public function action_wp_enqueue_scripts() {
    }


    /**
     * Plugin activated method. Perform any activation tasks here.
     * Note that this _does not_ run during upgrades.
     *
     * @since 1.0.0
     */
    public function activate() {

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();


        // Define table names with WordPress table prefix
        $table_activation_options = $wpdb->prefix.'rc_configuration_options';
        $table_services = $wpdb->prefix.'rc_services';
        $table_services_rel_products = $wpdb->prefix.'rc_services_rel_products';
        $table_tariff_grids = $wpdb->prefix.'rc_tariff_grids';
        $table_orders_rel_shipping_labels = $wpdb->prefix.'rc_orders_rel_shipping_labels';

        // SQL for creating the rc_services table
        $sql_services = "
            CREATE TABLE IF NOT EXISTS $table_services (
                id INT AUTO_INCREMENT PRIMARY KEY,         -- Unique ID for each service
                name VARCHAR(255) NOT NULL,               -- Name of the service
                slug VARCHAR(255) NOT NULL,               -- Slug of the service
                client_choice VARCHAR(3) NOT NULL DEFAULT 'no', -- Whether the client can choose this service
                delivery_method VARCHAR(255) NOT NULL,    -- Delivery method associated with the service
                enabled VARCHAR(3) NOT NULL DEFAULT 'no',        -- Whether the service is active
                price DECIMAL(10,2) NOT NULL DEFAULT 0.00 -- Price of the service (default: free)
            ) $charset_collate;

        ";

        // SQL for creating the rc_services_rel_products table
        $sql_services_rel_products = "
            CREATE TABLE IF NOT EXISTS $table_services_rel_products (
                id INT AUTO_INCREMENT PRIMARY KEY,   -- Unique ID for each relation
                service_id INT NOT NULL,             -- Reference to rc_services.id
                product_id BIGINT(20) NOT NULL,      -- Reference to a WooCommerce product ID
                KEY service_id (service_id),         -- Index for service_id
                KEY product_id (product_id)          -- Index for product_id
            ) $charset_collate;
        ";

        // SQL for creating the rc_configuration_options table
        $sql_configuration_options = "        
            CREATE TABLE IF NOT EXISTS $table_activation_options (
                id INT AUTO_INCREMENT PRIMARY KEY,
                option_id INT,
                name VARCHAR(255),
                value VARCHAR(50),
                active BOOLEAN 
            ) $charset_collate;
            ";


        $sql_tariff_grids = "
            CREATE TABLE IF NOT EXISTS $table_tariff_grids (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                method_name VARCHAR(255) NOT NULL,
                criteria ENUM('price', 'weight') NOT NULL,
                shipping_threshold DECIMAL(10,2) NULL,
                min_value DECIMAL(10,3) NOT NULL,
                max_value DECIMAL(10,3)  DEFAULT NULL,
                price DECIMAL(10,2) NOT NULL
            ) $charset_collate;";


        // SQL for creating the rc_orders_rel_shipping_labels table
        $sql_orders_rel_shipping_labels = "
            CREATE TABLE IF NOT EXISTS $table_orders_rel_shipping_labels (
                id INT AUTO_INCREMENT PRIMARY KEY,   -- Unique ID for each relation
                order_id INT NOT NULL,               -- Reference to WooCommerce order ID
                shipping_label VARCHAR(255),         -- Shipping label identifier
                shipping_label_pdf VARCHAR(512),         -- Shipping label PDF
                shipping_status VARCHAR(50),         -- Current shipping status
                last_updated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Last modification time
                KEY shipping_label (shipping_label),
                KEY order_id (order_id),
                KEY last_updated (last_updated)
            ) $charset_collate;
        ";

        require_once( ABSPATH.'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_configuration_options );
        dbDelta( $sql_services );
        dbDelta( $sql_services_rel_products );
        dbDelta( $sql_tariff_grids );
        dbDelta( $sql_orders_rel_shipping_labels );



        // Init services
        WP_Services_DAO::instance()->initialize_rc_services();

        // Get activation key
        $activationKey = get_option( WC_RC_Shipping_Constants::OPTION_ACTIVATION_KEY );
        if ( !is_null($activationKey) && !empty($activationKey) && $activationKey !== '' ) {
            // Update configuration data
            WC_RC_Shipping_Config_Manager::instance()->update_configuration_data();

        }


        // Init cron
        WP_Cron_Manager::instance()->activate();
    }


    /**
     * Plugin deactivation method. Perform any deactivation tasks here.
     *
     * @since 1.0.0
     */
    public function deactivate() {

        // Delete composer.json and vendor directory
        if (file_exists(__DIR__ . '/composer.json')) {
            wp_delete_file(__DIR__ . '/composer.json');
        }
        
        if (is_dir(__DIR__ . '/vendor')) {
            $this->rrmdir(__DIR__ . '/vendor');
        }

        // Deactivate cron
        WP_Cron_Manager::instance()->deactivate();
    }

    /**
     * Recursively remove a directory and its contents
     * 
     * @param string $dir Directory path to remove
     * @return void
     */
    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object)) {
                        $this->rrmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        wp_delete_file($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            // Utilisation de WP_Filesystem pour supprimer le dossier
            if ( ! function_exists( 'WP_Filesystem' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            WP_Filesystem();
            global $wp_filesystem;
            $wp_filesystem->rmdir( $dir );
        }
    }
}

Relais_Colis_Woocommerce_Loader::instance();