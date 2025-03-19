<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WC_RC_Services_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Order;

/**
 * Class WC_Orders_Manager
 *
 * This class is responsible for managing WooCommerce orders and their interactions with the Relais Colis shipping system.
 * It handles shipping method validation, order metadata updates, and compatibility with both HPOS (High-Performance Order Storage)
 * and legacy WooCommerce order storage.
 *
 * ## Key Features:
 * - **Shipping Method Management**: Ensures compatibility with Relais Colis shipping methods (`relay`, `home`, `home+`).
 * - **WooCommerce Hooks Integration**: Registers essential WooCommerce actions for order processing.
 * - **HPOS & Legacy Support**: Detects and handles both HPOS-based and traditional CPT-based orders.
 * - **Admin UI Enhancements**: Modifies the WooCommerce order admin panel for better visibility of Relais Colis shipping details.
 * - **Shipping Services & Metadata Handling**: Stores and retrieves additional shipping data (e.g., service fees, relay details).
 * - **Multilingual Compatibility**: Uses WooCommerce hooks to ensure seamless internationalization.
 *
 * ## Data Structure
 * Orders processed via this class store additional metadata related to Relais Colis:
 *
 * ```php
 * // Example metadata stored within WooCommerce orders
 * [
 *     'rc_order_status' => 'status_rc_livraison_en_cours',
 *     'rc_shipping_method' => 'wc_rc_shipping_method_relay',
 *     'rc_services' => ['rc_service_two_person_delivery'],
 *     'rc_service_infos' => [
 *         'rc_service_digicode' => '1315',
 *         'rc_service_floor' => '2',
 *         'rc_service_type_habitat' => 'apartment',
 *         'rc_service_elevator' => '1',
 *         'rc_service_informations_complementaires' => 'Leave at reception.'
 *     ],
 *     'rc_relay_data' => [
 *         'Nomrelais' => 'Point Relais Paris',
 *         'Geocoadresse' => '123 Avenue XYZ',
 *         'Postalcode' => '75001',
 *         'Commune' => 'Paris'
 *     ]
 * ]
 * ```
 *
 * ## Methods Overview:
 * - `init()`: Initializes all WooCommerce hooks for order handling.
 * - `is_order_page()`: Determines whether the current admin page is a WooCommerce order edit page.
 * - `action_woocommerce_admin_order_data_after_order_details()`: Removes conflicting multi-shipping buttons in the order edit page.
 * - `action_woocommerce_admin_order_data_after_shipping_address()`: Displays additional shipping details in the admin panel.
 * - `action_woocommerce_store_api_checkout_update_order_meta()`: Handles metadata updates when an order is processed via WooCommerce's Checkout Block API.
 *
 * ## WooCommerce Hooks Used:
 * - `woocommerce_admin_order_data_after_order_details`: Injects modifications in the order details panel.
 * - `woocommerce_after_order_details`: Alternative for legacy WooCommerce orders.
 * - `woocommerce_store_api_checkout_update_order_meta`: Updates order meta when checkout is completed.
 * - `woocommerce_admin_order_data_after_shipping_address`: Adds additional shipping information below the shipping address in the admin panel.
 *
 * ##  Considerations:
 * - **WooCommerce HPOS Compatibility**: Ensures full compatibility with HPOS-enabled WooCommerce stores.
 * - **Performance Optimization**: Uses `remove_filters_with_method_name()` to prevent conflicts with third-party plugins.
 * - **Security**: Ensures that only authorized users can modify order metadata.
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Orders_Manager {

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

        // WC_MultiShipping marketing conflict !
        // May disable WC_MultiShipping buttons in order
        // Remove -> WCMultiShipping\inc\admin\classes\abstract_classes abstract_helper - add_action( 'woocommerce_after_order_itemmeta', [ $page, 'add_admin_shipping_method_selection' ], 10, 2 );
        // Do it in hook woocommerce_admin_order_data_after_order_details, called just before woocommerce_after_order_itemmeta, and with order initialized
        // HPOS-based orders
        add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'action_woocommerce_admin_order_data_after_order_details' ), 10, 1 );
        // Legacy – for CPT-based orders
        add_action( 'woocommerce_after_order_details', array( $this, 'action_woocommerce_admin_order_data_after_order_details' ), 10, 1 );

        /**
         * Fires when the Checkout Block/Store API updates an order's meta data.
         *
         * This hook gives extensions the chance to add or update meta data on the $order.
         * Throwing an exception from a callback attached to this action will make the Checkout Block render in a warning state, effectively preventing checkout.
         *
         * This is similar to existing core hook woocommerce_checkout_update_order_meta.
         * We're using a new action:
         * - To keep the interface focused (only pass $order, not passing request data).
         * - This also explicitly indicates these orders are from checkout block/StoreAPI.
         *
         * @param \WC_Order $order Order object.
         * @see https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/3686
         *
         * @since 7.2.0
         *
         */
        // New checkout FSE
        add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'action_woocommerce_store_api_checkout_update_order_meta' ), 10, 1 );
        // Compatibility with old shotcode checkout
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'action_woocommerce_checkout_update_order_meta' ), 10, 2 );

        // Use woocommerce_admin_order_data_after_shipping_address to display RC shipping info
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'action_woocommerce_admin_order_data_after_shipping_address' ), 10, 1 );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
    }

    /***
     * Adding CSS and JS into header
     * Default add assets/admin.css and assets/admin.js
     */
    public function action_admin_enqueue_scripts() {

        // Check if we are in the WordPress admin area
        if ( !$this->is_order_page() ) {
            return;
        }

        // CSS
        wp_enqueue_style(WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS.'_css', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/relais-colis.css', array(), '1.0', 'all');
    }

    /**
     * Check if current page is an order edit admin page, in HPOS et legacy mode
     * @return bool
     */
    private function is_order_page() {

        if (
            !is_admin() || (
                // HPOS Mode: Verifies "wc-orders" page with ID and edit action
                ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-orders' && isset( $_GET['id'] ) && isset( $_GET['action'] ) && $_GET['action'] === 'edit' )

                // Legacy Mode: Verifies classic WooCommerce order edit page
                || ( isset( $_GET['post'] ) && isset( $_GET['action'] ) && $_GET['action'] === 'edit' )
            ) === false
        ) {
            return false;
        }
        return true;
    }

    /**
     * Because this hook is called before woocommerce_after_order_itemmeta,
     * It is used to remove WC_MultiShipping buttons in order
     * Do it in hook woocommerce_admin_order_data_after_order_details, called just before woocommerce_after_order_itemmeta, and with order initialized
     * @return void
     */
    public function action_woocommerce_admin_order_data_after_order_details( WC_Order $wc_order ) {

        WP_Log::debug( __METHOD__, [ '$wc_order' => $wc_order ], 'relais-colis-woocommerce' );

        // Check if we are in the WordPress admin area
        if ( !$this->is_order_page() ) {
            return;
        }
        WP_Log::debug( __METHOD__.' - Page validated', [ '$wc_order' => $wc_order ], 'relais-colis-woocommerce' );

        // Check if the shipping method is "Relais Colis"
        if ( WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $wc_order ) !== false ) {

            // Remove -> WCMultiShipping\inc\admin\classes\abstract_classes abstract_helper - add_action( 'woocommerce_after_order_itemmeta', [ $page, 'add_admin_shipping_method_selection' ], 10, 2 );
            WP_Helper::remove_filters_with_method_name( 'woocommerce_after_order_itemmeta', 'add_admin_shipping_method_selection', 10 );
        }
    }

    /**
     * Use woocommerce_admin_order_data_after_shipping_address to display RC shipping info
     * Display under the Shipping block
     * @param $wc_order
     * @return void
     */
    public function action_woocommerce_admin_order_data_after_shipping_address( WC_Order $wc_order ) {

        // Check if we are in the WordPress admin area
        if ( !$this->is_order_page() ) {
            return;
        }

        WC_Order_Shipping_Infos_Manager::instance()->render_shipping_infos( $wc_order );
    }

    /**
     * Fires when the Checkout Block/Store API updates an order's meta data.
     * Old checkout with shortcode
     * @param $order_id
     * @param $data
     * @return void
     */
    public function action_woocommerce_checkout_update_order_meta( $order_id, $data ) {

        // Get WC order
        $order = wc_get_order( $order_id );
        $this->action_woocommerce_store_api_checkout_update_order_meta( $order );
    }

    /**
     * Fires when the Checkout Block/Store API updates an order's meta data.
     * @param $wc_order
     * @return void
     */
    public function action_woocommerce_store_api_checkout_update_order_meta( WC_Order $wc_order ) {

        WP_Log::debug( __METHOD__.' - Checkout Block/Store API updates an order meta data.', [ 'wc_order' => $wc_order ], 'relais-colis-woocommerce' );

        // Check if the shipping method is "Relais Colis"
        $rc_shipping_method = WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $wc_order );
        if ( $rc_shipping_method !== false ) {

            // Store a meta data for shipping method to ease ordering by RC shipping method
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SHIPPING_METHOD, $rc_shipping_method );

            // Init order RC state
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_TO_BE_DISTRIBUTED );

            // Save order
            $wc_order->save();
        }

        // Check session data
        //  [$session_rc_service_fees] => Array
        //        (
        //            [0] => rc_service_two_person_delivery
        //        )
        // [$session_rc_service_fees] => Array
        //        (
        //            [0] => rc_service_oversized_items
        //            [1] => rc_service_removal_old_equipment
        //        )
        if ( WC()->session->__isset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES ) ) {

            $session_rc_service_fees = WC()->session->get( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES );
            WP_Log::debug( __METHOD__.' - Checkout Block/Store API updates an order meta data.', [ '$session_rc_service_fees' => $session_rc_service_fees ], 'relais-colis-woocommerce' );

            // Fees are already taken into account, added to the cart during checkout

            // Update WooCommerce order meta data
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES, $session_rc_service_fees );

            // Save order
            $wc_order->save();

            // Clear session
            WC()->session->__unset(WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES);
        }

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
        if ( WC()->session->__isset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS ) ) {

            $session_rc_service_infos = WC()->session->get( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS );
            WP_Log::debug( __METHOD__.' - Checkout Block/Store API updates an order meta data.', [ '$session_rc_service_infos' => $session_rc_service_infos ], 'relais-colis-woocommerce' );

            // Update WooCommerce order meta data
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS, $session_rc_service_infos );

            // Save order
            $wc_order->save();

            // Clear session
            WC()->session->__unset(WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS);
        }

        // Relay data
        if ( WC()->session->__isset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA ) ) {

            $session_rc_relay_data = WC()->session->get( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA );
            WP_Log::debug( __METHOD__.' - Checkout Block/Store API updates an order meta data.', [ '$session_rc_relay_data' => $session_rc_relay_data ], 'relais-colis-woocommerce' );

            // Change shipping address (displayed in customer order confirmation, and in order admin)
            /*$wc_order->set_shipping_company( $session_rc_relay_data[ 'Nomrelais' ] );
            $wc_order->set_shipping_address_1( $session_rc_relay_data[ 'Geocoadresse' ] );
            $wc_order->set_shipping_address_2( '' );
            $wc_order->set_shipping_postcode( $session_rc_relay_data[ 'Postalcode' ] );
            $wc_order->set_shipping_city( $session_rc_relay_data[ 'Commune' ] );
            $wc_order->set_shipping_country( 'FR' ); // Countrycode or countryLabel ?*/

            // Supported WooCommerce country codes
            //AF, ZA, AX, AL, DZ, DE, AS, AD, AO, AI, AQ, AG, SA, AR, AM, AW, AU, AT, AZ, BS, BH, BD, BB, PW, BE, BZ, BJ, BM, BT, BY, BO, BA, BW, BR, BN, BG, BF, BI, KH, CM, CA, CV, CL, CN, CX, CY, CO, KM, CG, CD, KP, KR, CR, CI, HR, CU, CW, DK, DJ, DM, EG, AE, EC, ER, ES, EE, SZ, US, ET, FJ, FI, FR, GA, GM, GE, GS, GH, GI, GR, GD, GL, GP, GU, GT, GG, GN, GQ, GW, GY, GF, HT, HN, HK, HU, BV, IM, NF, KY, CC, CK, FK, FO, HM, MH, UM, SB, TC, IN, ID, IR, IQ, IE, IS, IL, IT, JM, JP, JE, JO, KZ, KE, KI, KW, KG, RE, LA, LS, LV, LB, LR, LY, LI, LT, LU, MO, MK, MG, MY, MW, MV, ML, MT, MA, MQ, MU, MR, YT, MX, FM, MD, MC, MN, ME, MS, MZ, MM, NA, NR, NP, NI, NE, NG, NU, MP, NO, NC, NZ, OM, PK, PA, PG, PY, NL, PE, PH, PN, PL, PF, PT, PR, QA, CF, DO, CZ, RO, GB, RU, RW, BQ, EH, BL, PM, KN, MF, SX, VC, SH, LC, SV, WS, SM, ST, SN, RS, SC, SL, SG, SK, SI, SO, SD, SS, LK, SE, CH, SR, SJ, SY, TW, TJ, TZ, TD, TF, IO, PS, TH, TL, TG, TK, TO, TT, TN, TM, TR, TV, UG, UA, UY, UZ, VU, VA, VE, VN, VG, VI, WF, YE, ZM, ZW

            // Update WooCommerce order meta data
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA, $session_rc_relay_data );

            // Save order
            $wc_order->save();

            // Clear session
            WC()->session->__unset(WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA);
        }

        // Auto distribution of packages if C2C mode
        WP_Log::debug( __METHOD__.' - Auto distribution of packages if C2C mode?', [ 'is_c2c_interaction_mode?' => WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode()?'true':'false' ], 'relais-colis-woocommerce' );
        if ( WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode() ) {

            // Distribution strategy is : try and put as max as possible items in each package
            WC_Order_Packages_Manager::instance()->auto_distribute_packages( $wc_order->get_id() );

        }

    }
}
