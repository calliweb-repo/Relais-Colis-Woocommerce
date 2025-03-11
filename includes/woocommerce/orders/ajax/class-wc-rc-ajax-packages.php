<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use Exception;

/**
 * WooCommerce Shipping AJAX Handler for packages management
 *
 * @since     1.0.0
 */
class WC_RC_Ajax_Packages {

    // Use Trait Singleton
    use Singleton;

    // Packages ar stored as meta-data within Orders
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
    //
    //                ]
    //        ]

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
            $order = wc_get_order( $order_id );

            // Check if the order exists
            if ( !$order ) {
                wp_send_json_error( [
                    'message' => __( 'Order not found', 'relais-colis-woocommerce' )
                ] );
            }

            // Retrieve existing packages (if any)
            $colis = $order->get_meta( '_rc_colis', true ) ?: [];

            WP_Log::debug( __METHOD__.' - Before adding new package', [
                'order_id' => $order_id,
                'existing_package' => $colis
            ], 'relais-colis-woocommerce' );

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

            // Reindex to avoid holes
            $colis = is_array( $colis ) ? array_values( $colis ) : [];

            WP_Log::debug( __METHOD__.' - After adding new package', [
                'updated_colis' => $colis
            ], 'relais-colis-woocommerce' );

            // Update order meta
            $order->update_meta_data( '_rc_colis', $colis );
            $order->save(); // Required for HPOS

            // Prepare item list JSON data
            $items_json = WC_Order_Packages_Manager::instance()->build_remaining_items( $order, $colis, false );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items_json
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
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            WP_Log::debug( __METHOD__.' - Adding product to package', [
                'POST' => $_POST,
            ], 'relais-colis-woocommerce' );


            $order_id = intval( $_POST[ 'order_id' ] );
            $product_id = intval( $_POST[ 'product_id' ] );
            $quantity = intval( $_POST[ 'quantity' ] );
            $colis_index = intval( $_POST[ 'colis_index' ] );

            $order = wc_get_order( $order_id );

            // Check if the order exists
            if ( !$order ) {
                wp_send_json_error( [
                    'message' => __( 'Order not found', 'relais-colis-woocommerce' )
                ] );
            }

            $colis = $order->get_meta( '_rc_colis', true ) ?: [];

            WP_Log::debug( __METHOD__.' - Before adding product', [
                'order_id' => $order_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'colis_index' => $colis_index,
                'colis' => $colis,
            ], 'relais-colis-woocommerce' );

            // Ensure the package exists before adding products
            if ( !isset( $colis[ $colis_index ] ) ) {

                wp_send_json_error( [ 'message' => __( 'Package not found', 'relais-colis-woocommerce' ) ] );
            }

            $product = wc_get_product( $product_id );
            if ( !$product ) {

                wp_send_json_error( [ 'message' => __( 'Invalid product', 'relais-colis-woocommerce' ) ] );
            }

            // Must not add more than remaining
            $items = $order->get_items();
            foreach ( $items as $item_id => $item ) {

                $item_product = $item->get_product();
                $item_product_id = $item_product->get_id();
                if ( $item_product_id === $product_id ) {

                    $remaining_quantity = $item->get_quantity() - WC_Order_Packages_Manager::instance()->rc_count_product_in_colis( $item_product_id, $colis );
                    if ( $quantity > $remaining_quantity ) {

                        wp_send_json_error( [ 'message' => __( 'Not enough product remaining quantity', 'relais-colis-woocommerce' ) ] );
                    }
                }
            }

            // Adjust package
            $colis[ $colis_index ][ 'items' ][ $product_id ] = ( $colis[ $colis_index ][ 'items' ][ $product_id ] ?? 0 ) + $quantity;
            $colis[ $colis_index ][ 'weight' ] += $product->get_weight() * $quantity;

            // Reindex to avoid holes
            $colis = is_array( $colis ) ? array_values( $colis ) : [];

            WP_Log::debug( __METHOD__.' - After adding product', [ 'colis' => $colis ], 'relais-colis-woocommerce' );

            $order->update_meta_data( '_rc_colis', $colis );
            $order->save(); // Necessary for HPOS

