<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Orders_Rel_Shipping_Labels_DAO;
use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Generate;
use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Home_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_Bulk_Generate;
use RelaisColisWoocommerce\RCAPI\WP_RC_C2C_Relay_Place_Advertisement;
use RelaisColisWoocommerce\RCAPI\WP_RC_Place_Advertisement_Request;
use RelaisColisWoocommerce\RCAPI\WP_RC_Transport_Generate;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WC_RC_Services_Manager;
use RelaisColisWoocommerce\WC_WooCommerce_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WP_Post;
use Exception;
use WC_Order;
use WC_Order_Query;

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
        WC_RC_Ajax_Way_Bill::instance();

        // Ajouter un filtre pour désactiver le verrouillage des commandes
        add_filter('wc_order_is_editable', '__return_true');
        
        // OU supprimer le verrou existant
        add_action('init', function() {
            if (isset($_GET['post'])) {
                delete_post_meta($_GET['post'], '_edit_lock');
            }
        });
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


        // Get the order
        if ($post instanceof WC_Order) {
            $order = $post;
        } elseif ($post instanceof WP_Post) {
            $order = wc_get_order($post->ID);
        } else {
            WP_Log::error(__METHOD__, ['$post' => $post, 'type' => gettype($post)], 'relais-colis-woocommerce');
            return;
        }
        
        WP_Log::error( __METHOD__, [ '$order' => $order ], 'relais-colis-woocommerce' );
        if (!$order) {
            return;
        }

        // Check if it's a Relais Colis order
        $shipping_methods = $order->get_shipping_methods();
        $is_relais_colis = false;
        
        foreach ($shipping_methods as $shipping_method) {
            if (WC_RC_Shipping_Method_Manager::instance()->is_a_rc_shipping_method($shipping_method->get_method_id())) {
                $is_relais_colis = true;
                break;
            }
        }

        // Only add the metabox if it's a Relais Colis order
        if (!$is_relais_colis) {
            return;
        }

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
        WP_Log::debug( __METHOD__, [ 'screen_id' => $screen->id, 'post_type' => get_post_type() ], 'relais-colis-woocommerce' );

        // Ensure we are on a WooCommerce order edit page
        if ( $screen && $screen->id !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders' ) {

            return;
        }

        // CSS
        wp_enqueue_style( 'font-awesome', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/font-awesome.css' );
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
            'label_total_weight' => __( 'Total weight (kg)', 'relais-colis-woocommerce' ),
            'label_dimensions' => __( 'Dimensions', 'relais-colis-woocommerce' ),
            'label_height' => __( 'height', 'relais-colis-woocommerce' ),
            'label_width' => __( 'width', 'relais-colis-woocommerce' ),
            'label_length' => __( 'length', 'relais-colis-woocommerce' ),
            'label_unit_weight' => __( 'Unit weight (kg)', 'relais-colis-woocommerce' ),
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
            'label_generate_home_return_label' => __( 'Generate home return label', 'relais-colis-woocommerce' ),
            'label_return_information' => __( 'Return information', 'relais-colis-woocommerce' ),
            'label_return_number' => __( 'Return number', 'relais-colis-woocommerce' ),
            'label_return_number_cab' => __( 'Cab number', 'relais-colis-woocommerce' ),
            'label_return_limit_date' => __( 'Deadline associated with the return', 'relais-colis-woocommerce' ),
            'label_view_return_label' => __( 'URL for related return label', 'relais-colis-woocommerce' ),
            'label_generate_way_bill' => __( 'Generate way bill', 'relais-colis-woocommerce' ),
            'label_print_way_bill' => __( 'Print way bill', 'relais-colis-woocommerce' ),
            'label_error_network' => __( 'A network error occurred: ', 'relais-colis-woocommerce' ),
            'label_error_unknown' => __( 'Unknown error.', 'relais-colis-woocommerce' ),
            'label_error_unknown_generate_way_bill' => __( 'Unknown error while generating the way bill', 'relais-colis-woocommerce' ),
            'label_product' => __( 'Product', 'relais-colis-woocommerce' ),
            'label_actions' => __( 'Actions', 'relais-colis-woocommerce' ),
            'label_error_colis_too_big' => __( 'The colis must be less than 170cm in any dimension.', 'relais-colis-woocommerce' ),
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
     * Check if items are all distributed
     * @param $items
     * @return bool
     */
    public function has_remaining_items( $items ) {

        $remaining_quantity = 0;
        foreach ( $items as $item_id => $item ) {

            if ( isset( $item[ 'remaining_quantity' ] ) ) {

                $remaining_quantity += $item[ 'remaining_quantity' ];
            }
        }
        return ( $remaining_quantity > 0 );
    }

    /**
     * Build the JSON for remaing items, depending on packages distribution
     * @param WC_Order $order
     * @return string
     */
    public function build_remaining_items( WC_Order $order, $colis, $json_encoded = true ) {

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
        WP_Log::debug( __METHOD__.' - After rebuilding items', [ '$items_json' => $items_json ], 'relais-colis-woocommerce' );
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

        // Get WC order
        if ($post instanceof WC_Order) {
            $wc_order = $post;
        } elseif ($post instanceof WP_Post) {
            $wc_order = wc_get_order($post->ID);
        } else {
            WP_Log::error(__METHOD__, ['$post' => $post, 'type' => gettype($post)], 'relais-colis-woocommerce');
            return;
        }
        
        
        // Vérifier si c'est une commande Relais Colis
        $shipping_methods = $wc_order->get_shipping_methods();
        $is_relais_colis = false;
        foreach ($shipping_methods as $shipping_method) {
            if (strpos($shipping_method->get_method_title(), 'Relais Colis') !== false) {
                $is_relais_colis = true;
                break;
            }
        }

            // Si ce n'est pas une commande Relais Colis, on sort
    if (!$is_relais_colis) {
        return;
    }
        

        // Log the method execution for debugging.
        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

                // Get the order
        if ($post instanceof WC_Order) {
            $order = $post;
        } elseif ($post instanceof WP_Post) {
            $order = wc_get_order($post->ID);
        } else {
            WP_Log::error(__METHOD__, ['$post' => $post, 'type' => gettype($post)], 'relais-colis-woocommerce');
            return;
        }
        

        // Fetch existing package distribution data (Legacy & HPOS support).
        [ $colis, $items ] = $this->load_order_packages( $order->get_id() );

        foreach ( $colis as &$c_colis ) {

            if ( array_key_exists( 'shipping_label', $c_colis ) ) {

                $shipping_label = $c_colis[ 'shipping_label' ];

                // Get shipping status
                $shipping_status = WP_Orders_Rel_Shipping_Labels_DAO::instance()->get_shipping_status_by_shipping_label( $shipping_label );
                WP_Log::debug( __METHOD__, [ '$shipping_label' => $shipping_label, '$shipping_status' => $shipping_status ], 'relais-colis-woocommerce' );

                if ( !is_null( $shipping_status ) && ( $shipping_status !== WC_RC_Shipping_Constants::STATUS_RC_PENDING ) ) {

                    $c_colis[ 'shipping_status_label' ] = WC_RC_Shipping_Constants::get_rc_status_title( $shipping_status );
                    $c_colis[ 'shipping_status' ] = $shipping_status;
                }
            }
        }
        WP_Log::debug( __METHOD__.' - Updated colis with shipping statuses', [ '$colis' => $colis ], 'relais-colis-woocommerce' );

        // Prepare JSON data to pass to JavaScript
        $colis_json = json_encode( $colis );
        $items_json = json_encode( $items );

        // Get WC order
        if ($post instanceof WC_Order) {
            $wc_order = $post;
        } elseif ($post instanceof WP_Post) {
            $wc_order = wc_get_order($post->ID);
        } else {
            WP_Log::error(__METHOD__, ['$post' => $post, 'type' => gettype($post)], 'relais-colis-woocommerce');
            return;
        }
        

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
        $return_limit_date_raw = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_LIMIT_DATE );
        $return_limit_date = '';
        if ( !empty( $return_limit_date_raw ) ) {
            try {
                $date = new \DateTime( $return_limit_date_raw );
                $return_limit_date = $date->format( 'd/m/Y H:i:s' );
            } catch ( Exception $e ) {
                $return_limit_date = $return_limit_date_raw; // Fallback vers la valeur originale si la conversion échoue
            }
        }
        $return_image_url = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_IMAGE_URL );
        $return_token = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_TOKEN );
        $return_created_at = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RETURN_CREATED_AT );
        // Get way bill info
        $rc_way_bill = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_WAY_BILL );
        // Get order state
        $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

        $rc_shipping_method = WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $wc_order );
        WP_Log::debug( __METHOD__.' - Order loaded', [ '$order_state' => $order_state, '$colis' => $colis, '$items' => $items ], 'relais-colis-woocommerce' );

       // var_dump( print_r($return_bordereau_smart_url, true));die();

        // Inject JSON data into JavaScript
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo "<script>
            var c2c_mode = ".( WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode() ? "1" : "0" ).";
            var rc_order_colis = $colis_json;
            var rc_order_items = $items_json;
            var rc_order_id = ".esc_js($wc_order->get_id()).";
            var rc_order_status = '".esc_js($wc_order->get_status())."';  // Ajouter le statut de la commande
            var return_bordereau_smart_url = '".$return_image_url."';
            var return_number = '".$return_number."';
            var return_number_cab = '".$return_number_cab."';
            var return_limit_date = '".$return_limit_date."';
            var return_image_url = '';
            var return_token = '".$return_token."';
            var return_created_at = '".$return_created_at."';
            var rc_way_bill = '".$rc_way_bill."';
            var rc_order_state = '".$order_state."';
            var rc_shipping_method = '".$rc_shipping_method."';
          </script>";
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

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

        WP_Log::debug( __METHOD__.' - Put items in package', [ '$items' => $items, '$current_colis' => $current_colis, '$items_to_distribute' => $items_to_distribute, '$max_weight' => $max_weight ], 'relais-colis-woocommerce' );

        // Get weigth unit for conversions
        $woocommerce_weight_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT, 'g' );

        foreach ( $items as &$item ) {

            $item_id = isset( $item[ 'id' ] ) ? $item[ 'id' ] : null;
            $item_weight = isset( $item[ 'weight' ] ) ? (float)$item[ 'weight' ] : 0;
            $item_weight_grams = WP_Helper::convert_to_grams( $item_weight, $woocommerce_weight_unit );
            $remaining_qty = isset( $item[ 'remaining_quantity' ] ) ? (int)$item[ 'remaining_quantity' ] : 0;

            // If no ID or weight invalid, skip
            if ( !$item_id || $item_weight_grams <= 0 ) {
                continue;
            }

            // If item is heavier than the max allowed, skip
            if ( $item_weight_grams > $max_weight ) {
                // var_dump( $item_weight_grams );
                continue;
            }

            // Init weight if not set
            if ( !isset( $current_colis[ 'weight' ] ) ) {
                $current_colis[ 'weight' ] = 0;
            }

            // Init items array
            if ( !isset( $current_colis[ 'items' ] ) || !is_array( $current_colis[ 'items' ] ) ) {
                $current_colis[ 'items' ] = [];
            }

            while ( $remaining_qty > 0 ) {

                $current_weight = (float)$current_colis[ 'weight' ];
                $current_weight_grams = WP_Helper::convert_to_grams( $current_weight, $woocommerce_weight_unit );

                // Will package become too heavy?
                if ( ( $current_weight_grams + $item_weight_grams ) > $max_weight ) {
                    break; // Go to next item
                }

                // Add or increment item in the package
                if ( !isset( $current_colis[ 'items' ][ $item_id ] ) ) {
                    $current_colis[ 'items' ][ $item_id ] = 1;
                } else {
                    $current_colis[ 'items' ][ $item_id ] += 1;
                }

                // Update weight
                $current_colis[ 'weight' ] += $item_weight;

                // Update quantities
                $remaining_qty--;
                $item[ 'remaining_quantity' ] = $remaining_qty;
                $items_to_distribute--;
            }
        }
    }

    /**
     * Auto distribute items in packages
     * @param $items array items to be distributed. These param is modified as a reference
     * @return array list of packages, false if problem occurred
     */
    public function auto_distribute_packages( $order_id ) {
        // Get order
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        // Load packages
        [$colis, $items] = $this->load_order_packages($order_id);

        // If no remaining items to distribute, return
        if (!$this->has_remaining_items($items)) {
            /**
             * Action triggered after bulk actions on RC shop order
             */
            do_action("after_bulk_actions_rc_shop_order", $order_id, false, __('The products have already been distributed into packages', 'relais-colis-woocommerce'));
            return false;
        }

        // Distribution strategy is : try and put as max as possible items in each package
        $max_weight = 20000; // max par défaut, en grammes
        $woocommerce_weight_unit = get_option(WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT, 'g');
        $items_to_distribute = 0;

        // Récupérer la méthode d'expédition de la commande
        $shipping_methods = $order->get_shipping_methods();
        $shipping_method = '';
        
        foreach ($shipping_methods as $shipping_method_obj) {
            $shipping_method = $shipping_method_obj->get_method_id();
            break; // On prend la première méthode d'expédition trouvée
        }


        // Vérifier si un produit pèse entre 20kg et 40kg
        foreach ($items as $item) {
            $item_weight = isset($item['weight']) ? (float)$item['weight'] : 0;
            $item_weight_grams = WP_Helper::convert_to_grams($item_weight, $woocommerce_weight_unit);
            
            if ($item_weight_grams > 20000 && $item_weight_grams <= 40000) {
                $max_weight = 40000; // Si oui, on augmente la limite à 40kg
                break;
            }
            if ($item_weight_grams > 40000 && $item_weight_grams <= 1300000) {
                $max_weight = 1300000; // Si oui, on augmente la limite à 40kg
                break;
            }
        }

        if ($shipping_method === 'wc_rc_shipping_method_homeplus' || $shipping_method === 'wc_rc_shipping_method_home' ) {
            $max_weight = 1300000;
        }

        // First parse all items to calculate total number of products to distribute
        foreach ($items as $item) {
            if (isset($item['remaining_quantity'])) {
                $items_to_distribute += $item['remaining_quantity'];
            }
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

            // Keeping nb to distribute in memory
            $items_to_distribute_before = $items_to_distribute;

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

            // If no items distributed, exit...
            if ( $items_to_distribute_before === $items_to_distribute ) break;

            $colis[] = $current_colis;
        }

        // Save packages
        [ $colis, $items ] = WC_Order_Packages_Manager::instance()->save_order_packages( $colis, $order_id );

        // If no remaining items, then order change to state ORDER_STATE_ITEMS_DISTRIBUTED
        if ( !WC_Order_Packages_Manager::instance()->has_remaining_items( $items ) ) {

            $order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_DISTRIBUTED );

            // Save order
            $order->save();

            /**
             * Notify 3rd party code on Relais Colis bulk action result
             *
             * @param int $order_id The order ID
             * @param boolean $is_success true if success, otherwise false
             * @param string $message A message associated with the hook
             * @since 1.0.0
             *
             */
            do_action( "after_bulk_actions_rc_shop_order", $order_id, true, __( 'All the packages have been distributed', 'relais-colis-woocommerce' ) );
        } else {

            /**
             * Notify 3rd party code on Relais Colis bulk action result
             *
             * @param int $order_id The order ID
             * @param boolean $is_success true if success, otherwise false
             * @param string $message A message associated with the hook
             * @since 1.0.0
             *
             */
            do_action( "after_bulk_actions_rc_shop_order", $order_id, false, __( 'There are still packages to be distributed', 'relais-colis-woocommerce' ) );
        }
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

            throw new Exception( esc_html__( 'Order not found', 'relais-colis-woocommerce' ) );
        }

        // Check if the shipping method is "Relais Colis"
        $rc_shipping_method = WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $order );
        // if ( $rc_shipping_method === false ) {

        //     throw new Exception( __( 'Invalid Relais Colis method', 'relais-colis-woocommerce' ) );
        // }

        // Fetch existing package distribution data (Legacy & HPOS support).
        $colis = method_exists( $order, 'get_meta' ) ?
            $order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_COLIS, true ) :
            get_post_meta( $order_id, '_rc_colis', true );

        // Get existing packages
        $colis = $colis ?: [];

        // Reindex to avoid holes
        $colis = is_array( $colis ) ? array_values( $colis ) : [];

        // List of remaining items
        $items = $this->build_remaining_items( $order, $colis, false );

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

            throw new Exception( esc_html__( 'Order not found', 'relais-colis-woocommerce' ) );
        }

        // Reindex to avoid holes
        $packages = is_array( $packages ) ? array_values( $packages ) : [];

        // Update order meta data
        $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_COLIS, $packages );
        $wc_order->save();

        // List of remaining items
        $items = $this->build_remaining_items( $wc_order, $packages, false );

        return array( $packages, $items );
    }

    /**
     * Place a way bill for all packages (/transport/generate RC API)
     * @param WC_Order $wc_orders
     * @return string the way bill
     */
    public function generate_way_bill( WC_Order $wc_order ) {

        try {

            // Get order state
            $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );
            if ( $order_state != WC_RC_Shipping_Constants::ORDER_STATE_SHIPPING_LABELS_PLACED ) {

                WP_Log::debug( __METHOD__.' - Order state incoherency', [ '$order_state' => $order_state ], 'relais-colis-woocommerce' );

                // Pb occured... HTML response not permitted
                throw new WP_Relais_Colis_API_Exception( __( 'Shipping labels must be placed first', 'relais-colis-woocommerce' ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INCOHERENCY_STATE ] );
            }

            $wc_order_id = $wc_order->get_id();

            // Get interaction mode
            $is_c2c_interaction_mode = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode();

            // Only for B2C mode
            if ( $is_c2c_interaction_mode ) {

                // Pb occurred... invalid mode
                throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_INVALID_C2C_MODE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INVALID_C2C_MODE ] );
            }

            // Prepare request (generic part)
            $dynamic_params = array(
                WP_RC_Transport_Generate::COLIS0 => $wc_order_id,
            );

            // Call API
            $transport_generate = WP_Relais_Colis_API::instance()->transport_generate( $dynamic_params, false );

            if ( is_null( $transport_generate ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );

                // Pb occured... HTML response not permitted
                throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ] );
            }

            // Affect to order :
            // rc_way_bill
            $rc_way_bill = $transport_generate->get_pdf_transport_label();

            // Update order
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_WAY_BILL, $rc_way_bill );

            // All is right then change state
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, WC_RC_Shipping_Constants::ORDER_STATE_WAY_BILLS_GENERATED );

            $wc_order->save();

            return $rc_way_bill;

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::error( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );
            throw $wp_relais_colis_api_exception;
        }
    }

    /**
     * Place a shipping label for an order
     * @param $wc_order the WooCommerce order
     * @return array 2-uple colis and items
     */
    public function place_shipping_label( WC_Order $wc_order ) {

        try {

            // Get order state
            $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );
            if ( $order_state == WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED ) {

                WP_Log::debug( __METHOD__.' - Order state incoherency', [ '$order_state' => $order_state ], 'relais-colis-woocommerce' );

                // Pb occured... HTML response not permitted
                throw new WP_Relais_Colis_API_Exception( __( 'The packages in the order must first be divided into packages', 'relais-colis-woocommerce' ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INCOHERENCY_STATE ] );
            } else if ( ( $order_state == WC_RC_Shipping_Constants::ORDER_STATE_SHIPPING_LABELS_PLACED ) || ( $order_state == WC_RC_Shipping_Constants::ORDER_STATE_WAY_BILLS_GENERATED ) ) {

                WP_Log::debug( __METHOD__.' - Order state incoherency', [ '$order_state' => $order_state ], 'relais-colis-woocommerce' );

                // Pb occured... HTML response not permitted
                throw new WP_Relais_Colis_API_Exception( __( 'Product shipping labels have already been generated', 'relais-colis-woocommerce' ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INCOHERENCY_STATE ] );
            }

            // Load packages
            $wc_order_id = $wc_order->get_id();
            [ $colis, $items ] = $this->load_order_packages( $wc_order_id );
            WP_Log::debug( __METHOD__, [ '$colis' => $colis, '$items' => $items ], 'relais-colis-woocommerce' );

            // Get interaction mode
            $is_c2c_interaction_mode = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode();

            // Prepare request (generic part)
            // Country
            $country = get_option( 'woocommerce_default_country' ); // Eg: "FR:IDF"
            $country_array = explode( ":", $country );
            $store_country = $country_array[ 0 ]; // Country (Eg: FR)

            // Check if the shipping method is "Relais Colis"
            $rc_shipping_method = WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $wc_order );

            switch ( $rc_shipping_method ) {
                case WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID:

                    // Get xeett and agency code for relay, from meta data
                    $xeett = '';
                    $agency_code = '';

                    // Check if relay_data
                    //            [Xeett] => G2013
                    //            [Agencecode] => G2
                    //            [Pseudorvc] => 06366
                    $rc_relay_data = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA );
                    WP_Log::debug( __METHOD__, [ '$rc_relay_data' => $rc_relay_data ], 'relais-colis-woocommerce' );
                    if ( !empty( $rc_relay_data ) ) {

                        // Extract informations
                        $xeett = $rc_relay_data[ 'Xeett' ] ?? '';
                        $agency_code = $rc_relay_data[ 'Agencecode' ] ?? '';
                        $pseudo_rvc = $rc_relay_data[ 'Pseudorvc' ] ?? '';
                    }

                    $dynamic_params_place_shipping_label = array();

                    $b2c_datas = [];
                    $total_weight = 0;

                    // Request RC API place_advertisement
                    foreach ( $colis as &$c_colis ) {

                        // Depend on interaction mode (B2C or C2C)

                        // Get package weight
                        $dynamic_params_place_shipping_label[ WP_RC_Place_Advertisement_Request::SHIPPMENT_WEIGHT ] = ''.$c_colis[ 'weight' ];
                        $dynamic_params_place_shipping_label[ WP_RC_Place_Advertisement_Request::WEIGHT ] = ''.$c_colis[ 'weight' ];

                        $dynamic_params_place_shipping_label[ WP_RC_Place_Advertisement_Request::HEIGHT ] = ''.$c_colis['dimensions'][ 'height' ];
                        $dynamic_params_place_shipping_label[ WP_RC_Place_Advertisement_Request::WIDTH ] = ''.$c_colis['dimensions'][ 'width' ];
                        $dynamic_params_place_shipping_label[ WP_RC_Place_Advertisement_Request::LENGTH ] = ''.$c_colis['dimensions'][ 'length' ];

                        if(isset($c_colis['dimensions']['height']) && isset($c_colis['dimensions']['width']) && isset($c_colis['dimensions']['length'])){
                            if($c_colis['dimensions']['height'] > 0 && $c_colis['dimensions']['width'] > 0 && $c_colis['dimensions']['length'] > 0){
                                $dynamic_params_place_shipping_label[ WP_RC_Place_Advertisement_Request::VOLUME ] = ''.$c_colis['dimensions'][ 'height' ] * $c_colis['dimensions'][ 'width' ] * $c_colis['dimensions'][ 'length' ];
                            }
                        }

                        // C2C - Relay
                        if ( $is_c2c_interaction_mode ) {

                            // Dynamic params

                            // Dynamic common params
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_ID ] = ''.$wc_order->get_customer_id();
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_FULLNAME ] = $wc_order->get_shipping_first_name().' '.$wc_order->get_shipping_last_name();
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_EMAIL ] = $wc_order->get_billing_email();
                            $customer_phone = $wc_order->get_shipping_phone();
                            if ( empty( $customer_phone ) ) $customer_phone = '0'; // FIXME ... trouver une autre solution
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_PHONE ] = $customer_phone;
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::CUSTOMER_MOBILE ] = $wc_order->get_shipping_phone() ?? '';
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::ADDRESS1_EXPEDITEUR ] = get_option( 'woocommerce_store_address' );
                            if ( !empty( get_option( 'woocommerce_store_address_2' ) ) ) $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::ADDRESS2_EXPEDITEUR ] = get_option( 'woocommerce_store_address_2' );
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::EMAIL_EXPEDITEUR ] = get_option( 'woocommerce_email_from_address' );
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::CITY_EXPEDITEUR ] = get_option( 'woocommerce_store_city' );
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::NAME_EXPEDITEUR ] = get_option( 'blogname' );
                            if ( !empty( get_option( 'woocommerce_store_phone' ) ) ) $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::PHONE_EXPEDITEUR ] = get_option( 'woocommerce_store_phone' );
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::POSTCODE_EXPEDITEUR ] = get_option( 'woocommerce_store_postcode' );
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::ORDER_REFERENCE ] = $wc_order->get_order_number();
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_1 ] = $wc_order->get_shipping_address_1();
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_2 ] = $wc_order->get_shipping_address_2();
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_POSTCODE ] = $wc_order->get_shipping_postcode();
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_CITY ] = $wc_order->get_shipping_city();
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::SHIPPING_COUNTRY_CODE ] = $store_country;
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::AGENCY_CODE ] = $agency_code;
                            $dynamic_params_place_shipping_label[ WP_RC_C2C_Relay_Place_Advertisement::XEETT ] = $xeett;

                            // Call API
                            $c2c_relay_place_advertisement = WP_Relais_Colis_API::instance()->c2c_relay_place_advertisement( $dynamic_params_place_shipping_label, false );

                            if ( is_null( $c2c_relay_place_advertisement ) ) {

                                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );

                                // Pb occured... HTML response not permitted
                                throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ] );
                            }

                            // Display response
                            if ( $c2c_relay_place_advertisement->validate() ) {

                                $entry = $c2c_relay_place_advertisement->entry;

                                WP_Log::debug( __METHOD__.' - Valid response', [
                                    'Entry' => $entry,
                                    'Shipping method' => $rc_shipping_method,
                                ], 'relais-colis-woocommerce' );

                                // Set shipping label in colis
                                $c_colis[ 'shipping_label' ] = $entry;

                                // Init RC status
                                WC_Orders_RC_Status_Manager::instance()->init_order_rc_status( $wc_order, $entry );

                            } else {

                                WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );

                                // Pb occured... HTML response not permitted
                                throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE ] );
                            }
                        } // B2C - Relay
                        else {

                            // Dynamic params
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::AGENCY_CODE ] = $agency_code;
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_ID ] = ''.$wc_order->get_customer_id();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_FULLNAME ] = $wc_order->get_shipping_first_name().' '.$wc_order->get_shipping_last_name();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_EMAIL ] = $wc_order->get_billing_email() ?? '';
                            $customer_phone = $wc_order->get_shipping_phone();
                            if ( empty( $customer_phone ) ) $customer_phone = '0'; // FIXME ... trouver une autre solution
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_PHONE ] = $customer_phone;
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::CUSTOMER_MOBILE ] = $wc_order->get_shipping_phone() ?? '';
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::PSEUDO_RVC ] = $pseudo_rvc;
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::ORDER_REFERENCE ] = $wc_order->get_order_number();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_1 ] = $wc_order->get_shipping_address_1();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_ADDRESS_2 ] = $wc_order->get_shipping_address_2() ?? '';
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_POSTCODE ] = $wc_order->get_shipping_postcode();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_CITY ] = $wc_order->get_shipping_city();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::SHIPPING_COUNTRY_CODE ] = $store_country;
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::XEETT ] = ''.$xeett;
                            WP_Log::debug( __METHOD__.' - B2C - Relay Params', ['$dynamic_params_place_shipping_label'=>$dynamic_params_place_shipping_label], 'relais-colis-woocommerce' );


                            $isMax = 0;
                            $weight_unit = get_option('woocommerce_weight_unit');

                            if( $weight_unit == 'kg' ){
                                $isMax = ($c_colis[ 'weight' ] > 20 && $c_colis[ 'weight' ] <= 40) ? 1 : 0;
                            }else{
                                $isMax = ($c_colis[ 'weight' ] > 20000 && $c_colis[ 'weight' ] <= 40000) ? 1 : 0;
                            }

                           // var_dump($isMax);die();

                            if( $rc_relay_data['Relaismax'] == 1 && $isMax){
                                $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::DELIVERY_TYPE ] = '08';
                                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_IS_MAX, true );
                                $wc_order->save();
                            }else{
                                $dynamic_params_place_shipping_label[ WP_RC_B2C_Relay_Place_Advertisement::DELIVERY_TYPE ] = '00';
                            }

                            $b2c_datas[] = $dynamic_params_place_shipping_label;    

                            $total_weight += $c_colis['weight'];
                        }
                    }

                    if (!$is_c2c_interaction_mode && !empty($b2c_datas)){
                        foreach ($b2c_datas as &$b2c_data) {
                            $b2c_data['shippmentWeight'] = $total_weight;
                        }
                        unset($b2c_data);

                        // Call API
                        $b2c_relay_place_advertisement = WP_Relais_Colis_API::instance()->b2c_relay_place_advertisement( $b2c_datas, false );

                        if ( is_null( $b2c_relay_place_advertisement ) ) {

                            WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );
                            
                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ] );
                        }
                        
                        // Display response
                        if ( $b2c_relay_place_advertisement->validate() ) {

                            $entry = $b2c_relay_place_advertisement->entry;

                            if(is_array($entry)){
                                foreach ($entry as $key => $item) {
                                    $colis[$key]['shipping_label'] = $item;
                                }
                            }else{
                                $colis[0]['shipping_label'] = $entry;
                            }

                            WP_Log::debug( __METHOD__.' - Valid response', [
                                'Entry' => $entry,
                                'Shipping method' => $rc_shipping_method,
                            ], 'relais-colis-woocommerce' );

                            // Init RC status
                            WC_Orders_RC_Status_Manager::instance()->init_order_rc_status( $wc_order, $entry );

                        } else {

                            WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE ] );
                        }
                    }
                    break;

                case WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID:
                case WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID:

                    $b2c_datas = [];
                    $total_weight = 0;
                    // Request RC API place_advertisement
                    foreach ( $colis as &$c_colis ) {

                        // Depend on interaction mode (B2C or C2C)

                        // C2C - Home
                        if ( $is_c2c_interaction_mode ) {

                            // Not supported
                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_PLACE_ADVERTISEMENT_C2C_HOME_NOT_SUPPORTED ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_PLACE_ADVERTISEMENT_C2C_HOME_NOT_SUPPORTED ] );
                        } // B2C - Home
                        else {

                            // Get package weight
                            $dynamic_params_place_shipping_label[ WP_RC_Place_Advertisement_Request::SHIPPMENT_WEIGHT ] = ''.$c_colis[ 'weight' ];
                            $dynamic_params_place_shipping_label[ WP_RC_Place_Advertisement_Request::WEIGHT ] = ''.$c_colis[ 'weight' ];

                            $rc_services = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES );
                            WP_Log::debug( __METHOD__, [ '$rc_services' => $rc_services ], 'relais-colis-woocommerce' );

                            // Init values with defaults
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_ID ] = ''.$wc_order->get_customer_id();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_FULLNAME ] = $wc_order->get_shipping_first_name().' '.$wc_order->get_shipping_last_name();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_EMAIL ] = $wc_order->get_billing_email() ?? '';
                            $customer_phone = $wc_order->get_shipping_phone();
                            if ( empty( $customer_phone ) ) $customer_phone = '061234567890'; // FIXME ... trouver une autre solution
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_PHONE ] = $customer_phone;
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::CUSTOMER_MOBILE ] = $wc_order->get_shipping_phone() ?? '';
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::ORDER_REFERENCE ] = $wc_order->get_order_number();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::SHIPPING_ADDRESS_1 ] = $wc_order->get_shipping_address_1();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::SHIPPING_ADDRESS_2 ] = $wc_order->get_shipping_address_2() ?? '';
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::SHIPPING_POSTCODE ] = $wc_order->get_shipping_postcode();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::SHIPPING_CITY ] = $wc_order->get_shipping_city();
                            $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::SHIPPING_COUNTRY_CODE ] = $store_country;

                            // Build services RC params array from relay_data
                            $rc_prestations_param = WC_Orders_Manager::instance()->build_rc_prestations_param( $wc_order );
                            WP_Log::debug( __METHOD__.' - Build rc prestations param', [ '$rc_prestations_param' => $rc_prestations_param ], 'relais-colis-woocommerce' );
                            if ( !empty( $rc_prestations_param ) ) {

                                $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::PRESTATIONS ] = $rc_prestations_param;
                            }

                            // Home+ - Add a few new params
                            if ( $rc_shipping_method === WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID ) {

                                $rc_service_infos = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS );
                                WP_Log::debug( __METHOD__, [ '$rc_service_infos' => $rc_service_infos ], 'relais-colis-woocommerce' );

                                if ( !empty( $rc_service_infos ) && is_array( $rc_service_infos ) ) {

                                    //    [$session_rc_service_infos] => Array
                                    //        (
                                    //            [rc_service_digicode] => 1315
                                    //            [rc_service_floor] => 2
                                    //            [rc_service_type_habitat] => apartment
                                    //            [rc_service_elevator] => 1
                                    //            [rc_service_informations_complementaires] => Blabla
                                    //Prendre à gauche
                                    //Puis à droite
                                    //        )
                                    foreach ( $rc_service_infos as $rc_service_info => $rc_service_info_value ) {

                                        // Service key must start with WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                                        if ( strpos( $rc_service_info, WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) !== 0 ) continue;

                                        // Extract slug
                                        // Start after prefix WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                                        $slug = substr( $rc_service_info, strlen( WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) );

                                        // Extract informations
                                        //            "digicode": "{{DATA_CLT_digicode}}", # code de 0 à 8 caractères
                                        //            "floor": "{{DATA_CLT_floor}}",
                                        //            "housingType": "{{DATA_CLT_housing}}" # valeurs possible "maison" ou "appartement",
                                        //            "lift": "{{DATA_CLT_lift}}" # présence d'un ascenceur "1" ou "0",

                                        switch ( $slug ) {

                                            case WC_RC_Services_Manager::SERVICE_HOMEPLUS_DIGICODE:
                                                $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::DIGICODE ] = $rc_service_info_value;
                                                break;
                                            case WC_RC_Services_Manager::SERVICE_HOMEPLUS_FLOOR:
                                                $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::FLOOR ] = ''.$rc_service_info_value;
                                                break;
                                            case WC_RC_Services_Manager::SERVICE_HOMEPLUS_TYPE_OF_RESIDENCE:
                                                $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::HOUSING_TYPE ] = ( $rc_service_info_value == "house" ? "0" : ( $rc_service_info_value == "apartment" ? "1" : "" ) );
                                                break;
                                            case WC_RC_Services_Manager::SERVICE_HOMEPLUS_ELEVATOR:
                                                $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::LIFT ] = ''.$rc_service_info_value;
                                                break;
                                        }
                                    }
                                }
                                WP_Log::debug( __METHOD__.' - New request params', [ '$dynamic_params_place_shipping_label' => $dynamic_params_place_shipping_label ], 'relais-colis-woocommerce' );
                            } else {

                                $dynamic_params_place_shipping_label[ WP_RC_B2C_Home_Place_Advertisement::HOME_PLUS ] = '0';
                            }

                            $b2c_datas[] = $dynamic_params_place_shipping_label;    

                            $total_weight += $c_colis['weight'];

                        }
                    }

                    if (!$is_c2c_interaction_mode && !empty($b2c_datas)){

                        foreach ($b2c_datas as &$b2c_data) {
                            $b2c_data['shippmentWeight'] = $total_weight;
                        }
                        unset($b2c_data);
                        // Call API
                        $b2c_home_place_advertisement = WP_Relais_Colis_API::instance()->b2c_home_place_advertisement( $b2c_datas, false );

                        if ( is_null( $b2c_home_place_advertisement ) ) {

                            WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ] );
                        }

                        // Display response
                        if ( $b2c_home_place_advertisement->validate() ) {

                            $entry = $b2c_home_place_advertisement->entry;

                            if(is_array($entry)){
                                foreach ($entry as $key => $item) {
                                    $colis[$key]['shipping_label'] = $item;
                                }
                            }else{
                                $colis[0]['shipping_label'] = $entry;
                            }

                            WP_Log::debug( __METHOD__.' - Valid response', [
                                'Entry' => $entry,
                                'Shipping method' => $rc_shipping_method,
                            ], 'relais-colis-woocommerce' );

                            // Init RC status
                            WC_Orders_RC_Status_Manager::instance()->init_order_rc_status( $wc_order, $entry );

                        } else {

                            WP_Log::debug( __METHOD__.' - Invalid response', [], 'relais-colis-woocommerce' );

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE ] );
                        }
                    }

                    break;
            }

            // Save packages
            [ $colis, $items ] = $this->save_order_packages( $colis, $wc_order_id );

            // All is right then change state
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, WC_RC_Shipping_Constants::ORDER_STATE_SHIPPING_LABELS_PLACED );

            // Save order
            $wc_order->save();

            return [ $colis, $items ];

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::error( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );

            throw $wp_relais_colis_api_exception;
        }
    }

    /**
     * Place a shipping label for an order
     * @param $wc_order the WooCommerce order
     * @return array 2-uple colis and items
     */
    public function print_shipping_label( WC_Order $wc_order, $colis_index, $shipping_label ) {

        try {

            // Load packages
            $wc_order_id = $wc_order->get_id();
            [ $colis, $items ] = $this->load_order_packages( $wc_order_id );
            WP_Log::debug( __METHOD__, [ '$colis' => $colis, '$items' => $items ], 'relais-colis-woocommerce' );

            // Get interaction mode
            $is_c2c_interaction_mode = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode();

            // C2C - Relay
            if ( $is_c2c_interaction_mode ) {

                // If shipping label is received, then download PDF format
                // Dynamic params
                $option_rc_label_format = get_option( WC_RC_Shipping_Constants::OPTION_RC_LABEL_FORMAT );
                $dynamic_params_generate = array(
                    WP_RC_B2C_Generate::FORMAT => $option_rc_label_format,
                    WP_RC_B2C_Generate::ETIQUETTE1 => $shipping_label,
                );
                $c2c_generate = WP_Relais_Colis_API::instance()->c2c_generate( $dynamic_params_generate, false );

                if ( is_null( $c2c_generate ) ) {

                    WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );

                    // Pb occured... HTML response not permitted
                    throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ] );
                }

                // PDF downloaded successfully
                $colis[ $colis_index ][ 'shipping_label_pdf' ] = $c2c_generate->get_pdf_delivery_label();
                WP_Log::debug( __METHOD__.' - Print label - C2C OK', [ '$shipping_label' => $shipping_label ], 'relais-colis-woocommerce' );

            } // B2C - Relay
            else {

                // If shipping label is received, then download PDF format
                // Dynamic params
                $option_rc_label_format = get_option( WC_RC_Shipping_Constants::OPTION_RC_LABEL_FORMAT );
                $dynamic_params_generate = array(
                    WP_RC_B2C_Generate::FORMAT => $option_rc_label_format,
                    WP_RC_B2C_Generate::ETIQUETTE1 => $shipping_label,
                );
                $c2c_generate = WP_Relais_Colis_API::instance()->b2c_generate( $dynamic_params_generate, false );

                if ( is_null( $c2c_generate ) ) {

                    WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );

                    // Pb occured... HTML response not permitted
                    throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ] );
                }

                // PDF downloaded successfully
                $colis[ $colis_index ][ 'shipping_label_pdf' ] = $c2c_generate->get_pdf_delivery_label();
                WP_Log::debug( __METHOD__.' - Print label - B2C OK', [ '$shipping_label' => $shipping_label ], 'relais-colis-woocommerce' );

            }

            // Save packages
            [ $colis, $items ] = $this->save_order_packages( $colis, $wc_order_id );

            // All is right then change state
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, WC_RC_Shipping_Constants::ORDER_STATE_SHIPPING_LABELS_PLACED );

            // Save order
            $wc_order->save();

            return [ $colis, $items ];

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::error( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );

            throw $wp_relais_colis_api_exception;
        }
    }

    /**
     * Get all orders which are in a given state :
     * @param $state string One of ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED, ORDER_STATE_ITEMS_DISTRIBUTED, ORDER_STATE_SHIPPING_LABELS_PLACED, ORDER_STATE_WAY_BILLS_GENERATED
     * @return array of order ids
     */
    public function get_orders_with_state( $state ) {

        // Check that state does exist
        $authorized_states = WC_RC_Shipping_Constants::get_order_states();
        WP_Log::debug( __METHOD__, [ '$authorized_states' => $authorized_states ], 'relais-colis-woocommerce' );

        if ( !array_key_exists( $state, $authorized_states ) ) {

            return array();
        }

        $args = [
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
            'meta_query' => [
                [
                    'key' => WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE,
                    'value' => $state,
                    'compare' => '='
                ],
            ],
        ];

        $query = new WC_Order_Query( $args );
        WP_Log::debug( __METHOD__, [ '$query' => $query, 'args' => $args ], 'relais-colis-woocommerce' );

        $order_ids = $query->get_orders();
        WP_Log::debug( __METHOD__, [ '$order_ids' => $order_ids ], 'relais-colis-woocommerce' );
        return $order_ids;
    }

    /**
     * Bulk print shipping labels
     * @param $order_ids array of order IDs
     * @return void
     */
    public function bulk_print_shipping_labels( $order_ids ) {

        // Final result : if a few errors occurred, notices will be displayed, but not PDF returned
        $final_result = true;
        $shipping_labels_to_printed = array();
        $idsToPrint = array();

        // Parse all orders
        foreach ( $order_ids as $order_id ) {

            try {
                // Get WC order
                $wc_order = wc_get_order( $order_id );

                // Get order state
                $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );
                if ( $order_state != WC_RC_Shipping_Constants::ORDER_STATE_SHIPPING_LABELS_PLACED ) {

                    WP_Log::debug( __METHOD__.' - Order state incoherency', [ '$order_state' => $order_state ], 'relais-colis-woocommerce' );

                    // Pb occured... HTML response not permitted
                    $final_result = false;
                    throw new WP_Relais_Colis_API_Exception( __( 'Shipping labels must be placed first', 'relais-colis-woocommerce' ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INCOHERENCY_STATE ] );
                }

                // State is ok then get all shipping labels for these order
                $shipping_labels_by_order_id = WP_Orders_Rel_Shipping_Labels_DAO::instance()->get_shipping_labels_by_order_id( $order_id );
                if ( !empty( $shipping_labels_by_order_id ) ) {

                    foreach ( $shipping_labels_by_order_id as $shipping_label_by_order_id ) {

                        $shipping_labels_to_printed[] = $shipping_label_by_order_id[ 'shipping_label' ];
                        $idsToPrint[] = $order_id;
                    }
                }


            } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

                WP_Log::error( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );

                /**
                 * Notify 3rd party code on Relais Colis bulk action result
                 *
                 * @param int $order_id The order ID
                 * @param boolean $is_success true if success, otherwise false
                 * @param string $message A message associated with the hook
                 * @since 1.0.0
                 *
                 */
                do_action( "after_bulk_actions_rc_shop_order", $order_id, false, $wp_relais_colis_api_exception->getMessage() );

            }
        }

        // If errors occured, no need to call API
        if ( !$final_result ) return;

        WP_Log::debug( __METHOD__.' - Shipping labels extracted from orders', [ '$shipping_labels_to_printed' => $shipping_labels_to_printed, 'order_ids' => $order_ids ], 'relais-colis-woocommerce' );

        // Call API
        try {
            // Get format from config
            $option_rc_label_format = get_option( WC_RC_Shipping_Constants::OPTION_RC_LABEL_FORMAT );

            // Dynamic params
            $dynamic_params = array(
                WP_RC_Bulk_Generate::FORMAT => $option_rc_label_format,
            );
            $index = 1;
            foreach ( $shipping_labels_to_printed as $shipping_label_to_printed ) {

                $dynamic_params[ WP_RC_Bulk_Generate::ETIQUETTE.''.$index ] = $shipping_label_to_printed;
                $index++;
            }

            // Call API
            $bulk_generate = WP_Relais_Colis_API::instance()->bulk_generate( $dynamic_params, false );

            if ( is_null( $bulk_generate ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );

                // Pb occured... HTML response not permitted
                throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ] );
            }

            /**
             * Notify 3rd party code on Relais Colis bulk action result
             *
             * @param int $order_id The order ID
             * @param boolean $is_success true if success, otherwise false
             * @param string $message A message associated with the hook
             * @since 1.0.0
             *
             */
            
            $message = sprintf(
                /* translators: 1: shipping labels url */
                __( "Click <a href='%s' target='_blank'>here</a> to download the shipping labels.", 'relais-colis-woocommerce' ),
                esc_url( $bulk_generate->get_pdf_delivery_label() )
            );
            WP_Log::debug( __METHOD__, [ '$message' => '##'.$message.'##' ], 'relais-colis-woocommerce' );
            do_action( "after_bulk_actions_rc_shop_order", implode(',', $idsToPrint), true, $message );

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );

            /**
             * Notify 3rd party code on Relais Colis bulk action result
             *
             * @param int $order_id The order ID
             * @param boolean $is_success true if success, otherwise false
             * @param string $message A message associated with the hook
             * @since 1.0.0
             *
             */
            do_action( "after_bulk_actions_rc_shop_order", $order_id, false, $wp_relais_colis_api_exception->getMessage() );

        }
    }

    /**
     * Bulk generate way bills
     * @param $order_ids array of order IDs
     * @return void
     */
    public function bulk_generate_way_bills( $order_ids ) {

        // Final result : if a few errors occurred, notices will be displayed, but not PDF returned
        $final_result = true;
        $way_bills_to_be_generated = array();

        // Parse all orders
        foreach ( $order_ids as $order_id ) {

            try {
                // Get WC order
                $wc_order = wc_get_order( $order_id );

                // Get order state
                $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );
                if ( $order_state != WC_RC_Shipping_Constants::ORDER_STATE_SHIPPING_LABELS_PLACED ) {

                    WP_Log::debug( __METHOD__.' - Order state incoherency', [ '$order_state' => $order_state ], 'relais-colis-woocommerce' );

                    // Pb occured... HTML response not permitted
                    $final_result = false;
                    throw new WP_Relais_Colis_API_Exception( __( 'Shipping labels must be placed first', 'relais-colis-woocommerce' ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INCOHERENCY_STATE ] );
                }

            } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

                WP_Log::error( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage() ], 'relais-colis-woocommerce' );

                /**
                 * Notify 3rd party code on Relais Colis bulk action result
                 *
                 * @param int $order_id The order ID
                 * @param boolean $is_success true if success, otherwise false
                 * @param string $message A message associated with the hook
                 * @since 1.0.0
                 *
                 */
                do_action( "after_bulk_actions_rc_shop_order", $order_id, false, $wp_relais_colis_api_exception->getMessage() );

            }
        }

        // If errors occured, no need to call API
        if ( !$final_result ) return;

        // Call API
        try {

            // Get interaction mode
            $is_c2c_interaction_mode = WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode();

            // Only for B2C mode
            if ( $is_c2c_interaction_mode ) {

                // Pb occurred... invalid mode
                throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_INVALID_C2C_MODE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_INVALID_C2C_MODE ] );
            }

            // Prepare request (generic part)
            $dynamic_params = array();
            $index = 0;
            foreach ( $order_ids as $order_id ) {

                $dynamic_params[ WP_RC_Transport_Generate::COLIS.''.$index ] = $order_id;
            }

            // Call API
            $transport_generate = WP_Relais_Colis_API::instance()->transport_generate( $dynamic_params, false );

            if ( is_null( $transport_generate ) ) {

                WP_Log::debug( __METHOD__.' - No response', [], 'relais-colis-woocommerce' );

                // Pb occured... HTML response not permitted
                throw new WP_Relais_Colis_API_Exception( WP_Relais_Colis_API_Exception::get_i18n_message( WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ), WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::RC_API_NO_RESPONSE ] );
            }

            // Affect to order :
            // rc_way_bill
            $rc_way_bill = $transport_generate->get_pdf_transport_label();

            // Update order states
            foreach ( $order_ids as $order_id ) {

                // Get WC order
                $wc_order = wc_get_order( $order_id );

                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_WAY_BILL, $rc_way_bill );

                // All is right then change state
                $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, WC_RC_Shipping_Constants::ORDER_STATE_WAY_BILLS_GENERATED );

                $wc_order->save();
            }

            /**
             * Notify 3rd party code on Relais Colis bulk action result
             *
             * @param int $order_id The order ID
             * @param boolean $is_success true if success, otherwise false
             * @param string $message A message associated with the hook
             * @since 1.0.0
             *
             */
            $message = sprintf(
                /* translators: 1: way bills url */
                __( "Click <a href='%s' target='_blank'>here</a> to download the way bills.", 'relais-colis-woocommerce' ),
                esc_url( $rc_way_bill )
            );
            do_action( "after_bulk_actions_rc_shop_order", $order_id, true, $message );

        } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

            WP_Log::debug( __METHOD__.' - Error response', [ 'code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail() ], 'relais-colis-woocommerce' );

            /**
             * Notify 3rd party code on Relais Colis bulk action result
             *
             * @param int $order_id The order ID
             * @param boolean $is_success true if success, otherwise false
             * @param string $message A message associated with the hook
             * @since 1.0.0
             *
             */
            do_action( "after_bulk_actions_rc_shop_order", $order_id, false, $wp_relais_colis_api_exception->getMessage() );

        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook || 'shop_order' !== get_post_type()) {
            return;
        }

        // Get the order
        $order_id = get_the_ID();
        $order = wc_get_order($order_id);
        
        wp_localize_script('rc-order-packages', 'rc_order_packages', array(
            'label_add_a_package' => __('Add a package', 'relais-colis-woocommerce'),
            // ... autres labels ...
            'rc_order_status' => 'wc-' . $order->get_status()  // Ajouter le statut de la commande
        ));
    }
}
