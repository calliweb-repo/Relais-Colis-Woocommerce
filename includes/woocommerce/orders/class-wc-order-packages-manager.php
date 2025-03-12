<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Orders_Rel_Shipping_Labels_DAO;
use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WC_WooCommerce_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WP_Post;
use Exception;
use WC_Order;

/**
 * Class WC_Order_Packages_Manager
 *
 * This class manages the distribution of products into multiple packages (colis) within WooCommerce orders.
 * It provides a dedicated WooCommerce admin meta box, allowing merchants to allocate items to packages,
 * generate shipping labels, and track package statuses.
 *
 * ## 🛠️ Key Features:
 * - **Meta Box Integration**: Adds an interactive interface in WooCommerce order management for package allocation.
 * - **HPOS & Legacy Compatibility**: Fully compatible with both WooCommerce High-Performance Order Storage (HPOS)
 *   and the legacy post-based order system.
 * - **Dynamic Package Assignment**: Supports **manual** and **automatic** distribution of products across packages.
 * - **Shipping Label & Status Tracking**: Associates **shipping labels** and **real-time tracking statuses**
 *   with each package.
 * - **AJAX-based Updates**: Uses AJAX to handle package creation, deletion, and item distribution seamlessly.
 * - **Security & Validation**: Implements nonce verification and input validation to prevent unauthorized actions.
 * - **Multilingual Support**: Supports translations via `wp_localize_script()`.
 *
 * ## Data Structure:
 * Package (`colis`) information is stored in WooCommerce order metadata (`_rc_colis`), with the following format:
 *
 * ```php
 * '_rc_colis' => [
 *     'items' => [
 *         [
 *             'id' => 83,
 *             'name' => "Tomatoes",
 *             'weight' => 120,
 *             'quantity' => 2,
 *             'remaining_quantity' => 0
 *         ],
 *         [
 *             'id' => 73,
 *             'name' => "Peppers",
 *             'weight' => 25000,
 *             'quantity' => 1,
 *             'remaining_quantity' => 1
 *         ]
 *     ],
 *     'colis' => [
 *         [
 *             'items' => [83 => 2],
 *             'weight' => 240,
 *             'dimensions' => [
 *                 'height' => 0,
 *                 'width' => 0,
 *                 'length' => 0
 *             ],
 *             'shipping_label' => "4H013000008101",
 *             'shipping_label_pdf' => "<PDF URL>",
 *             'shipping_status' => "status_rc_depose_en_relais",
 *             'shipping_status_label' => "Colis retiré au point relais",
 *             'c2c_shipping_price' => 5.92,
 *         ]
 *     ]
 * ]
 * ```
 *
 * ## Methods Overview:
 * - **Meta Box Management:**
 *   - `init()`: Initializes the package management system.
 *   - `action_add_meta_boxes()`: Registers the WooCommerce order meta box.
 *   - `rc_woocommerce_colis_callback()`: Renders the UI for package distribution.
 *
 * - **AJAX-based Package Handling:**
 *   - `action_admin_enqueue_scripts()`: Enqueues required JavaScript and CSS files.
 *   - `rc_count_product_in_colis()`: Counts how many times a product is assigned to packages.
 *   - `build_remaining_items()`: Generates a JSON list of unassigned order items.
 *   - `put_items_in_package()`: Allocates products into packages while respecting weight constraints.
 *   - `auto_distribute_packages()`: Automatically distributes products across packages.
 *
 * - **Order Metadata Management:**
 *   - `load_order_packages()`: Retrieves stored package data from the WooCommerce order meta.
 *   - `save_order_packages()`: Saves updated package data into the WooCommerce order meta.
 *
 * ## Workflow:
 * 1. **Product Allocation to Packages**
 *    - Admins can manually assign products to packages.
 *    - Alternatively, the system can auto-distribute items based on weight and volume constraints.
 *
 * 2. **Shipping Label Generation**
 *    - Packages are sent to the Relais Colis API to generate shipping labels.
 *    - Generated labels are stored in the order metadata.
 *
 * 3. **Package Tracking & Updates**
 *    - Each package receives a status update as it moves through the shipping process.
 *    - The interface reflects these updates, ensuring visibility.
 *
 * ## Considerations:
 * - **High-Performance Order Storage (HPOS)**: Fully optimized for WooCommerce's modern HPOS.
 * - **Security & Data Validation**: Protects against unauthorized changes with nonce verification.
 * - **Performance Optimization**: Uses batch processing and AJAX to ensure a seamless admin experience.
 * - **Extensibility**: Built for future enhancements, including third-party integrations.
 *
 * ## WooCommerce Hooks Used:
 * - `add_meta_boxes`: Registers the WooCommerce meta box for package management.
 * - `admin_enqueue_scripts`: Loads JavaScript & CSS for the admin interface.
 * - `wp_ajax_*`: Handles AJAX requests for package updates and auto-distribution.
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Order_Packages_Manager {

    // Use Trait Singleton
    use Singleton;

    const RC_ORDER_PACKAGES = 'rc_order_packages';

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
        WC_RC_Ajax_Shipping_Price::instance();
        WC_RC_Ajax_Shipping_Return::instance();
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
            'label_get_packages_price' => __( 'Estimate your shipment', 'relais-colis-woocommerce' ),
            'label_estimated_shipping_price' => __( 'Estimated shipping price:', 'relais-colis-woocommerce' ),
            'label_generate_return_label' => __( 'Generate return label', 'relais-colis-woocommerce' ),
            'label_return_information' => __( 'Return information', 'relais-colis-woocommerce' ),
            'label_return_number' => __( 'Return number', 'relais-colis-woocommerce' ),
            'label_return_number_cab' => __( 'Cab number', 'relais-colis-woocommerce' ),
            'label_return_limit_date' => __( 'Deadline associated with the return', 'relais-colis-woocommerce' ),
            'label_view_return_label' => __( 'URL for related return label', 'relais-colis-woocommerce' ),
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

        // Fetch existing package distribution data (Legacy & HPOS support).
        [ $colis, $items ] = $this->load_order_packages( $post->ID );

        foreach ( $colis as &$c_colis ) {

            if ( array_key_exists( 'shipping_label', $c_colis ) ) {

                $shipping_label = $c_colis['shipping_label'];

                // Get shipping status
                $shipping_status = WP_Orders_Rel_Shipping_Labels_DAO::instance()->get_shipping_status_by_shipping_label( $shipping_label );
                WP_Log::debug( __METHOD__, ['$shipping_label'=>$shipping_label, '$shipping_status'=>$shipping_status], 'relais-colis-woocommerce' );

                if ( !is_null( $shipping_status ) && ( $shipping_status !== WC_RC_Shipping_Constants::STATUS_RC_PENDING ) ) {

                    $c_colis['shipping_status_label'] = WC_RC_Shipping_Constants::get_rc_status_title( $shipping_status );
                    $c_colis['shipping_status'] = $shipping_status;
                }
            }
        }
        WP_Log::debug( __METHOD__.' - Updated colis with shipping statuses', ['$colis'=>$colis], 'relais-colis-woocommerce' );

        // Prepare JSON data to pass to JavaScript
        $colis_json = json_encode( $colis );
        $items_json = json_encode( $items );

        // Get WC order
        $wc_order = wc_get_order( $post->ID );

        // Get return infos, if available
        // bordereau_smart_url
        // return_number
        // number_cab
        // limit_date
        // image_url
        // token
        // created_at
        // Update order meta data
        $return_bordereau_smart_url = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_BORDEREAU_SMART_URL );
        $return_number = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_RETURN_NUMBER );
        $return_number_cab = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_NUMBER_CAB );
        $return_limit_date = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_LIMIT_DATE );
        $return_image_url = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_IMAGE_URL );
        $return_token = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_TOKEN );
        $return_created_at = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_CREATED_AT );

        // Inject JSON data into JavaScript
        echo "<script>
            var c2c_mode = ".(WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode()?"1":"0").";
            var rc_order_colis = $colis_json;
            var rc_order_items = $items_json;
            var rc_order_id = ".esc_js( $post->ID ).";
            var return_bordereau_smart_url = '".$return_bordereau_smart_url."';
            var return_number = '".$return_number."';
            var return_number_cab = '".$return_number_cab."';
            var return_limit_date = '".$return_limit_date."';
            var return_image_url = '".$return_image_url."';
            var return_token = '".$return_token."';
            var return_created_at = '".$return_created_at."';
          </script>";

        // Empty container where JavaScript will generate the UI dynamically
        echo '<div id="rc-colis-container"></div>';
    }

    /**
     * Try to put as much as possible items in a package
     * Used in packages auto distribute
     * @param $items
     * @param $current_colis
     * @param $items_to_distribute
     * @param $max_weight
     * @return void
     */
    public function put_items_in_package( &$items, &$current_colis, &$items_to_distribute, $max_weight ) {

        foreach ( $items as &$item ) {

            $item_id = $item[ 'id' ];
            $item_weight = $item[ 'weight' ];

            // If weigth is too important, then cannot distribute product
            if ( $item_weight > $max_weight ) continue;

            // Distribute per colis as max as possible
            while ( $item[ 'remaining_quantity' ] > 0 ) {

                // Package will become too heavy ?
                if ( ( $current_colis[ 'weight' ] + $item_weight ) > $max_weight ) continue 2; // Next item...

                // Can add an item in this package
                if ( !isset( $current_colis[ 'items' ][ $item_id ] ) ) {

                    // Create an entry for this product in the package
                    $current_colis[ 'items' ][ $item_id ] = 1;
                } else {

                    $current_colis[ 'items' ][ $item_id ] = $current_colis[ 'items' ][ $item_id ] + 1;
                }
                $current_colis[ 'weight' ] += $item_weight;

                // Remaining quantity decrement
                $item[ 'remaining_quantity' ] = $item[ 'remaining_quantity' ] - 1;
                $items_to_distribute--;
            }
        }
    }

    /**
     * Auto distribute items in packages
     * @param $items array items to be distributed. These param is modified as a reference
     * @return array list of packages
     */
    public function auto_distribute_packages( &$items ) {

        // Distribution strategy is : try and put as max as possible items in each package
        $max_weight = 20000; // max per package
        $items_to_distribute = 0;

        // First parse all items to calculate total number of productsto distribute
        foreach ( $items as $item ) {

            // If weigth is too important, then cannot distribute product
            if ( $item[ 'weight' ] > $max_weight ) continue;

            // Add remaining to total
            $items_to_distribute += $item[ 'remaining_quantity' ];
        }

        // Second parse all existing packages and try to distribute items in them...
        if ( !empty( $colis ) ) {

            // For each package, try and put as max as possible items
            foreach ( $colis as &$current_colis ) {

                // Try to put as much as possible items in a package
                $this->put_items_in_package( $items, $current_colis, $items_to_distribute, $max_weight );
            }
        }

        // Finally, try to distribute in new packages
        while ( $items_to_distribute > 0 ) {

            // Add a new empty package
            $current_colis = [
                'items' => [],
                'weight' => 0,
                'dimensions' => [
                    'height' => 0,
                    'width' => 0,
                    'length' => 0,
                ]
            ];

            // Try to put as much as possible items in a package
            $this->put_items_in_package( $items, $current_colis, $items_to_distribute, $max_weight );

            $colis[] = $current_colis;
        }
        return $colis;
    }

    /**
     * Load packages and build items structure from meta data
     * @param $order_id int order identifier
     * @return array packages and items structure, as a 2-uple
     */
    public function load_order_packages( $order_id ) {

        // Get WC order
        $order = wc_get_order( $order_id );

        if ( !$order ) {

            throw new Exception( __( 'Order not found', 'relais-colis-woocommerce' ) );
        }

        // Check if the shipping method is "Relais Colis"
        $rc_shipping_method = WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $order );
        if ( $rc_shipping_method === false ) {

            throw new Exception( __( 'Invalid Relais Colis method', 'relais-colis-woocommerce' ) );
        }

        // Fetch existing package distribution data (Legacy & HPOS support).
        $colis = method_exists( $order, 'get_meta' ) ?
            $order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_COLIS, true ) :
            get_post_meta( $order_id, '_rc_colis', true );

        // Get existing packages
        $colis = $colis ?: [];

        // Reindex to avoid holes
        $colis = is_array( $colis ) ? array_values( $colis ) : [];

        // List of remaining items
        $items = WC_Order_Packages_Manager::instance()->build_remaining_items( $order, $colis, false );

        WP_Log::debug( __METHOD__.' - Before auto distribute', [
            'order_id' => $order_id,
            'items' => $items,
            'colis' => $colis,
        ], 'relais-colis-woocommerce' );

        return array( $colis, $items );
    }

    /**
     * Save packages to meta data
     * @param $packages array packages structure
     * @param $order_id int order identifier
     * @return array packages and items structure, as a 2-uple
     */
    public function save_order_packages( $packages, $order_id ) {

        // Get WC order
        $wc_order = wc_get_order( $order_id );

        if ( !$wc_order ) {

            throw new Exception( __( 'Order not found', 'relais-colis-woocommerce' ) );
        }

        // Reindex to avoid holes
        $packages = is_array( $packages ) ? array_values( $packages ) : [];

        // Update order meta data
        $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_COLIS, $packages );
        $wc_order->save();

        // List of remaining items
        $items = WC_Order_Packages_Manager::instance()->build_remaining_items( $wc_order, $packages, false );

        return array( $packages, $items );
    }
}
