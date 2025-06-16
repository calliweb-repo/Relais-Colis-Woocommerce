<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WC_WooCommerce_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * Abstract class WC_Orders_B2c_Bulk_Actions_Manager to facilitate all B2C RC bulk actions
 * Manage Bulk actions in B2C mode:
 * - Place shipping labels
 * - Print shipping labels
 * - Place way bills
 * - Auto distribute
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
abstract class WC_Orders_B2c_Bulk_Actions_Manager {

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // Only available in B2C mode
        if ( WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode() ) return;

        add_action( 'woocommerce_init', function () {

            // HPOS
            if ( WC_WooCommerce_Manager::instance()->is_hpos_enabled() ) {

                // Support for HPOS (High Performance Order Storage)
                add_action( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'filter_bulk_actions_edit_shop_order' ) );
                add_action( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'filter_handle_bulk_actions_edit_shop_order' ), 10, 3 );
            }
            // Legacy mode
            else {

                // Add a custom bulk action for exporting orders to CSV
                add_filter( 'bulk_actions-edit-shop_order', array( $this, 'filter_bulk_actions_edit_shop_order' ), 10, 1 );

                // Handle the bulk export action
                add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'filter_handle_bulk_actions_edit_shop_order' ), 10, 3 );
            }
        });

        /**
         * Notify 3rd party code on autodistribute result
         *
         * @param int $order_id The order ID
         * @param boolean $is_success true if success, otherwise false
         * @param string $message A message associated with the hook
         * @since 1.0.0
         *
         */
        add_action( 'after_bulk_actions_rc_shop_order', array( $this, 'action_after_bulk_actions_rc_shop_order' ), 10, 3 );

        // Use admin_notices hook to display messages, after redirect
        add_action( 'admin_notices', array( $this, 'action_admin_notices' ), 10 );
    }

    /**
     * Notify 3rd party code on autodistribute result
     *
     * @param int $order_id The order ID
     * @param boolean $is_success true if success, otherwise false
     * @param string $message A message associated with the hook
     * @since 1.0.0
     *
     */
    public function action_after_bulk_actions_rc_shop_order( $order_id, $is_success, $message ) {

        WP_Log::debug( __METHOD__, [ '$order_id' => $order_id, '$is_success' => $is_success ? 'true' : 'false', '$message' => $message ], 'relais-colis-woocommerce' );

        // Store result in a transient to get it after redirection
        $results = get_transient( 'rc_bulk_action_results' ) ?: [];
        $results[ $order_id ] = [ 'is_success' => $is_success, 'message' => $message ];
        set_transient( 'rc_bulk_action_results', $results, 60 );
    }

    /**
     * Display result and reset messages
     * @return void
     */
    public function action_admin_notices() {

        // Get result store in a transient
        $results = get_transient( 'rc_bulk_action_results' );

        if ( !empty( $results ) ) {

            foreach ( $results as $order_id => $result ) {

                $notice_class = $result[ 'is_success' ] ? 'updated' : 'error';
                printf(
                    '<div class="%s notice is-dismissible"><p>%s %s - %s</p></div>',
                    esc_attr( $notice_class ),
                    esc_html__( 'Order', 'relais-colis-woocommerce' ),
                    esc_html( $order_id ),
                    wp_kses_post( $result['message'] )
                );
            }
        }
        // Delete transient after usage
        delete_transient( 'rc_bulk_action_results' );
    }

    /**
     * Add a custom bulk action for exporting orders to CSV
     *
     * @param array $bulk_actions The existing bulk actions
     * @return array Updated bulk actions with our custom action
     */
    public function filter_bulk_actions_edit_shop_order( $bulk_actions ) {

        $action_slug = $this->get_specific_bulk_action_slug();
        $action_title = $this->get_specific_bulk_action_title();

        $bulk_actions[ $action_slug ] = $action_title;
        return $bulk_actions;
    }

    /**
     * Handle the bulk export action
     *
     * @param string $redirect_url The URL to redirect to after processing
     * @param string $action The action being processed
     * @param array $order_ids The selected order IDs
     * @return string Updated redirect URL
     */
    public function filter_handle_bulk_actions_edit_shop_order( $redirect_url, $action, $order_ids ) {

        if ( $action !== $this->get_specific_bulk_action_slug() ) return $redirect_url;

        // Check user permissions
        if ( !current_user_can( 'manage_woocommerce' ) ) {

            wp_die( esc_html__( 'You do not have sufficient permissions to bulk auto distribute items in packages.', 'relais-colis-woocommerce' ) );
        }

        // Bulk action
        WP_Log::debug( __METHOD__, [ '$redirect_url' => $redirect_url, '$action' => $action, '$order_ids' => $order_ids ], 'relais-colis-woocommerce' );
        $this->handle_specific_bulk_actions( $order_ids );

        return $redirect_url;
    }

    /**
     * Template Method
     * Get the slug used to attach bulk action
     * @return mixed
     */
    abstract protected function get_specific_bulk_action_slug();

    /**
     * Template Method
     * Get the title used to attach bulk action
     * @return mixed
     */
    abstract protected function get_specific_bulk_action_title();

    /**
     * Template Method
     * Specific execution of the bulk action
     * @param array $order_ids the order IDs
     * @return mixed
     */
    abstract protected function handle_specific_bulk_actions( $order_ids );
}
