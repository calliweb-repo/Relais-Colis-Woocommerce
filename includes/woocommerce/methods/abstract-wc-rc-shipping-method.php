<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Tariff_Grids_DAO;
use RelaisColisWoocommerce\RCAPI\WP_RC_C2C_Get_Packages_Price;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Shipping_Method;


/**
 * Specific WooCommerce Shipping Method Class for Relais Colis
 *
 * Extend shipping methods to handle shipping calculations etc.
 *
 * @since     1.0.0
 */
abstract class WC_RC_Shipping_Method extends WC_Shipping_Method {

    /**
     * Constructor.
     *
     * @param int $instance_id Instance ID.
     */
    public function __construct( $instance_id = 0 ) {

        parent::__construct( $instance_id );

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        // Have to be defined in child
        // - Unique ID: $this->id
        // - Method title: $this->method_title
        // - Method description: $this->method_description
        // - Title: $this->title

        // Default activation
        $this->enabled = "yes";

        // Define what this shipping method supports
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        // Load method options
        // Have to be called in child class: $this->init();
    }

    /**
     * Processes and saves global shipping method options in the admin area.
     *
     * This method is usually attached to woocommerce_update_options_x hooks.
     *
     * @return bool was anything saved?
     * @since 2.6.0
     */
    public function process_admin_options() {

        WP_Log::debug( __METHOD__.' - Override process_admin_options', [ 'POST' => $_POST ], 'relais-colis-woocommerce' );
        //[POST] => Array
        //        (
        //            [wc_shipping_zones_nonce] => db7d2e4298
        //            [instance_id] => 8
        //            [data] => Array
        //                (
        //                    [woocommerce_wc_rc_shipping_method_relay_enabled] => 1
        //                    [woocommerce_wc_rc_shipping_method_relay_title] => Relais Colis - Point relais
        //                    [instance_id] => 8
        //                )
        //
        //        )

        if ( isset( $_POST[ 'data' ] ) && is_array( $_POST[ 'data' ] ) ) {

            foreach ( $_POST[ 'data' ] as $key => $value ) {

                $clean_key = str_replace( 'woocommerce_'.$this->id.'_', '', $key );
                $this->settings[ $clean_key ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
            }
            // Persist in DB
            update_option( $this->get_option_key(), $this->settings );
            $this->init_settings();
            $this->title = $this->settings[ 'title' ]; // Assure la mise à jour
        }

        return true;
    }

    /**
     * Helper method used to calculate package weight
     * @param $package the package param of calculate shipping
     * @return float|void the weight, otherwise null if at least one weight is not provided
     */
    private function get_package_weight( $package ) {

        //    [package] => Array
        //        (
        //            [contents] => Array
        //                (
        //                    [fe9fc289c3ff0af142b6d3bead98a923] => Array
        //                        (
        //                            [key] => fe9fc289c3ff0af142b6d3bead98a923
        //                            [product_id] => 83
        //                            [variation_id] => 0
        //                            [variation] => Array
        //                                (
        //                                )
        //
        //                            [quantity] => 1
        //                            [data_hash] => b5c1d5ca8bae6d4896cf1807cdf763f0
        //                            [line_tax_data] => Array
        //                                (
        //                                    [subtotal] => Array
        //                                        (
        //                                        )
        //
        //                                    [total] => Array
        //                                        (
        //                                        )
        //
        //                                )
        //
        //                            [line_subtotal] => 31.1
        //                            [line_subtotal_tax] => 0
        //                            [line_total] => 31.1
        //                            [line_tax] => 0
        //                            [data] => Array
        //                                (
        //                                    [WC_Product_Simple] => {"id":83,"name":"Ab.","slug":"ab","date_created":{"date":"2025-01-29 16:54:08.000000","timezone_type":3,"timezone":"Europe\/Paris"},"date_modified":{"date":"2025-02-03 16:53:10.000000","timezone_type":3,"timezone":"Europe\/Paris"},"status":"publish","featured":false,"catalog_visibility":"visible","description":"Excepturi eum dolore consequatur perferendis culpa. Quidem saepe enim voluptatem sint et non quisquam adipisci. Id voluptas fuga consequatur facere expedita reprehenderit necessitatibus. Enim ut rerum voluptatem ipsam soluta cupiditate et aperiam. Molestias expedita eum corporis deserunt ipsam. Facere laboriosam autem et facilis. Adipisci vel nisi aspernatur necessitatibus delectus aut. Reiciendis totam sint dolorum mollitia. Laborum esse quos assumenda delectus eum et officiis expedita. At qui ipsa est tempore quia. Molestiae et quia fugiat quia deserunt voluptas consequatur.","short_description":"","sku":"","global_unique_id":"","price":"31.1","regular_price":"31.1","sale_price":"30.1","date_on_sale_from":null,"date_on_sale_to":null,"total_sales":1,"tax_status":"taxable","tax_class":"","manage_stock":false,"stock_quantity":null,"stock_status":"instock","backorders":"no","low_stock_amount":"","sold_individually":false,"weight":"1.2","length":"50","width":"60","height":"70","upsell_ids":[],"cross_sell_ids":[],"parent_id":0,"reviews_allowed":false,"purchase_note":"","attributes":[],"default_attributes":[],"menu_order":0,"post_password":"","virtual":false,"downloadable":false,"category_ids":[16],"tag_ids":[],"shipping_class_id":0,"downloads":[],"image_id":"84","gallery_image_ids":[],"download_limit":-1,"download_expiry":-1,"rating_counts":[],"average_rating":"0","review_count":0,"cogs_value":0,"meta_data":[]}
        //                                )
        //
        //                        )
        //
        //                )
        $total_weight = 0;

        foreach ( $package[ 'contents' ] as $item_id => $item ) {

            // Get nb of units
            $quantity = $item[ 'quantity' ];

            // Get one unit weight
            $product = $item[ 'data' ];
            $weight = $product->get_weight();
            WP_Log::debug( __METHOD__, [ '$quantity' => $quantity, '$product' => $product ], 'relais-colis-woocommerce' );

            // At least one product with no weight, return null
            if ( is_null( $weight ) || ( $weight === '' ) ) continue;

            // Add weight to total
            $total_weight += $quantity * $weight;
        }
        return $total_weight;
    }

    /**
     * Get the specific ID for Relais Colis child class
     */
    abstract protected function get_wc_rc_shipping_method_default_title();

    /**
     *  Init defines the parameter strategy for loading/saving
     */
    public function init() {
        WP_Log::debug( __METHOD__.' - Beginning', [], 'relais-colis-woocommerce' );

        // Load parameters
        $this->init_form_fields();
        WP_Log::debug( __METHOD__.' - After init_form_fields ', [ '$this->settings' => $this->settings ], 'relais-colis-woocommerce' );

        $this->init_settings();

        WP_Log::debug( __METHOD__.' - After init_form_fields and init_settings', [ '$this->settings' => $this->settings ], 'relais-colis-woocommerce' );

        //$this->title = $this->get_option('title');
        $this->title = isset( $this->settings[ 'title' ] ) ? sanitize_text_field( $this->settings[ 'title' ] ) : $this->get_wc_rc_shipping_method_default_title();
        $this->method_title = isset( $this->settings[ 'title' ] ) ? sanitize_text_field( $this->settings[ 'title' ] ) : $this->get_wc_rc_shipping_method_default_title();

        // Save parameters
        add_action( 'woocommerce_update_options_shipping_'.$this->id, array( $this, 'process_admin_options' ) );

        WP_Log::debug( __METHOD__.' - Init at the end', [ '$this->settings' => $this->settings, ], 'relais-colis-woocommerce' );
    }

    /**
     * Initialise Settings.
     *
     * Store all settings in a single database entry
     * and make sure the $settings array is either the default
     * or the settings stored in the database.
     *
     * @since 1.0.0
     * @uses get_option(), add_option()
     */
    public function init_settings() {
        WP_Log::debug( __METHOD__.' - Beginning', [], 'relais-colis-woocommerce' );
        $this->settings = get_option( $this->get_option_key(), null );
        WP_Log::debug( __METHOD__.' - After get_option_key ', [ '$this->settings' => $this->settings, 'option_key' => $this->get_instance_option_key() ], 'relais-colis-woocommerce' );

        // If there are no settings defined, use defaults.
        if ( !is_array( $this->settings ) ) {
            $form_fields = $this->get_form_fields();
            $this->settings = array_merge( array_fill_keys( array_keys( $form_fields ), '' ), wp_list_pluck( $form_fields, 'default' ) );
        }
    }

    /**
     * Initialise Shipping Settings Form Fields.
     */
    public function init_form_fields() {

        WP_Log::debug( __METHOD__.' - Init Form fields', [ '$this->form_fields' => $this->form_fields, '$this->settings' => $this->settings ], 'relais-colis-woocommerce' );

        $this->form_fields = [
            'enabled' => [
                'title' => __( 'Enable/Disable', 'relais-colis-woocommerce' ),
                'type' => 'checkbox',
                'description' => __( 'Enable this shipping method.', 'relais-colis-woocommerce' ),
                'default' => 'yes',
            ],
            'title' => [
                'title' => __( 'Title', 'relais-colis-woocommerce' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'relais-colis-woocommerce' ),
                'default' => $this->get_wc_rc_shipping_method_default_title(),
            ],
        ];
        WP_Log::debug( __METHOD__.' - Init Form fields OK', [ '$this->form_fields' => $this->form_fields, '$this->settings' => $this->settings ], 'relais-colis-woocommerce' );
    }

    /**
     * Called to calculate shipping rates for this method. Rates can be added using the add_rate() method.
     *
     * @param array $package Package array.
     * @override
     */
    public function calculate_shipping( $package = [] ) {

        WP_Log::debug( __METHOD__, [ 'package' => $package ], 'relais-colis-woocommerce' );

        // Weight must be present
        $package_weight = $this->get_package_weight( $package );

        WP_Log::debug( __METHOD__, [ 'package weight' => $package_weight ], 'relais-colis-woocommerce' );
        if ( is_null( $package_weight ) ) {

            WP_Log::debug( __METHOD__.' - No defined weight for at least one product', [], 'relais-colis-woocommerce' );
            return;
        }

        // Get cart total price
        // $cart_total_price = $package[ 'contents_cost' ];
        $cart_total_price = $package[ 'cart_subtotal' ];


        // Check if price-based pricing is available in the tariff grid
        $shipping_price = WP_Tariff_Grids_DAO::instance()->get_shipping_price( $this->get_database_method_name(), $cart_total_price, 'price' );

        WP_Log::debug( __METHOD__.' - price-based pricing?', [ '$cart_total_price' => $cart_total_price, '$shipping_price' => $shipping_price ], 'relais-colis-woocommerce' );

        if ( is_null( $shipping_price ) ) {

            // Switch to weight-based pricing
            $shipping_price = WP_Tariff_Grids_DAO::instance()->get_shipping_price( $this->get_database_method_name(), $package_weight, 'weight' );
            WP_Log::debug( __METHOD__.' - weight-based pricing?', [ '$package_weight' => $package_weight, '$shipping_price' => $shipping_price ], 'relais-colis-woocommerce' );
        }
        
        // If no matching tariff is found, do not display this shipping method
        if ( is_null( $shipping_price ) ) {

            WP_Log::debug( __METHOD__.' - No pricing found', [], 'relais-colis-woocommerce' );
            return;
        }

        // Check if price-based threshold is available in the tariff grid
        $threshold = WP_Tariff_Grids_DAO::instance()->get_shipping_threshold( $this->get_database_method_name(), 'price' );

        if ( is_null( $threshold ) ) {

            // Switch to weight-based threshold
            $threshold = WP_Tariff_Grids_DAO::instance()->get_shipping_threshold( $this->get_database_method_name(), 'weight' );
        }

        // Check if cart total price is greater than the threshold
        if ( !is_null( $threshold ) && $cart_total_price > $threshold ) {

            // Set rate
            $rate = [
                'id' => $this->id, // Unique identifier for this shipping rate
                'label' => $this->title, // Label displayed in the checkout
                'cost' => 0, // Shipping cost retrieved from the tariff grid
                'calc_tax' => 'per_order', // Tax calculation mode
            ];
        }
        else {

            // Set rate
            $rate = [
                'id' => $this->id, // Unique identifier for this shipping rate
                'label' => $this->title, // Label displayed in the checkout
                'cost' => floatval( $shipping_price ), // Shipping cost retrieved from the tariff grid
                'calc_tax' => 'per_order', // Tax calculation mode
            ];
        }
        WP_Log::debug( __METHOD__.' - Rate calculated', [ 'rate' => $rate ], 'relais-colis-woocommerce' );

        $this->add_rate( $rate );
    }

    /**
     * Template Method used to convert this method id into DB used method name
     * @return string
     */
    abstract protected function get_database_method_name();
}