            // Prepare item list JSON data
            $items_json = WC_Order_Packages_Manager::instance()->build_remaining_items( $order, $colis, false );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items_json
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
     * AJAX Handler: Remove a product from a package.
     */
    public function action_wp_ajax_rc_remove_from_colis() {

        try {
            // Nonce security check
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            $order_id = intval( $_POST[ 'order_id' ] );
            $product_id = intval( $_POST[ 'product_id' ] );
            $colis_index = intval( $_POST[ 'colis_index' ] );

            $order = wc_get_order( $order_id );

            // Check if the order exists
            if ( !$order ) {
                wp_send_json_error( [
                    'message' => __( 'Order not found', 'relais-colis-woocommerce' )
                ] );
            }

            $colis = $order->get_meta( '_rc_colis', true ) ?: [];

            WP_Log::debug( __METHOD__.' - Before removing product', [
                'order_id' => $order_id,
                'product_id' => $product_id,
                'colis_index' => $colis_index,
                'colis' => $colis,
            ], 'relais-colis-woocommerce' );

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

            // If the package is empty, remove it entirely
            /*if ( empty( $colis[ $colis_index ][ 'items' ] ) ) {

                unset( $colis[ $colis_index ] );
            }*/

            // Reindex to avoid holes
            $colis = is_array( $colis ) ? array_values( $colis ) : [];

            WP_Log::debug( __METHOD__.' - After removing product', [ 'colis' => $colis ], 'relais-colis-woocommerce' );

            $order->update_meta_data( '_rc_colis', $colis );
            $order->save(); // Necessary for HPOS

            // Prepare item list JSON data
            $items_json = WC_Order_Packages_Manager::instance()->build_remaining_items( $order, $colis, false );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items_json
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

            $order = wc_get_order( $order_id );

            // Check if the order exists
            if ( !$order ) {
                wp_send_json_error( [
                    'message' => __( 'Order not found', 'relais-colis-woocommerce' )
                ] );
            }

            $colis = $order->get_meta( '_rc_colis', true ) ?: [];

            WP_Log::debug( __METHOD__.' - Before deleting package', [
                'order_id' => $order_id,
                'colis_index' => $colis_index,
                'colis' => $colis,
            ], 'relais-colis-woocommerce' );

            // Ensure the package exists
            if ( !isset( $colis[ $colis_index ] ) ) {

                wp_send_json_error( [ 'message' => __( 'Package not found', 'relais-colis-woocommerce' ) ] );
            }

            // Adjust package
            // Delete
            unset( $colis[ $colis_index ] );

            // Reindex to avoid holes
            $colis = is_array( $colis ) ? array_values( $colis ) : [];

            WP_Log::debug( __METHOD__.' - After deleting package', [ 'colis' => $colis ], 'relais-colis-woocommerce' );

            $order->update_meta_data( '_rc_colis', $colis );
            $order->save(); // Necessary for HPOS

            // Prepare item list JSON data
            $items_json = WC_Order_Packages_Manager::instance()->build_remaining_items( $order, $colis, false );

            // Success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items_json
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
     * Try to put as much as possible items in a package
     * @param $items
     * @param $current_colis
     * @param $items_to_distribute
     * @param $max_weight
     * @return void
     */
    private function put_items_in_package( &$items, &$current_colis, &$items_to_distribute, $max_weight ) {

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
     * AJAX Handler: Auto distribute
     */
    public function action_wp_ajax_rc_auto_distribute() {

        try {
            check_ajax_referer( 'rc_woocommerce_nonce', 'nonce' );

            $order_id = intval( $_POST[ 'order_id' ] );
            $order = wc_get_order( $order_id );

            if ( !$order ) {

                wp_send_json_error( [ 'message' => __( 'Order not found', 'relais-colis-woocommerce' ) ] );
            }

            // Récupération des colis existants
            $colis = $order->get_meta( '_rc_colis', true ) ?: [];

            // Reindex to avoid holes
            $colis = is_array( $colis ) ? array_values( $colis ) : [];

            // Liste des produits restants
            $items = WC_Order_Packages_Manager::instance()->build_remaining_items( $order, $colis, false );

            WP_Log::debug( __METHOD__.' - Before auto distribute', [
                'order_id' => $order_id,
                'items' => $items,
                'colis' => $colis,
            ], 'relais-colis-woocommerce' );

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

            // Mise à jour de la commande
            $order->update_meta_data( '_rc_colis', $colis );
            $order->save();

            // New items whould be empty
            //$new_items = WC_Order_Packages_Manager::instance()->build_remaining_items( $order, $colis, false );

            WP_Log::debug( __METHOD__.' - After auto distribute', [
                'order_id' => $order_id,
                'items' => $items,
                'colis' => $colis,
            ], 'relais-colis-woocommerce' );

            wp_send_json_success( [
                'colis' => $colis,
                'items' => $items
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

            // Get order
            $order = wc_get_order( $order_id );

            // Check if the order exists
            if ( !$order ) {

                wp_send_json_error( [ 'message' => __( 'Order not found', 'relais-colis-woocommerce' ) ] );
            }

            // Get existing packages
            $colis = $order->get_meta( '_rc_colis', true ) ?: [];
            if ( !isset( $colis[ $colis_index ] ) ) {

                wp_send_json_error( [ 'message' => __( 'Package not found', 'relais-colis-woocommerce' ) ] );
            }

            WP_Log::debug( __METHOD__.' - Before updating package', [
                'order_id' => $order_id,
                'colis_index' => $colis_index,
                'new_weight' => $new_weight,
                'new_height' => $new_height,
                'new_width' => $new_width,
                'new_length' => $new_length,
                'colis' => $colis,
            ], 'relais-colis-woocommerce' );

            // Update package infos : weight and dimensions
            $colis[ $colis_index ][ 'weight' ] = $new_weight;
            $colis[ $colis_index ][ 'dimensions' ] = [
                'height' => $new_height,
                'width' => $new_width,
                'length' => $new_length,
            ];

            // Update the order meta data
            $order->update_meta_data( '_rc_colis', $colis );
            $order->save();

            WP_Log::debug( __METHOD__.' - After updating package', [ 'colis' => $colis ], 'relais-colis-woocommerce' );

            // Send success response
            wp_send_json_success( [
                'colis' => $colis,
                'items' => WC_Order_Packages_Manager::instance()->build_remaining_items( $order, $colis, false )
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
