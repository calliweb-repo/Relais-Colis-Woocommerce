<?php

namespace RelaisColisWoocommerce\WPFw;

defined( 'ABSPATH' ) or exit;

/**
 * Wordpress Generic Plugin
 *
 * @version 1.0.0
 */
abstract class WP_Plugin {

    /**
     * Plugin activated method. Perform any activation tasks here.
     * Note that this _does not_ run during upgrades.
     *
     * @since 1.0.0
     */
    abstract public function activate();


    /**
     * Plugin deactivation method. Perform any deactivation tasks here.
     *
     * @since 1.0.0
     */
    abstract public function deactivate();

    /**
     * Initializes the custom post types.
     * Called on init and on activation hooks
     * 
     * Must be override to create custom post types for the plugin
     *
     * @since 1.0.0
     */
    abstract public function init_custom_post_types();
}
