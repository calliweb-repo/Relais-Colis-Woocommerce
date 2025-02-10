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
     * Initializes the custom post types.
     * Called on init and on activation hooks
     * 
     * Must be override to create custom post types for the plugin
     *
     * @since 1.0.0
     */
    abstract public function init_custom_post_types();
}
