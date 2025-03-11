<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Orders_Rel_Shipping_Labels_DAO;
use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WC_WooCommerce_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WP_Post;
use WC_Order;

/**
 * WooCommerce Packages Manager.
 *
 * @since     1.0.0,
 */
class WC_Order_Packages_Manager {

    // Use Trait Singleton
    use Singleton;

    const RC_ORDER_PACKAGES = 'rc_order_packages';

    // Packages ar stored as meta-data within Orders
    // '_rc_colis' => [
    //    [items] => [
    //            [0] => [
    //                    [id] => 83
    //                    [name] => Ab.
    //                    [weight] => 120
    //                    [quantity] => 2
    //                    [remaining_quantity] => 0
    //                ]
    //
    //            [1] => [
    //                    [id] => 73
    //                    [name] => Aut.
    //                    [weight] => 25000
    //                    [quantity] => 1
    //                    [remaining_quantity] => 1
    //                ]
    //
    //        )
    //
    //    [colis] => [
    //            [0] => [
    //                    [items] => [
    //                            [83] => 2
    //                        ]
    //
    //                    [weight] => 240
    //                    [dimensions] => [
    //                            [height] => 0
    //                            [width] => 0
    //                            [length] => 0
    //                        ]
    //                    [shipping_label] => 4H013000008101
    //                    [shipping_label_pdf] => <url du PDF>
    //                    [shipping_status] => status_rc_depose_en_relais
    //        ]

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // Add a metabox for packages distribution
        add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ), 10, 2 );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

        // Init AJAX Handler
        WC_RC_Ajax_Packages::instance();
        WC_RC_Ajax_Shipping_Label::instance();
    }

    /**
     * Add a metabox for packages distribution
     * @return void
     */
    public function action_add_meta_boxes( $post_type, $post ) {

        WP_Log::debug( __METHOD__, [ '$post_type' => $post_type, '$post' => $post ], 'relais-colis-woocommerce' );

        if ( ( $post_type !== "woocommerce_page_wc-orders" ) && ( 'shop_order' != get_post_type( get_the_ID() ) ) ) {

            return;
        }

        // Not when adding an order
        $screen = get_current_screen();
        if ( 'add' == $screen->action )
            return;

        // HPOS-based orders
        if ( WC_WooCommerce_Manager::instance()->is_hpos_enabled() ) {

            add_meta_box(
                'rc_woocommerce_colis',
                'Relais Colis - Gestion des colis',
                array( $this, 'rc_woocommerce_colis_callback' ),
                'woocommerce_page_wc-orders',
                'normal'
            );
        } // Legacy – for CPT-based orders
        else {

            add_meta_box(
                'rc_woocommerce_colis',
                'Relais Colis - Gestion des colis',
                array( $this, 'rc_woocommerce_colis_callback' ),
                'shop_order',
                'normal'
            );
        }
    }

    /**
     * Enqueue needed scripts
     */
    public function action_admin_enqueue_scripts() {

        // Get the current screen object
        $screen = get_current_screen();

        // Log for debugging
        WP_Log::debug(__METHOD__, ['screen_id' => $screen->id, 'post_type' => get_post_type()], 'relais-colis-woocommerce' );

        // Ensure we are on a WooCommerce order edit page
        if ($screen && $screen->id !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders') {

            return;
        }

        // CSS
        wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css' );
        wp_enqueue_style( self::RC_ORDER_PACKAGES.'_css', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/order-packages.css', array(), '1.0', 'all' );

        // JS
        wp_enqueue_script( self::RC_ORDER_PACKAGES.'_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/order-packages.js', array( 'jquery' ), '1.0', true );

        // Weight and dimensions unit
        $option_rc_weight_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT );
        $option_rc_length_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_LENGTH_UNIT );

        // Pass script params to JS
        wp_localize_script( self::RC_ORDER_PACKAGES.'_js', 'rc_order_packages', array(
            'nonce' => wp_create_nonce( 'rc_woocommerce_nonce' ),
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'max_per_colis' => 30000,
            // Translations
            'label_remaining' => __( 'remaining', 'relais-colis-woocommerce' ),
            'label_auto_distribute' => __( 'Auto distribute', 'relais-colis-woocommerce' ),
            'label_package' => __( 'Package', 'relais-colis-woocommerce' ),
            'label_add_in_package' => __( 'Add in package', 'relais-colis-woocommerce' ),
            'label_please_add_a_package' => __( 'Please add a package.', 'relais-colis-woocommerce' ),
            'label_products_to_distribute' => __( 'Products to distribute', 'relais-colis-woocommerce' ),
            'label_delete_package' => __( 'Delete Package', 'relais-colis-woocommerce' ),
            'label_pcs' => __( 'pcs', 'relais-colis-woocommerce' ),
            'label_remove_from_package' => __( 'Remove from package', 'relais-colis-woocommerce' ),
            'label_add_a_package' => __( 'Add a package', 'relais-colis-woocommerce' ),
            'label_existing_packages' => __( 'Existing Packages', 'relais-colis-woocommerce' ),
            'label_unknown' => __( 'Unknown', 'relais-colis-woocommerce' ),
            'label_total_weight' => __( 'Total weight', 'relais-colis-woocommerce' ),
            'label_dimensions' => __( 'Dimensions', 'relais-colis-woocommerce' ),
            'label_height' => __( 'height', 'relais-colis-woocommerce' ),
            'label_width' => __( 'width', 'relais-colis-woocommerce' ),
            'label_length' => __( 'length', 'relais-colis-woocommerce' ),
            'label_unit_weight' => __( 'Unit weight', 'relais-colis-woocommerce' ),
            'label_remaining_quantity_to_be_distributed' => __( 'Quantity to be distributed', 'relais-colis-woocommerce' ),
            'label_quantity' => __( 'Quantity', 'relais-colis-woocommerce' ),
            'label_update_package' => __( 'Update package', 'relais-colis-woocommerce' ),
            'label_all_products_assigned' => __( 'All products have been assigned to a package.', 'relais-colis-woocommerce' ),
            'label_weight_units' => $option_rc_weight_unit,
            'label_dimensions_units' => $option_rc_length_unit,
            'label_total' => __( 'Total', 'relais-colis-woocommerce' ),
            'label_recap' => __( 'Summary', 'relais-colis-woocommerce' ),
            'label_place_shipping_label' => __( 'Place shipping label', 'relais-colis-woocommerce' ),
            'label_print_shipping_label' => __( 'Print shipping label', 'relais-colis-woocommerce' ),
            'label_shipping_label' => __( 'Shipping label:', 'relais-colis-woocommerce' ),
        ) );

    }

    /**
     * Count how many times a product is already assigned to packages (colis).
     *
     * @param int $product_id The ID of the product to count.
     * @param array $colis The array of existing packages.
     * @return int The total quantity of the product already distributed in packages.
     */
    public function rc_count_product_in_colis( $product_id, $colis ) {

        $count = 0;

        // Loop through all existing packages to count occurrences of the product.
        foreach ( $colis as $colis_data ) {

            if ( isset( $colis_data[ 'items' ][ $product_id ] ) ) {

                $count += $colis_data[ 'items' ][ $product_id ];
            }
        }

        return $count;
    }

    /**
     * Build the JSON for remaing items, depending on packages distribution
     * @param WC_Order $order
     * @return string
     */
    public function build_remaining_items( WC_Order $order, $colis, $json_encoded=true ) {

        // Get order items (products purchased in the order).
        $items = $order->get_items();
        $items_json = [];

        foreach ( $items as $item_id => $item ) {
            $product = $item->get_product();
            $product_id = $product->get_id();

            $items_json[] = [
                'id' => $product_id,
                'name' => $product->get_name(),
                'weight' => $product->get_weight(),
                'quantity' => $item->get_quantity(),
                'remaining_quantity' => $item->get_quantity() - $this->rc_count_product_in_colis( $product_id, $colis )
            ];
        }
        WP_Log::debug( __METHOD__.' - After rebuilding items', ['$items_json' => $items_json], 'relais-colis-woocommerce' );
        if ( $json_encoded ) $items_json = json_encode( $items_json );
        return $items_json;
    }

    /**
     * Callback for the RC meta box.
     * Displays the interface for managing package distribution within an order.
     *
     * @param WP_Post $post The order post object.
     */
    public function rc_woocommerce_colis_callback( $post ) {

        // Log the method execution for debugging.
        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        // Retrieve the order instance from WooCommerce.
        $order = wc_get_order( $post->ID );

        // Fetch existing package distribution data (Legacy & HPOS support).
        $colis = method_exists( $order, 'get_meta' ) ?
            $order->get_meta( '_rc_colis', true ) :
            get_post_meta( $post->ID, '_rc_colis', true );

        $colis = $colis ?: [];
        WP_Log::notice( __METHOD__.' - Init colis from meta data', ['$colis'=>$colis], 'relais-colis-woocommerce' );

        // Reindex to avoid holes
        $colis = is_array($colis) ? array_values($colis) : [];

        //    [colis] => [
        //            [0] => [
        //                    [items] => [
        //                            [83] => 2
        //                        ]
        //
        //                    [weight] => 240
        //                    [dimensions] => [
        //                            [height] => 0
        //                            [width] => 0
        //                            [length] => 0
        //                        ]
        //                    [shipping_label] => 4H013000008101
        //                    [shipping_label_pdf] => <url du PDF>
        //                    [shipping_status] => status_rc_depose_en_relais
        //        ]
        foreach ( $colis as &$c_colis ) {

            if ( array_key_exists( 'shipping_label', $c_colis ) ) {

                $shipping_label = $c_colis['shipping_label'];

                // Get shipping status
                $shipping_status = WP_Orders_Rel_Shipping_Labels_DAO::instance()->get_shipping_status_by_shipping_label( $shipping_label );

                if ( !is_null( $shipping_status ) && ( $shipping_status !== WC_RC_Shipping_Constants::STATUS_RC_PENDING ) ) {

                    $c_colis['shipping_status'] = WC_RC_Shipping_Constants::get_rc_status_title( $shipping_status );
                }
            }
        }
        WP_Log::notice( __METHOD__.' - Updated colis with shipping statuses', ['$colis'=>$colis], 'relais-colis-woocommerce' );

        // Prepare JSON data to pass to JavaScript
        $colis_json = json_encode( $colis );

        // Prepare item list JSON data
        $items_json = $this->build_remaining_items( $order, $colis );

        // Inject JSON data into JavaScript
        echo "<script>
            var rc_order_colis = $colis_json;
            var rc_order_items = $items_json;
            var rc_order_id = ".esc_js( $post->ID ).";
          </script>";

        // Empty container where JavaScript will generate the UI dynamically
        echo '<div id="rc-colis-container"></div>';
    }
}
