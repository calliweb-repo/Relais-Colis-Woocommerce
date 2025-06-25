<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Orders_Rel_Shipping_Labels_DAO;
use RelaisColisWoocommerce\RCAPI\WP_RC_B2C_Home_Place_Advertisement;
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

        // Sync rc_services_rel_products with existing orders
        add_action( 'deleted_post', array( $this, 'action_deleted_post' ) );
    }

    /**
     * Sync rc_services_rel_products with existing orders
     * @param $post_id
     * @return void
     */
    public function action_deleted_post( $post_id ) {

        WP_Log::debug( __METHOD__, [ '$post_id' => $post_id ], 'relais-colis-woocommerce' );

        WP_Orders_Rel_Shipping_Labels_DAO::instance()->delete_shipping_labels_for_order_and_orphans( $post_id );
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
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
     * Build services RC params array from relay_data
     * @param WC_Order $wc_order
     * @return string
     */
    public function build_rc_prestations_param( WC_Order $wc_order ) {

        $rc_services = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES );
        WP_Log::debug( __METHOD__, [ '$rc_services' => $rc_services ], 'relais-colis-woocommerce' );

        if ( !empty( $rc_services ) && is_array( $rc_services ) ) {

            //          '1' => 'cpSchedule',
            //          '3' => 'cpDeliveryOnTheFloor',
            //          '4' => 'cpDeliveryAtTwo',
            //          '5' => 'cpTurnOnHomeAppliance',
            //          '6' => 'cpMountFurniture',
            //          '7' => 'cpNonStandard',
            //          '8' => 'cpUnpacking',
            //           '9' => 'cpEvacuationPackaging',
            //          '10' => 'cpRecovery',
            //          '11' => 'cpDeliveryDesiredRoom',
            //           '18' => 'cpDeliveryEco',
            $prestations = [];

            foreach ( $rc_services as $rc_service ) {

                // Service key must start with WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                if ( strpos( $rc_service, WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) !== 0 ) continue;

                // Extract slug
                // Start after prefix WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                $slug = substr( $rc_service, strlen( WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) );

                switch ( $slug ) {

                    case WC_RC_Services_Manager::APPOINTMENT_SCHEDULING:
                        $prestations[] = '1';
                        break;
                    case WC_RC_Services_Manager::DELIVERY_TO_FLOOR:
                        $prestations[] = '3';
                        break;
                    case WC_RC_Services_Manager::TWO_PERSON_DELIVERY:
                        $prestations[] = '4';
                        break;
                    case WC_RC_Services_Manager::SETUP_LARGE_APPLIANCES:
                        $prestations[] = '5';
                        break;
                    case WC_RC_Services_Manager::QUICK_ASSEMBLY:
                        $prestations[] = '6';
                        break;
                    case WC_RC_Services_Manager::OVERSIZED_ITEMS:
                        $prestations[] = '7';
                        break;
                    case WC_RC_Services_Manager::PRODUCT_UNPACKING:
                        $prestations[] = '8';
                        break;
                    case WC_RC_Services_Manager::PACKAGING_REMOVAL:
                        $prestations[] = '9';
                        break;
                    case WC_RC_Services_Manager::REMOVAL_OLD_EQUIPMENT:
                        $prestations[] = '10';
                        break;
                    case WC_RC_Services_Manager::DELIVERY_DESIRED_ROOM:
                        $prestations[] = '11';
                        break;
                    case WC_RC_Services_Manager::CURBSIDE_DELIVERY:
                        $prestations[] = '18';
                        break;
                }
            }
            if ( !empty( $prestations ) ) {

                $prestations = array_map('intval', $prestations);
                return $prestations;
            }
        }
        return '';
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

            // Save customer info
            $customer_shipping_address = array(
                'shipping_address_1' => WC()->customer->get_shipping_address_1(),
                'shipping_address_2' => WC()->customer->get_shipping_address_2(),
                'shipping_postcode' => WC()->customer->get_shipping_postcode(),
                'shipping_city' => WC()->customer->get_shipping_city(),
                'shipping_company' => WC()->customer->get_shipping_company(),
                'shipping_country' => WC()->customer->get_shipping_country(),
            );

            
            // Sauvegarder également dans les métadonnées de la commande
            $wc_order->update_meta_data( 'rc_customer_shipping_address', $customer_shipping_address );
            WP_Log::debug( __METHOD__.' - Customer info set in transient.', [ 'set_transient' => $customer_shipping_address ], 'relais-colis-woocommerce' );


            // Change shipping address (displayed in customer order confirmation, and in order admin)
            $wc_order->set_shipping_company( $session_rc_relay_data[ 'Nomrelais' ] );
            $wc_order->set_shipping_address_1( $session_rc_relay_data[ 'Geocoadresse' ] );
            $wc_order->set_shipping_address_2( '' );
            $wc_order->set_shipping_postcode( $session_rc_relay_data[ 'Postalcode' ] );
            $wc_order->set_shipping_city( $session_rc_relay_data[ 'Commune' ] );
            $wc_order->set_shipping_country( 'FR' ); // Countrycode or countryLabel ?

            // Supported WooCommerce country codes
            //AF, ZA, AX, AL, DZ, DE, AS, AD, AO, AI, AQ, AG, SA, AR, AM, AW, AU, AT, AZ, BS, BH, BD, BB, PW, BE, BZ, BJ, BM, BT, BY, BO, BA, BW, BR, BN, BG, BF, BI, KH, CM, CA, CV, CL, CN, CX, CY, CO, KM, CG, CD, KP, KR, CR, CI, HR, CU, CW, DK, DJ, DM, EG, AE, EC, ER, ES, EE, SZ, US, ET, FJ, FI, FR, GA, GM, GE, GS, GH, GI, GR, GD, GL, GP, GU, GT, GG, GN, GQ, GW, GY, GF, HT, HN, HK, HU, BV, IM, NF, KY, CC, CK, FK, FO, HM, MH, UM, SB, TC, IN, ID, IR, IQ, IE, IS, IL, IT, JM, JP, JE, JO, KZ, KE, KI, KW, KG, RE, LA, LS, LV, LB, LR, LY, LI, LT, LU, MO, MK, MG, MY, MW, MV, ML, MT, MA, MQ, MU, MR, YT, MX, FM, MD, MC, MN, ME, MS, MZ, MM, NA, NR, NP, NI, NE, NG, NU, MP, NO, NC, NZ, OM, PK, PA, PG, PY, NL, PE, PH, PN, PL, PF, PT, PR, QA, CF, DO, CZ, RO, GB, RU, RW, BQ, EH, BL, PM, KN, MF, SX, VC, SH, LC, SV, WS, SM, ST, SN, RS, SC, SL, SG, SK, SI, SO, SD, SS, LK, SE, CH, SR, SJ, SY, TW, TJ, TZ, TD, TF, IO, PS, TH, TL, TG, TK, TO, TT, TN, TM, TR, TV, UG, UA, UY, UZ, VU, VA, VE, VN, VG, VI, WF, YE, ZM, ZW

            // Update WooCommerce order meta data
            //            [IconeLogo] => logoOuvert.png
            //            [AffichageLien] => OK
            //            [Distance] => 3426
            //            [Xeett] => G2013
            //            [Nomrelais] => CARREFOUR MARKET
            //            [Lon] => 6.52549
            //            [Lat] => 45.486
            //            [Nomdepositaire] => CARREFOUR MARKET
            //            [Geocoadresse] => 49 RUE DES BOULEAUX
            //            [Complementadresse] =>
            //            [Postalcode] => 73600
            //            [Commune] => MOUTIERS
            //            [Countrycode] => FRA
            //            [Urlrelais] => javascript:window.open(\'https://service.relaiscolis.com/tracking/point_relaiscolis.aspx?RelCode=G2013\', \'window\', \'toolbar=no,status=no,menubar=no,scrollbars=auto,resizable=no,width=430,height=457,left=0,top=0\')
            //            [Depositaireetat] => A
            //            [Depositairenom] => CARREFOUR MARKET PROVENCIA
            //            [Photopath] => https://service.relaiscolis.com/PhotosRelais/245925.JPG
            //            [Photoname] => 245925.JPG
            //            [Horairelundimatin] => 08:30-12:00
            //            [Horairelundiapm] => 12:00-19:45
            //            [Horairemardimatin] => 08:30-12:00
            //            [Horairemardiapm] => 12:00-19:45
            //            [Horairemercredimatin] => 08:30-12:00
            //            [Horairemercrediapm] => 12:00-19:45
            //            [Horairejeudimatin] => 08:30-12:00
            //            [Horairejeudiapm] => 12:00-19:45
            //            [Horairevendredimatin] => 08:30-12:00
            //            [Horairevendrediapm] => 12:00-19:45
            //            [Horairesamedimatin] => 08:30-12:00
            //            [Horairesamediapm] => 12:00-19:45
            //            [Horairedimanchematin] => 09:00-12:00
            //            [Horairedimancheapm] => -
            //            [Datecreation] => 25/03/2019
            //            [Datepremiercolis] => 08/05/2020
            //            [Datederniercolis] => 17/03/2020
            //            [Datefermeture] => 17/03/2020
            //            [Agencecode] => G2
            //            [Agencenom] => CHAMBERY
            //            [Agenceadresse1] => 92 RUE JACQUES CARTIER
            //            [Agenceadresse2] =>
            //            [Agencecodepostal] => 73800
            //            [Agenceville] => STE HELENE DU LAC
            //            [Icone] => 1
            //            [Relaismax] => 0
            //            [Relaissmart] => 0
            //            [formattedAddressLine] => 49 RUE DES BOULEAUX
            //            [countryLabel] => France
            //            [countryISO] => FRA
            //            [streetLabel] => 49 RUE DES BOULEAUX
            //            [Info1] =>
            //            [Info2] =>
            //            [Info3] =>
            //            [Info4] =>
            //            [Info5] =>
            //            [Info6] =>
            //            [Info7] =>
            //            [Info8] =>
            //            [Info9] =>
            //            [Info10] =>
            //            [Pseudorvc] => 06483
            //            [MessageConges] =>
            //            [AgenceCountryISO] => FRA
            //            [IsLocker] => 0
            //            [RelaisId] => 106401-06
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
            // WC_Order_Packages_Manager::instance()->auto_distribute_packages( $wc_order->get_id() );
            
            // // Mettre à jour l'état de la commande après la distribution automatique
            // $wc_order->update_meta_data( 
            //     WC_RC_Shipping_Constants::ORDER_META_DATA_RC_STATE, 
            //     WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_DISTRIBUTED 
            // );
            // $wc_order->save();
            
            // WP_Log::debug( __METHOD__.' - Order state updated after auto distribution', [
            //     'order_id' => $wc_order->get_id(),
            //     'new_state' => WC_RC_Shipping_Constants::ORDER_STATE_ITEMS_DISTRIBUTED
            // ], 'relais-colis-woocommerce' );
        }

    }
}
