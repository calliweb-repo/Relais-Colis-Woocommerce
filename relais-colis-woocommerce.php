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
require_once __DIR__ . '/vendor/autoload.php';

// Require autoload for this current plugin
require_once __DIR__ . '/autoload.php';

// WordPress Framework
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
     * @since 1.0.0
     *
     * @return The plugin main instance
     */
    public function get_plugin() {

        return Relais_Colis_Woocommerce::instance();
    }

    /**
     * Gets the plugin update URL
     * This is used to link user when plugin need to be updated
     *
     * @since 1.0.0
     *
     * @return string plugin update URL
     */
    public function get_update_url() {

        return $this->get_plugin_uri();
    }

    /**
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @since 1.0.0
     *
     * @return string plugin settings URL
     */
    public function get_settings_url() {

        // FIXME link to WooC
        return $this->get_plugin_uri();
    }

    /**
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @since 1.0.0
     *
     * @return string documentation URL
     */
    public function get_documentation_url() {

        return $this->get_plugin_uri().'/documentation';
    }

    /**
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_support_url() {

        return $this->get_plugin_uri().'/support';
    }

    /**
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @since 1.0.0
     *
     * @return string
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
    public function action_wp_enqueue_scripts() {}


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

        $table_activation_options = $wpdb->prefix . 'rc_configuration_options';

        $sql = "        
            CREATE TABLE $table_activation_options (
                id INT AUTO_INCREMENT PRIMARY KEY,
                option_id INT,
                name VARCHAR(255),
                value VARCHAR(50),
                active BOOLEAN,
                user_choice BOOLEAN,
                delivery_method VARCHAR(50),
                price DECIMAL(10,2),
                products TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) $charset_collate;
            ";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }


    /**
     * Plugin deactivation method. Perform any deactivation tasks here.
     *
     * @since 1.0.0
     */
    public function deactivate(){

        global $wpdb;

        // Récupérer les noms des tables avec le préfixe WordPress
        $table_activation_options = $wpdb->prefix . 'rc_configuration_options';

        // Supprimer les tables
        $wpdb->query( "DROP TABLE IF EXISTS $table_activation_options" );
    }
}
Relais_Colis_Woocommerce_Loader::instance();