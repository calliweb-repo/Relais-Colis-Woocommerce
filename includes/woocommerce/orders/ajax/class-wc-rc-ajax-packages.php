<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use Exception;

/**
 * Class WC_RC_Ajax_Packages
 *
 * This class handles WooCommerce AJAX requests related to package management (colis) in the Relais Colis shipping system.
 * It provides functionalities for adding, updating, removing, and distributing packages within orders.
 *
 * ## Key Responsibilities:
 * - **Manage Packages (Colis) via AJAX**: Handles WooCommerce AJAX requests for package operations.
 * - **Seamless Order Meta Updates**: Manages `_rc_colis` meta key to store package details.
 * - **Auto-Distribution of Products**: Implements logic to automatically distribute products into available packages.
 * - **Weight & Dimension Updates**: Supports real-time updates to package weights and dimensions.
 * - **Robust Logging & Debugging**: Uses WP_Log to track operations for debugging purposes.
 *
 * ## Data Structure:
 * Each order contains an `_rc_colis` meta key that stores package details:
 * ```php
 * '_rc_colis' => [
 *     'items' => [
 *         [ 'id' => 83, 'name' => 'Ab.', 'weight' => 120, 'quantity' => 2, 'remaining_quantity' => 0 ],
 *         [ 'id' => 73, 'name' => 'Aut.', 'weight' => 25000, 'quantity' => 1, 'remaining_quantity' => 1 ]
 *     ],
 *     'colis' => [
 *         [
 *             'items' => [ 83 => 2 ],
 *             'weight' => 240,
 *             'dimensions' => [ 'height' => 0, 'width' => 0, 'length' => 0 ],
 *             'shipping_label' => '4H013000008101',
 *             'shipping_label_pdf' => '<url>',
 *             'shipping_status' => 'status_rc_depose_en_relais'
 *         ]
 *     ]
 * ]
 * ```
 *
 * ## Methods Overview:
 * - `action_wp_ajax_rc_add_colis()`: Creates a new empty package for an order.
 * - `action_wp_ajax_rc_add_to_colis()`: Adds a product to an existing package.
 * - `action_wp_ajax_rc_remove_from_colis()`: Removes a product from a package.
 * - `action_wp_ajax_rc_delete_colis()`: Deletes an entire package.
 * - `action_wp_ajax_rc_auto_distribute()`: Automatically distributes products into available packages.
 * - `action_wp_ajax_rc_update_colis()`: Updates package weight and dimensions.
 *
 * ## Workflow:
 * 1. **Package Management**:
 *    - Users can add/remove packages and assign products dynamically via AJAX.
 *    - All modifications are stored in WooCommerce order meta.
 *
 * 2. **Auto Distribution**:
 *    - Automatically attempts to distribute products among available packages.
 *    - Ensures packages do not exceed weight limits.
 *
 * 3. **Shipping Label Handling**:
 *    - Stores generated shipping labels for packages.
 *    - Updates shipping status dynamically.
 *
 * ## WooCommerce Hooks Used:
 * - `wp_ajax_rc_add_colis`
 * - `wp_ajax_rc_add_to_colis`
 * - `wp_ajax_rc_remove_from_colis`
 * - `wp_ajax_rc_delete_colis`
 * - `wp_ajax_rc_auto_distribute`
 * - `wp_ajax_rc_update_colis`
 *
 * ## ⚠Considerations:
 * - **Security**: Implements nonce verification to prevent unauthorized requests.
 * - **Database Optimization**: Uses indexed meta keys for fast lookups.
 * - **Scalability**: Designed to handle large orders efficiently.
 *
 * ## Example API Response:
 * ```json
 * {
 *     "success": true,
 *     "colis": [
 *         {
 *             "items": { "83": 2 },
 *             "weight": 240,
 *             "dimensions": { "height": 0, "width": 0, "length": 0 },
 *             "shipping_label": "4H013000008101",
 *             "shipping_status": "status_rc_depose_en_relais"
 *         }
 *     ],
 *     "items": [
 *         { "id": 83, "name": "Ab.", "weight": 120, "quantity": 2, "remaining_quantity": 0 },
 *         { "id": 73, "name": "Aut.", "weight": 25000, "quantity": 1, "remaining_quantity": 1 }
 *     ]
 * }
 * ```
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_RC_Ajax_Packages {

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

        // All actions are sent using AJAX
        add_action( 'wp_ajax_rc_add_colis', array( $this, 'action_wp_ajax_rc_add_colis' ) );
        add_action( 'wp_ajax_rc_add_to_colis', array( $this, 'action_wp_ajax_rc_add_to_colis' ) );
        add_action( 'wp_ajax_rc_remove_from_colis', array( $this, 'action_wp_ajax_rc_remove_from_colis' ) );
        add_action( 'wp_ajax_rc_delete_colis', array( $this, 'action_wp_ajax_rc_delete_colis' ) );
        add_action( 'wp_ajax_rc_auto_distribute', array( $this, 'action_wp_ajax_rc_auto_distribute' ) );
        add_action( 'wp_ajax_rc_update_colis', array( $this, 'action_wp_ajax_rc_update_colis' ) );
    }

    /**
     * AJAX Handler: Add a new empty package (colis) to the order.
     */
    public function action_wp_ajax_rc_add_colis() {

        try {
            // Nonce security check
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            WP_Log::debug( __METHOD__.' - Adding package', [
                'POST' => $_POST,
            ], 'relais-colis-woocommerce' );

            // Validate the order ID
            if ( !isset( $_POST[ 'order_id' ] ) || !is_numeric( $_POST[ 'order_id' ] ) ) {
                wp_send_json_error( [
                    'message' => __( 'Invalid order ID', 'relais-colis-woocommerce' )
                ] );
            }
            $order_id = intval( $_POST[ 'order_id' ] );

            // Load packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->load_order_packages( $order_id );

            // Add a new empty package
            $colis[] = [
                'items' => [],
                'weight' => 0,
                'dimensions' => [
                    'height' => 0,
                    'width' => 0,
                    'length' => 0,
                ]
            ];

            // Save packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->save_order_packages( $colis, $order_id );

            WP_Log::debug( __METHOD__.' - After adding new package', [
                'updated_colis' => $colis
            ], 'relais-colis-woocommerce' );

            // Get order state
            $wc_order = wc_get_order( $order_id );
            $order_state = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items,
                'rc_order_state' => $order_state
            ] );

        } catch ( Exception $e ) {
            WP_Log::error( __METHOD__.' - Error adding package', [
                'error_message' => $e->getMessage(),
                'order_id' => $order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while adding a package', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }

    /**
     * AJAX Handler: Add a product to an existing package.
     */
    public function action_wp_ajax_rc_add_to_colis() {
        try {
            // Nonce security check
            check_ajax_referer('rc_woocommerce_nonce', 'nonce');

            WP_Log::debug(__METHOD__.' - Adding product to package', [
                'POST' => $_POST,
            ], 'relais-colis-woocommerce');

            $order_id = intval($_POST['order_id']);
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            $colis_index = intval($_POST['colis_index']);

            // Load packages
            [$colis, $items] = WC_Order_Packages_Manager::instance()->load_order_packages($order_id);

            // Ensure the package exists before adding products
            if (!isset($colis[$colis_index])) {
                wp_send_json_error(['message' => __('Package not found', 'relais-colis-woocommerce')]);
            }

            $product = wc_get_product($product_id);
            if (!$product) {
                wp_send_json_error(['message' => __('Invalid product', 'relais-colis-woocommerce')]);
            }

            // Get WC order
            $order = wc_get_order($order_id);

            $isMax = 0;
            $isHome = 0;

            $woocommerce_weight_unit = get_option('woocommerce_weight_unit', 'g');

            // Vérifier tous les produits de la commande
            $order_items = $order->get_items();
            foreach ($order_items as $item_id => $item) {
                // Récupérer le produit associé à l'item
                $item_product = $item->get_product();
                $item_product_id = $item_product->get_id();
                
                // Plusieurs méthodes pour obtenir le poids
                $item_weight = 0;
                if (method_exists($item, 'get_weight')) {
                    $item_weight = $item->get_weight();
                } else if ($item_product && method_exists($item_product, 'get_weight')) {
                    // Obtenir le poids via l'objet produit
                    $item_weight = $item_product->get_weight();
                } else {
                    // Fallback sur les métadonnées du produit
                    $item_weight = get_post_meta($item_product_id, '_weight', true);
                }
                
                if ($item_product_id === $product_id) {
                    $remaining_quantity = $item->get_quantity() - WC_Order_Packages_Manager::instance()->rc_count_product_in_colis($item_product_id, $colis);
                    if ($quantity > $remaining_quantity) {
                        wp_send_json_error(['message' => __('Not enough product remaining quantity', 'relais-colis-woocommerce')]);
                    }
                }

                if (!empty($item_weight)) {
                    $weight_in_grams = WP_Helper::convert_to_grams($item_weight, $woocommerce_weight_unit);
                    

                    if ($weight_in_grams > 20000 && $weight_in_grams <= 40000) {
                        $isMax = 1;
                    }

                    if ($weight_in_grams > 40000 && $weight_in_grams <= 130000) {
                        $isHome = 1;
                        break;
                    }
                }
                
                // Vérification de la quantité restante seulement pour le produit qu'on veut ajouter
                if ($item_product_id === $product_id) {
                    $remaining_quantity = $item->get_quantity() - WC_Order_Packages_Manager::instance()->rc_count_product_in_colis($item_product_id, $colis);
                    if ($quantity > $remaining_quantity) {
                        wp_send_json_error(['message' => __('Not enough product remaining quantity', 'relais-colis-woocommerce')]);
                    }
                }
            }

            // Récupérer la méthode d'expédition de la commande
            $shipping_methods = $order->get_shipping_methods();
            $shipping_method = '';
            
            foreach ($shipping_methods as $shipping_method_obj) {
                $shipping_method = $shipping_method_obj->get_method_id();
                break; // On prend la première méthode d'expédition trouvée
            }

            if ($shipping_method === 'wc_rc_shipping_method_homeplus' || $shipping_method === 'wc_rc_shipping_method_homeplus_colis' ) {
                $max_weight = 1300000;
                $isHome = 1;
            }


            // Distribution strategy is : try and put as max as possible items in each package
            $max_weight = $isMax ? 40000 : 20000; // max per package, in grams
            $max_weight = $isHome ? 130000 : $max_weight;
            // Get current package weight
            $c_weigth = $product->get_weight();
            $c_weigth_grams = WP_Helper::convert_to_grams($c_weigth, $woocommerce_weight_unit);

            // If weigth is too important, then cannot distribute product
            if ($c_weigth_grams > $max_weight) {
                wp_send_json_error([
                    'message' => __('This product is too heavy to be added to a package.', 'relais-colis-woocommerce'),
                    'error_details' => ''
                ]);
            }

            // Adjust package
            $colis[$colis_index]['items'][$product_id] = ($colis[$colis_index]['items'][$product_id] ?? 0) + $quantity;
            $colis[$colis_index]['weight'] += (float)$product->get_weight() * $quantity;

            $package_weigth_grams = WP_Helper::convert_to_grams($colis[$colis_index]['weight'], $woocommerce_weight_unit);
            if($package_weigth_grams > $max_weight) {
                wp_send_json_error([
                    'message' => __('This package is too heavy to be added to a package.', 'relais-colis-woocommerce'),
                    'error_details' => ''
                ]);
            }

            // Save packages
            [$colis, $items] = WC_Order_Packages_Manager::instance()->save_order_packages($colis, $order_id);

            // If no remaining items, then order change to state ORDER_STATE_ITEMS_DISTRIBUTED
            if ( !WC_Order_Packages_Manager::instance()->has_remaining_items( $items ) ) {

                $order = wc_get_order( $order_id );
                $order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_DISTRIBUTED );

                // Save order
                $order->save();
            }

            WP_Log::debug( __METHOD__.' - After adding product', [ 'colis' => $colis ], 'relais-colis-woocommerce' );

            // Get order state
            $order_state = $order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items,
                'rc_order_state' => $order_state
            ] );

        } catch ( Exception $e ) {

            WP_Log::error( __METHOD__.' - Error adding package', [
                'error_message' => $e->getMessage(),
                'order_id' => $order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while adding product to a package', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }

    /**
     * AJAX Handler: Remove a product from a package.
     */
    public function action_wp_ajax_rc_remove_from_colis() {

        try {
            // Nonce security check
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            $order_id = intval( $_POST[ 'order_id' ] );
            $product_id = intval( $_POST[ 'product_id' ] );
            $colis_index = intval( $_POST[ 'colis_index' ] );

            // Load packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->load_order_packages( $order_id );

            // Ensure the package exists and contains the product
            if ( !isset( $colis[ $colis_index ] ) || !isset( $colis[ $colis_index ][ 'items' ][ $product_id ] ) ) {

                wp_send_json_error( [ 'message' => __( 'Product not found in package', 'relais-colis-woocommerce' ) ] );
            }

            $product = wc_get_product( $product_id );
            if ( !$product ) {

                wp_send_json_error( [ 'message' => __( 'Invalid product', 'relais-colis-woocommerce' ) ] );
            }

            // Adjust package
            // Subtract the product's weight
            $colis[ $colis_index ][ 'weight' ] -= $product->get_weight() * $colis[ $colis_index ][ 'items' ][ $product_id ];
            unset( $colis[ $colis_index ][ 'items' ][ $product_id ] );

            // Save packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->save_order_packages( $colis, $order_id );

            // Get WC order
            $order = wc_get_order( $order_id );

            // If remaining items, then order change to state ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED
            if ( WC_Order_Packages_Manager::instance()->has_remaining_items( $items ) ) {

                $order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED );

                // Save order
                $order->save();
            }

            WP_Log::debug( __METHOD__.' - After removing product', [ 'colis' => $colis ], 'relais-colis-woocommerce' );

            // Get order state
            $order_state = $order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items,
                'rc_order_state' => $order_state
            ] );

        } catch ( Exception $e ) {

            WP_Log::error( __METHOD__.' - Error adding package', [
                'error_message' => $e->getMessage(),
                'order_id' => $order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while adding a package', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }

    /**
     * AJAX Handler: Delete an entire package.
     */
    public function action_wp_ajax_rc_delete_colis() {

        try {
            // Nonce security check
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            $order_id = intval( $_POST[ 'order_id' ] );
            $colis_index = intval( $_POST[ 'colis_index' ] );

            // Load packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->load_order_packages( $order_id );

            // Ensure the package exists
            if ( !isset( $colis[ $colis_index ] ) ) {

                wp_send_json_error( [ 'message' => __( 'Package not found', 'relais-colis-woocommerce' ) ] );
            }

            // Adjust package
            // Delete
            unset( $colis[ $colis_index ] );

            // Save packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->save_order_packages( $colis, $order_id );

            // Get WC order
            $order = wc_get_order( $order_id );

            // If remaining items, then order change to state ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED
            if ( WC_Order_Packages_Manager::instance()->has_remaining_items( $items ) ) {

                $order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED );

                // Save order
                $order->save();
            }

            WP_Log::debug( __METHOD__.' - After deleting package', [ 'colis' => $colis ], 'relais-colis-woocommerce' );

            // Get order state
            $order_state = $order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items,
                'rc_order_state' => $order_state
            ] );

        } catch ( Exception $e ) {

            WP_Log::error( __METHOD__.' - Error adding package', [
                'error_message' => $e->getMessage(),
                'order_id' => $order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while removing product from package', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }

    /**
     * AJAX Handler: Auto distribute
     */
    public function action_wp_ajax_rc_auto_distribute() {

        try {
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            $order_id = intval( $_POST[ 'order_id' ] );

            // Distribution strategy is : try and put as max as possible items in each package
            $auto_distribute_packages_result = WC_Order_Packages_Manager::instance()->auto_distribute_packages( $order_id );
            if ( $auto_distribute_packages_result === false ) {

                wp_send_json_error( [
                    'message' => __( 'The products have already been distributed into packages', 'relais-colis-woocommerce' ),
                ] );
            }

            // Load packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->load_order_packages( $order_id );

            WP_Log::debug( __METHOD__.' - After auto distribute', [
                'order_id' => $order_id,
                'items' => $items,
                'colis' => $colis,
            ], 'relais-colis-woocommerce' );

            // Get order state
            $order = wc_get_order( $order_id );
            $order_state = $order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

            // Respond as error if there are more remaining items to distribute
            if ( WC_Order_Packages_Manager::instance()->has_remaining_items( $items ) ) {

                wp_send_json_error( [
                    'message' => __( 'There are still products to be distributed into packages', 'relais-colis-woocommerce' ),
                    'error_details' => ''
                ] );

            }
            else {
                wp_send_json_success( [
                    'colis' => $colis,
                    'items' => $items,
                    'rc_order_state' => $order_state
                ] );

            }
        } catch ( Exception $e ) {

            WP_Log::error( __METHOD__.' - Error adding package', [
                'error_message' => $e->getMessage(),
                'order_id' => $order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while auto distributing products into packages', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }

    /**
     * AJAX Handler: Update a package (dimensions, total weight...)
     */
    public function action_wp_ajax_rc_update_colis() {

        try {
            // Nonce security check
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            // Order id validation
            if ( !isset( $_POST[ 'order_id' ] ) || !is_numeric( $_POST[ 'order_id' ] ) ) {

                wp_send_json_error( [ 'message' => __( 'Invalid order ID', 'relais-colis-woocommerce' ) ] );
            }

            // Get AJAX params
            $order_id = intval( $_POST[ 'order_id' ] );
            $colis_index = intval( $_POST[ 'colis_index' ] );
            $new_weight = floatval( $_POST[ 'weight' ] );
            $new_height = isset( $_POST[ 'height' ] ) ? floatval( $_POST[ 'height' ] ) : null;
            $new_width = isset( $_POST[ 'width' ] ) ? floatval( $_POST[ 'width' ] ) : null;
            $new_length = isset( $_POST[ 'length' ] ) ? floatval( $_POST[ 'length' ] ) : null;

            // Load packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->load_order_packages( $order_id );

            // Update package infos : weight and dimensions
            $colis[ $colis_index ][ 'weight' ] = $new_weight;
            $colis[ $colis_index ][ 'dimensions' ] = [
                'height' => $new_height,
                'width' => $new_width,
                'length' => $new_length,
            ];

            $max_weight = 20000; // max per package, in grams

            $isMax = 0;
            $isHome = 0;
            $woocommerce_weight_unit = get_option('woocommerce_weight_unit', 'g');
            
            // Get order and check all products
            $order = wc_get_order($order_id);
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product) {
                    $item_weight = $product->get_weight();
                    if (!empty($item_weight)) {
                        $weight_in_grams = WP_Helper::convert_to_grams($item_weight, $woocommerce_weight_unit);
                        if ($weight_in_grams > 20000 && $weight_in_grams <= 40000) {
                            $isMax = 1;
                        }              
                        if ($weight_in_grams > 40000 && $weight_in_grams <= 130000) {
                            $isHome = 1;
                            break;
                        }
                    }
                }
            }

            // Set max_weight based on products weight
            $max_weight = $isMax ? 40000 : 20000;
            if ($isHome) {
                $max_weight = 130000;
            }

            // Check if package weight exceeds max_weight
            $package_weight_grams = WP_Helper::convert_to_grams($colis[$colis_index]['weight'], $woocommerce_weight_unit);
            if ($package_weight_grams > $max_weight) {
                wp_send_json_error([
                    'message' => __('This package is too heavy.', 'relais-colis-woocommerce'),
                    'error_details' => ''
                ]); 
            }

            // Save packages
            [$colis, $items] = WC_Order_Packages_Manager::instance()->save_order_packages($colis, $order_id);

            WP_Log::debug( __METHOD__.' - After updating package', [ 'colis' => $colis ], 'relais-colis-woocommerce' );

            // Get order state
            $order = wc_get_order( $order_id );
            $order_state = $order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE );

            // Send success response
            wp_send_json_success([
                'colis' => $colis,
                'items' => WC_Order_Packages_Manager::instance()->build_remaining_items( $order, $colis, false ),
                'rc_order_state' => $order_state
            ] );

        } catch ( Exception $e ) {
            WP_Log::error( __METHOD__.' - Error updating package', [
                'error_message' => $e->getMessage(),
                'order_id' => $order_id
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [
                'message' => __( 'An error occurred while updating the package', 'relais-colis-woocommerce' ),
                'error_details' => $e->getMessage()
            ] );
        }
    }
}
