<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WC_RC_Services_Manager;
use RelaisColisWoocommerce\WC_WooCommerce_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

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
    private static $hook_list = array();
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
            if ( (strpos($hook_name, 'woocommerce_order_details_after_order_table') !== false) ) {

                if ( !in_array( $hook_name, self::$hook_list ) ) {

                    echo "<p style='color: red;'>HOOK WooCommerce exécuté : $hook_name</p>";
                    WP_Log::debug( __METHOD__."🔥 Hook détecté", [ '$hook_name' => $hook_name ], 'relais-colis-woocommerce' );
                }
                self::$hook_list[] = $hook_name;
            }
        } );*/
        ////////////////////////////////// END TEST //////////////////////////////////

        // Display infos on customer order details page, in My account -> Orders -> Order page
        add_action( 'woocommerce_order_details_after_order_table', array( $this, 'woocommerce_order_details_after_order_table' ), 10, 1 );

        // Register scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
    }

    /**
     * Enqueue needed scripts
     */
    public function action_wp_enqueue_scripts() {

        // Check if we are in the WordPress admin area
        if ( !is_account_page() || !is_wc_endpoint_url( 'view-order' ) ) {
            return;
        }

        // CSS
        wp_enqueue_style( WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS.'_css', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/relais-colis.css', array(), '1.0', 'all' );
    }

    /**
     * Display infos on customer order details page, in My account -> Orders -> Order page
     * @param $wc_order
     * @return void
     */
    public function woocommerce_order_details_after_order_table( $wc_order ) {

        // Check if we are in the WordPress admin area
        if ( !is_account_page() || !is_wc_endpoint_url( 'view-order' ) ) {
            return;
        }

        WC_Order_Shipping_Infos_Manager::instance()->render_shipping_infos( $wc_order );
    }
}
