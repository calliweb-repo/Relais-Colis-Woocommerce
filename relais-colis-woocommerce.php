<?php
/**
 * Plugin Name: Relais Colis Woocommerce
 * Plugin URI: https://www.relaiscolis.com/
 * Description: Adds Relais Colis shipping method to WooCommerce.
 * Version: 1.0.0
 * Requires at least: 6.6.2
 * Requires PHP: 8.1
 * Author: Calliweb
 * Author URI: https://www.calliweb.fr/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: relais-colis-woocommerce
 * Domain Path: /languages
 *
 * Copyright: (c) 2021-2022 Sukellos, SARL (youremail@yourcompany.com)
 *
 * @package   Relais-Colis-Woocommerce
 * @author    Calliweb
 * @category  Admin
 * @copyright Copyright (c) 2024 Calliweb
 */

namespace RelaisColisWoocommerce;

defined( 'ABSPATH' ) or exit;

// Require vendor autoloads to be able to Use all frameworks namespaces
require_once __DIR__.'/vendor/autoload.php';

// Require autoload for this current plugin
require_once __DIR__.'/autoload.php';

// WordPress Framework
use RelaisColisWoocommerce\DAO\WP_Services_DAO;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use RelaisColisWoocommerce\WPFw\WP_PLoad;

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
        return $this->get_plugin_uri();
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

        return $this->get_plugin_uri().'/documentation';
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

        return $this->get_plugin_uri().'/support';
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

        return $this->get_plugin_uri().'/';
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
                min_value DECIMAL(10,2) NOT NULL,
                max_value DECIMAL(10,2)  DEFAULT NULL,
                price DECIMAL(10,2) NOT NULL
            ) $charset_collate;";

        require_once( ABSPATH.'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_configuration_options );
        dbDelta( $sql_services );
        dbDelta( $sql_services_rel_products );
        dbDelta( $sql_tariff_grids );

        // Init services
        WP_Services_DAO::instance()->initialize_rc_services();
    }


    /**
     * Plugin deactivation method. Perform any deactivation tasks here.
     *
     * @since 1.0.0
     */
    public function deactivate() {

        global $wpdb;

        // Récupérer les noms des tables avec le préfixe WordPress
        $table_activation_options = $wpdb->prefix.'rc_configuration_options';

        // Supprimer les tables
        $wpdb->query( "DROP TABLE IF EXISTS $table_activation_options" );
    }
}

Relais_Colis_Woocommerce_Loader::instance();