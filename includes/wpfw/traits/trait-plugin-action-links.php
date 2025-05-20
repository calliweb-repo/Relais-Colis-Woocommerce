<?php

namespace RelaisColisWoocommerce\WPFw\Traits;

defined( 'ABSPATH' ) or exit;

/**
 * Plugin action links
 * @see https://www.php.net/manual/fr/language.oop5.traits.php
 *
 * @since 1.0.0
 */
trait Plugin_Action_Links {

    private $plugin_file = null;

    /**
     * Init
     */
    public function init_action_links( $plugin_file ) {

        $this->plugin_file = $plugin_file;

        // Filters the list of action links displayed for a specific plugin in the Plugins list table.
        add_filter( 'plugin_action_links', array( $this, 'filter_plugin_action_links' ), 10, 4 );
    }

    /**
     * Gets the plugin update URL
     * This is used to link user when plugin need to be updated
     *
     * @since 1.0.0
     *
     * @return string plugin update URL
     */
    abstract public function get_update_url();

    /**
     * Gets the plugin configuration URL
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @since 1.0.0
     *
     * @return string plugin settings URL
     */
    abstract public function get_settings_url();


    /**
     * Gets the plugin documentation URL.
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @since 1.0.0
     *
     * @return string documentation URL
     */
    abstract public function get_documentation_url();


    /**
     * Gets the plugin support URL.
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_support_url();


    /**
     * Gets the plugin sales page URL.
     * This is used to build actions list in plugins page
     * Leave blank ('') to disable
     *
     * @since 1.0.0
     *
     * @return string
     */
    abstract public function get_sales_page_url();

    /**
     *          ===============
     *      =======================
     *  ============ HOOKS ===========
     *      =======================
     *          ===============
     */

    /**
     * Filters the list of action links displayed for a specific plugin in the Plugins list table.
     * See https://developer.wordpress.org/reference/hooks/plugin_action_links_plugin_file/
     *
     * @param $actions (string[]) An array of plugin action links. By default this can include 'activate', 'deactivate', and 'delete'. With Multisite active this can also include 'network_active' and 'network_only' items.
     * @param $plugin_file (string) Path to the plugin file relative to the plugins directory.
     * @param $plugin_data (array) An array of plugin data. See get_plugin_data() and the 'plugin_row_meta' filter for the list of possible values.
     * @param $context (string) The plugin context. By default this can include 'all', 'active', 'inactive', 'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
     */
    public function filter_plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) {

        // Get current plugin file
        $my_plugin_file = $this->plugin_file;

        if ( strpos( $my_plugin_file, $plugin_file ) === FALSE ) {

            return $actions;
        }

        // Add some infos in action links
        if ( !is_null( $this->get_settings_url() ) && ( $this->get_settings_url() !== '' )  ) {

            $actions[] = '<a href="'. esc_url( $this->get_settings_url() ) .'">'.__( 'Settings', 'relais-colis-woocommerce' ).'</a>';
        }
        if ( !is_null( $this->get_documentation_url() ) && ( $this->get_documentation_url() !== '' )  ) {

            $actions[] = '<a href="'. esc_url( $this->get_documentation_url() ) .'" target="_blank">'.__( 'Documentation', 'relais-colis-woocommerce' ).'</a>';
        }
        if ( !is_null( $this->get_support_url() ) && ( $this->get_support_url() !== '' )  ) {

            $actions[] = '<a href="'. esc_url( $this->get_support_url() ) .'" target="_blank">'.__( 'Support', 'relais-colis-woocommerce' ).'</a>';
        }


        return $actions;
    }
}
