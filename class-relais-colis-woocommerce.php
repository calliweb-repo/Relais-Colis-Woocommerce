<?php

namespace RelaisColisWoocommerce;

use RelaisColisWoocommerce\Tests\Relais_Colis_Woocommerce_Tests;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use RelaisColisWoocommerce\WPFw\WP_Plugin;

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

        // Place here your code to init your functionalities...


        // TESTS
        Relais_Colis_Woocommerce_Tests::instance();

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );
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

    /**
     * Plugin activated method. Perform any activation tasks here.
     * Note that this _does not_ run during upgrades.
     *
     * @since 1.0.0
     */
    public function activate() {

        // Place here your code to init / update DB tables...
    }


    /**
     * Plugin deactivation method. Perform any deactivation tasks here.
     *
     * @since 1.0.0
     */
    public function deactivate(){

        // Place here your code to remove DB tables...
    }
}
