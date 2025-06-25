<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WC_WooCommerce_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Order;

/**
 * WooCommerce Order list Manager.
 *
 * @since     1.0.0
 */
class WC_Orders_List_Table_Manager {

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

        // Legacy – for CPT-based orders            => HPOS-based orders
        //  pre_get_posts                           => woocommerce_order_query_args
        //  restrict_manage_posts                   => woocommerce_order_list_table_restrict_manage_orders
        //  manage_edit-shop_order_columns          => manage_woocommerce_page_wc-orders_columns
        //  manage_shop_order_posts_custom_column   => manage_woocommerce_page_wc-orders_custom_column
        //  bulk_actions-edit-shop_order            => bulk_actions-woocommerce_page_wc-orders
        //  handle_bulk_actions-edit-shop_order     => handle_bulk_actions-woocommerce_page_wc-orders

        // Add a custom column in orders list table
        // Legacy – for CPT-based orders
        add_filter( 'manage_edit-shop_order_columns', array( $this, 'filter_manage_woocommerce_page_wc_orders_columns' ), 9999, 1 );
        // HPOS-based orders
        add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'filter_manage_woocommerce_page_wc_orders_columns' ), 9999, 1 );

        // Display value for custom column
        // Legacy – for CPT-based orders
        add_action( 'manage_shop_order_posts_custom_column', array( $this, 'action_manage_woocommerce_page_wc_orders_custom_column' ), 10, 2 );
        // HPOS-based orders
        add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'action_manage_woocommerce_page_wc_orders_custom_column' ), 10, 2 );

        // Add a sortable column
        // Legacy – for CPT-based orders
        add_filter( 'manage_edit-shop_order_sortable_columns', array( $this, 'filter_manage_woocommerce_page_wc_orders_sortable_columns' ), 10, 1 );
        // HPOS-based orders
        add_filter( 'manage_woocommerce_page_wc-orders_sortable_columns', array( $this, 'filter_manage_woocommerce_page_wc_orders_sortable_columns' ), 10, 1 );

        // Order the sortable column
        // HPOS-based orders
        add_filter( 'woocommerce_order_query_args', array( $this, 'filter_woocommerce_order_query_args' ), 10, 1 );
        // Legacy – for CPT-based orders
        add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ), 10, 1 );

        // Add a filter in orders list table
        // Legacy – for CPT-based orders
        add_action( 'restrict_manage_posts', array( $this, 'action_restrict_manage_posts' ), 10, 1 );
        // HPOS-based orders
        add_action( 'woocommerce_order_list_table_restrict_manage_orders', array( $this, 'action_woocommerce_order_list_table_restrict_manage_orders' ), 10 );
    }

    /**
     * Add a custom column in orders list table
     * @param array $columns a list of columns
     * @return mixed the new list of columns
     */
    public function filter_manage_woocommerce_page_wc_orders_columns( $columns ) {

        WP_Log::debug( __METHOD__, [ '$columns' => $columns ], 'relais-colis-woocommerce' );

        $columns[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SHIPPING_METHOD ] = __( 'RC Shipping method', 'relais-colis-woocommerce' );
        $columns[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE ] = __( 'RC State', 'relais-colis-woocommerce' );
        return $columns;
    }

    /**
     * Display value for custom columns
     * @param string $column the column
     * @param int $post_id the current post in list
     * @return void
     */
    public function action_manage_woocommerce_page_wc_orders_custom_column( $column, $order_or_order_id ) {

        WP_Log::debug( __METHOD__, [ '$column' => $column, '$order_or_order_id' => $order_or_order_id ], 'relais-colis-woocommerce' );

        switch ( $column ) {
            case WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SHIPPING_METHOD:
                // Get WC order
                // Legacy CPT-based order compatibility
                $wc_order = $order_or_order_id instanceof WC_Order ? $order_or_order_id : wc_get_order( $order_or_order_id );

                // Check if the shipping method is "Relais Colis"
                $rc_shipping_method = WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $wc_order );
                if ( $rc_shipping_method !== false ) {

                    echo esc_html( WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method_name( $rc_shipping_method ) );
                } else echo '';
                break;
            case WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE:
                // Get WC order
                // Legacy CPT-based order compatibility
                $wc_order = $order_or_order_id instanceof WC_Order ? $order_or_order_id : wc_get_order( $order_or_order_id );

                // Check if the shipping method is "Relais Colis"
                $rc_shipping_method = WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $wc_order );
                if ( $rc_shipping_method !== false ) {

                    // Get order state
                    $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

                    echo esc_html( WC_RC_Shipping_Constants::get_order_state_title( $order_state ) );
                } else echo '';

                break;
            default: // Does nothing
                break;
        }
    }

    /**
     * Add a sortable column
     * @param array $sortable_columns the list of sortable columns
     * @return mixed
     */
    public function filter_manage_woocommerce_page_wc_orders_sortable_columns( $sortable_columns ) {

        WP_Log::debug( __METHOD__, [ '$sortable_columns' => $sortable_columns ], 'relais-colis-woocommerce' );

        $sortable_columns[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SHIPPING_METHOD ] = 'by_rc_shipping_method';
        $sortable_columns[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE ] = 'by_rc_state';
        return $sortable_columns;
    }

    /**
     * Order and filter the sortable column
     * Legacy – for CPT-based orders
     * @param $query
     * @return mixed|void
     */
    public function action_pre_get_posts( $query ) {

        if ( WC_WooCommerce_Manager::instance()->is_hpos_enabled() ) return;

        global $pagenow, $typenow;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( is_admin() && !empty( $_GET[ 'orderby' ] ) && !empty( $_GET[ 'order' ] ) && ( $_GET[ 'orderby' ] == 'by_rc_shipping_method' ) ) {

            WP_Log::debug( __METHOD__, [ '$query' => $query ], 'relais-colis-woocommerce' );

            // Order by custom meta data rc_shipping_method
            // Legacy – for CPT-based orders
            $query->set( 'meta_key', WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SHIPPING_METHOD );
            $query->set( 'orderby', 'meta_value' );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } else if ( is_admin() && !empty( $_GET[ 'orderby' ] ) && !empty( $_GET[ 'order' ] ) && ( $_GET[ 'orderby' ] == 'by_rc_state' ) ) {

            WP_Log::debug( __METHOD__, [ '$query' => $query ], 'relais-colis-woocommerce' );

            // Order by custom meta data rc_shipping_method
            // Legacy – for CPT-based orders
            $query->set( 'meta_key', WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );
            $query->set( 'orderby', 'meta_value' );

        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $pagenow === 'edit.php' && $typenow === 'shop_order' && isset( $_GET[ 'filter_rc_shipping_method' ] ) && !empty( $_GET[ 'filter_rc_shipping_method' ] ) ) {

            // Get chosen shipping method from GET
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $rc_shipping_method = sanitize_text_field( $_GET[ 'filter_rc_shipping_method' ] );

            $query->set( 'meta_query', array_merge(
                $query->get( 'meta_query' ) ?: array(),
                array(
                    array(
                        'key' => WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SHIPPING_METHOD,
                        'value' => $rc_shipping_method,
                        'compare' => '='
                    )
                )
            ) );

            WP_Log::debug( __METHOD__, [ '$rc_shipping_method' => $rc_shipping_method, '$query' => $query ], 'relais-colis-woocommerce' );
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $pagenow === 'edit.php' && $typenow === 'shop_order' && isset( $_GET[ 'filter_rc_state' ] ) && !empty( $_GET[ 'filter_rc_state' ] ) ) {

            // Get chosen state method from GET
            $rc_state = sanitize_text_field( $_GET[ 'filter_rc_state' ] );

            $query->set( 'meta_query', array_merge(
                $query->get( 'meta_query' ) ?: array(),
                array(
                    array(
                        'key' => WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE,
                        'value' => $rc_state,
                        'compare' => '='
                    )
                )
            ) );

            WP_Log::debug( __METHOD__, [ '$rc_state' => $rc_state, '$query' => $query ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * Order and filter the sortable column
     * HPOS-based orders
     * @param $query
     * @return mixed|void
     */
    public function filter_woocommerce_order_query_args( $query_vars ) {

        if ( !WC_WooCommerce_Manager::instance()->is_hpos_enabled() ) return $query_vars;

        global $pagenow;

        if ( is_admin() && !empty( $_GET[ 'orderby' ] ) && !empty( $_GET[ 'order' ] ) && ( $_GET[ 'orderby' ] == 'by_rc_shipping_method' ) ) {

            WP_Log::debug( __METHOD__, [ '$query_vars' => $query_vars ], 'relais-colis-woocommerce' );

            // Order by custom meta data rc_shipping_method
            $query_vars[ 'orderby' ] = 'meta_value';
            $query_vars[ 'order' ] = isset( $_GET[ 'order' ] ) && strtoupper( $_GET[ 'order' ] ) === 'DESC' ? 'DESC' : 'ASC';
            $query_vars[ 'meta_key' ] = WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SHIPPING_METHOD;


        } else if ( is_admin() && !empty( $_GET[ 'orderby' ] ) && !empty( $_GET[ 'order' ] ) && ( $_GET[ 'orderby' ] == 'by_rc_state' ) ) {

            WP_Log::debug( __METHOD__, [ '$query_vars' => $query_vars ], 'relais-colis-woocommerce' );

            // Order by custom meta data rc_shipping_method
            $query_vars[ 'orderby' ] = 'meta_value';
            $query_vars[ 'order' ] = isset( $_GET[ 'order' ] ) && strtoupper( $_GET[ 'order' ] ) === 'DESC' ? 'DESC' : 'ASC';
            $query_vars[ 'meta_key' ] = WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE;


        }
        if ( is_admin() && isset( $_GET[ 'filter_rc_shipping_method' ] ) && !empty( $_GET[ 'filter_rc_shipping_method' ] ) ) {

            // Get chosen shipping method from GET
            $rc_shipping_method = sanitize_text_field( $_GET[ 'filter_rc_shipping_method' ] );

            $query_vars[ 'meta_query' ][] = [
                'key' => WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SHIPPING_METHOD,
                'value' => $rc_shipping_method,
                'compare' => '='
            ];

            WP_Log::debug( __METHOD__, [ '$rc_shipping_method' => $rc_shipping_method, '$query_vars' => $query_vars ], 'relais-colis-woocommerce' );
        }
        if ( is_admin() && isset( $_GET[ 'filter_rc_state' ] ) && !empty( $_GET[ 'filter_rc_state' ] ) ) {

            // Get chosen shipping method from GET
            $rc_state = sanitize_text_field( $_GET[ 'filter_rc_state' ] );

            $query_vars[ 'meta_query' ][] = [
                'key' => WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE,
                'value' => $rc_state,
                'compare' => '='
            ];

            WP_Log::debug( __METHOD__, [ '$rc_state' => $rc_state, '$query_vars' => $query_vars ], 'relais-colis-woocommerce' );
        }
        return $query_vars;
    }

    /**
     * Add a filter in orders list table
     * HPOS-based orders
     * @param $post_type
     * @return void
     */
    public function action_woocommerce_order_list_table_restrict_manage_orders() {

        $this->action_restrict_manage_posts( 'shop_order' );
    }

    /**
     * Add a filter in orders list table
     * Legacy – for CPT-based orders
     * @param $post_type
     * @return void
     */
    public function action_restrict_manage_posts( $post_type ) {

        WP_Log::debug( __METHOD__, [ '$post_type' => $post_type ], 'relais-colis-woocommerce' );

        if ( $post_type !== 'shop_order' ) {
            return;
        }

        // List of available shipping methods
        $shipping_methods = [
            '' => __( 'All Relais Colis shipping methods', 'relais-colis-woocommerce' )
        ];
        $shipping_methods = array_merge( $shipping_methods, WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_methods() );

        // Get current filtered value
        $current_shipping_method = isset( $_GET[ 'filter_rc_shipping_method' ] ) ? $_GET[ 'filter_rc_shipping_method' ] : '';

        echo '<select name="filter_rc_shipping_method" id="dropdown_rc_shipping_methods">';
        foreach ( $shipping_methods as $key => $label ) {

            printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $current_shipping_method, $key, false ), esc_html( $label ) );
        }
        echo '</select>';

        // List of available order states
        $rc_order_states = [
            '' => __( 'All Relais Colis order states', 'relais-colis-woocommerce' )
        ];
        $rc_order_states = array_merge( $rc_order_states, WC_RC_Shipping_Constants::get_order_states() );

        // Get current filtered value
        $current_rc_state = isset( $_GET[ 'filter_rc_state' ] ) ? $_GET[ 'filter_rc_state' ] : '';

        echo '<select name="filter_rc_state" id="dropdown_rc_state">';
        foreach ( $rc_order_states as $key => $label ) {

            printf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $current_rc_state, $key, false ), esc_html( $label ) );
        }
        echo '</select>';
    }
}
