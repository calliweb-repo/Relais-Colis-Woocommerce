<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use SimplePie\Exception;
use WC_Customer;

/**
 * Class WC_Customer_Orders_Manager
 *
 * This class is responsible for managing WooCommerce orders infos in customer account
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Customer_Orders_Manager {

    // Use Trait Singleton
    use Singleton;

    ////////////////////////////////// TEST //////////////////////////////////
    //private static $hook_list = array();
    ////////////////////////////////// END TEST //////////////////////////////////

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        ////////////////////////////////// TEST //////////////////////////////////
        // Show hooks
        /*add_action( 'all', function ( $hook_name ) {
            if ( (strpos($hook_name, 'woocommerce-') !== false) ) {

                if ( !in_array( $hook_name, self::$hook_list ) ) {

                    //echo "<p style='color: red;'>HOOK WooCommerce exécuté : $hook_name</p>";
                    WP_Log::debug( __METHOD__."🔥 Hook détecté", [ '$hook_name' => $hook_name ], 'relais-colis-woocommerce' );
                }
                self::$hook_list[] = $hook_name;
            }
        } );*/
        ////////////////////////////////// END TEST //////////////////////////////////

        // Display infos on customer order details page, in My account -> Orders -> Order page
        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'action_woocommerce_order_details_after_order_table' ), 10, 1 );

        // And on thank you page
        add_action( 'woocommerce_thankyou', array( $this, 'action_woocommerce_thankyou' ), 10, 1 );

        // Register scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
    }

    /**
     * Enqueue needed scripts
     */
    public function action_wp_enqueue_scripts() {

        // Check if we are in the WordPress admin area
        if ( !is_account_page() && !is_wc_endpoint_url( 'view-order' ) && !is_wc_endpoint_url( 'order-received' ) ) {
            return;
        }

        // CSS
        wp_enqueue_style( WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS.'_css', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/relais-colis.css', array(), '1.0', 'all' );
    }

    /**
     * Display infos n thank you page
     * @param $order_id
     */
    public function action_woocommerce_thankyou( $order_id ) {

        if ( ! $order_id ) {
            return;
        }

        // Get order
        $wc_order = wc_get_order( $order_id );
        if ( ! $wc_order ) {
            return;
        }



        // Set original customer info
        $rc_customer_shipping_address = $wc_order->get_meta( 'rc_customer_shipping_address' );


        
        if ( !empty( $rc_customer_shipping_address ) ) {



            WP_Log::debug( __METHOD__.' - Customer info present in transient.', [ '$rc_customer_shipping_address' => $rc_customer_shipping_address ], 'relais-colis-woocommerce' );

            if ( !empty( $rc_customer_shipping_address ) ) {

                
                if ($wc_order->get_customer_id() !== 0) {
                    try {


                        $customer = new WC_Customer( $wc_order->get_customer_id() );

    
                        foreach ( $rc_customer_shipping_address as $key => $value ) {

                            // Use setters where available.
                            if ( is_callable( array( $customer, "set_{$key}" ) ) ) {

                                if ( $key === 'billing_email' && ! is_email( $value ) ) {
                                    continue;
                                }
                                $customer->{"set_{$key}"}( $value );
                            }
                        }
                        $customer->save();

                    } catch (Exception $exception ) {

                    }
                }
            }

            // Delete transient after usage
            //delete_transient( 'rc_customer_shipping_address' );
            $wc_order->delete_meta_data( 'rc_customer_shipping_address' );
            $wc_order->save();
        }


        WC_Order_Shipping_Infos_Manager::instance()->render_shipping_infos( $wc_order );
    }

    /**
     * Display infos on customer order details page, in My account -> Orders -> Order page
     * @param $wc_order
     * @return void
     */
    public function action_woocommerce_order_details_after_order_table( $wc_order ) {

        // Check if we are in the WordPress admin area
        if ( !is_account_page() || !is_wc_endpoint_url( 'view-order' ) ) {
            return;
        }

        WC_Order_Shipping_Infos_Manager::instance()->render_shipping_infos( $wc_order );
    }
}

