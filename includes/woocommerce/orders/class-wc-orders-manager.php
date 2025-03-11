<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WC_RC_Services_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Helper;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Order;

/**
 * WooCommerce Manager.
 *
 * @since     1.0.0
 */
class WC_Orders_Manager {

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
            if ( (strpos($hook_name, 'restrict_') !== false) && (strpos($hook_name, 'manage_') !== false)) {

                if ( !in_array( $hook_name, self::$hook_list ) ) {

                    echo "<p style='color: red;'>HOOK WooCommerce exécuté : $hook_name</p>";
                    WP_Log::debug( __METHOD__."🔥 Hook détecté", [ '$hook_name' => $hook_name ], 'relais-colis-woocommerce' );
                }
                self::$hook_list[] = $hook_name;
            }
        } );*/
        ////////////////////////////////// END TEST //////////////////////////////////

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
        add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'action_woocommerce_store_api_checkout_update_order_meta' ), 10, 1 );

        // Use woocommerce_admin_order_data_after_shipping_address to display RC shipping info
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'action_woocommerce_admin_order_data_after_shipping_address' ), 10, 1 );
    }

    /**
     * Because this hook is called before woocommerce_after_order_itemmeta,
     * It is used to remove WC_MultiShipping buttons in order
     * Do it in hook woocommerce_admin_order_data_after_order_details, called just before woocommerce_after_order_itemmeta, and with order initialized
     * @return void
     */
    public function action_woocommerce_admin_order_data_after_order_details( \WC_Order $wc_order ) {

        // Check if we are in the WordPress admin area
        if ( !is_admin()
            || !( isset( $_GET[ 'page' ] ) && ( $_GET[ 'page' ] === 'wc-orders' ) )
            || !isset( $_GET[ 'id' ] )
            || !( isset( $_GET[ 'action' ] ) && ( $_GET[ 'action' ] === 'edit' ) ) ) {

            return;
        }

        WP_Log::debug( __METHOD__, [ '$wc_order' => $wc_order ], 'relais-colis-woocommerce' );

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
        if ( !is_admin()
            || !( isset( $_GET[ 'page' ] ) && ( $_GET[ 'page' ] === 'wc-orders' ) )
            || !isset( $_GET[ 'id' ] )
            || !( isset( $_GET[ 'action' ] ) && ( $_GET[ 'action' ] === 'edit' ) ) ) {

            return;
        }
        WP_Log::debug( __METHOD__, [ 'wc_order' => $wc_order ], 'relais-colis-woocommerce' );

        // Check if the shipping method is "Relais Colis"
        $rc_shipping_method = WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method( $wc_order );
        if ( $rc_shipping_method !== false ) {

            // Treated infos:
            // - Choose Relais Colis    -> rc_relay_data
            // - Choose Home options    -> rc_services
            // - Choose Home+ options   -> rc_service_infos
            $rc_shipping_infos_html = null;
            switch ( $rc_shipping_method ) {
                case WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID:

                    // Check if relay_data
                    $rc_relay_data = $wc_order->get_meta( 'rc_relay_data' );
                    WP_Log::debug( __METHOD__, [ '$rc_relay_data' => $rc_relay_data ], 'relais-colis-woocommerce' );
                    if ( !empty( $rc_relay_data ) ) {

                        $rc_shipping_infos_html = '
                            <h3>'.__( 'Relais Colis - Opening hours', 'relais-colis-woocommerce' ).'</h3>
                            <table class="rc-delivery-hours">
                                <tbody>
                                    <tr><td>'.__( 'Monday', 'relais-colis-woocommerce' ).'</td><td>'.$rc_relay_data[ 'Horairelundimatin' ].' / '.$rc_relay_data[ 'Horairelundiapm' ].'</td></tr>
                                    <tr><td>'.__( 'Tuesday', 'relais-colis-woocommerce' ).'</td><td>'.$rc_relay_data[ 'Horairemardimatin' ].' / '.$rc_relay_data[ 'Horairemardiapm' ].'</td></tr>
                                    <tr><td>'.__( 'Wednesday', 'relais-colis-woocommerce' ).'</td><td>'.$rc_relay_data[ 'Horairemercredimatin' ].' / '.$rc_relay_data[ 'Horairemercrediapm' ].'</td></tr>
                                    <tr><td>'.__( 'Thursday', 'relais-colis-woocommerce' ).'</td><td>'.$rc_relay_data[ 'Horairejeudimatin' ].' / '.$rc_relay_data[ 'Horairejeudiapm' ].'</td></tr>
                                    <tr><td>'.__( 'Friday', 'relais-colis-woocommerce' ).'</td><td>'.$rc_relay_data[ 'Horairevendredimatin' ].' / '.$rc_relay_data[ 'Horairevendrediapm' ].'</td></tr>
                                    <tr><td>'.__( 'Saturday', 'relais-colis-woocommerce' ).'</td><td>'.$rc_relay_data[ 'Horairesamedimatin' ].' / '.$rc_relay_data[ 'Horairesamediapm' ].'</td></tr>
                                    <tr><td>'.__( 'Sunday', 'relais-colis-woocommerce' ).'</td><td>'.$rc_relay_data[ 'Horairedimanchematin' ].' / '.$rc_relay_data[ 'Horairedimancheapm' ].'</td></tr>
                                </tbody>
                            </table>
                        ';

                    }
                    break;
                case WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID:

                    // Check if rc_services
                    $rc_services = $wc_order->get_meta( 'rc_services' );
                    WP_Log::debug( __METHOD__, [ '$rc_services' => $rc_services ], 'relais-colis-woocommerce' );
                    if ( !empty( $rc_services ) ) {

                        $rc_shipping_infos_html = '<h3>'.__( 'Services', 'relais-colis-woocommerce' ).'</h3>';
                        //    [$session_rc_service_fees] => Array
                        //        (
                        //            [0] => rc_service_two_person_delivery
                        //            [1] => rc_service_two_person_delivery
                        //        )
                        //

                        foreach ( $rc_services as $rc_service ) {

                            // Service key must start with WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                            if ( strpos( $rc_service, WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) !== 0 ) continue;

                            // Extract slug
                            // Start after prefix WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                            $slug = substr( $rc_service, strlen( WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) );

                            $rc_shipping_infos_html .= '<br>'.WC_RC_Services_Manager::instance()->get_fixed_service_name( $slug );
                        }
                    }

                    break;
                case WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID:

                    // Check if rc_services
                    $rc_services = $wc_order->get_meta( 'rc_services' );
                    WP_Log::debug( __METHOD__, [ '$rc_services' => $rc_services ], 'relais-colis-woocommerce' );

                    // Check if rc_service_infos
                    $rc_service_infos = $wc_order->get_meta( 'rc_service_infos' );
                    WP_Log::debug( __METHOD__, [ '$rc_service_infos' => $rc_service_infos ], 'relais-colis-woocommerce' );

                    // Title
                    if ( !empty( $rc_services ) && !empty( $rc_service_infos ) ) {

                        $rc_shipping_infos_html = '<h3>'.__( 'Services', 'relais-colis-woocommerce' ).'</h3>';
                    }

                    // Services content
                    if ( !empty( $rc_services ) ) {

                        //    [$session_rc_service_fees] => Array
                        //        (
                        //            [0] => rc_service_two_person_delivery
                        //            [1] => rc_service_two_person_delivery
                        //        )
                        //
                        foreach ( $rc_services as $rc_service ) {

                            // Service key must start with WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                            if ( strpos( $rc_service, WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) !== 0 ) continue;

                            // Extract slug
                            // Start after prefix WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                            $slug = substr( $rc_service, strlen( WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) );

                            $rc_shipping_infos_html .= '<br>'.WC_RC_Services_Manager::instance()->get_fixed_service_name( $slug );
                        }
                    }

                    // Service infos content
                    if ( !empty( $rc_service_infos ) && is_array( $rc_service_infos ) ) {

                        $rc_shipping_infos_html = '<h3>'.__( 'Relais Colis - Additional infos', 'relais-colis-woocommerce' ).'</h3>';

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
                        $homeplus_addon_infos_fields = WC_RC_Services_Manager::instance()->get_homeplus_addon_infos_fields();
                        foreach ( $homeplus_addon_infos_fields as $homeplus_addon_infos_slug => $homeplus_addon_infos_field ) {

                            if ( !array_key_exists( WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$homeplus_addon_infos_slug, $rc_service_infos ) ) continue;

                            switch ( $homeplus_addon_infos_field[ 'type' ] ) {
                                case 'text':
                                    $rc_shipping_infos_html .= '<br>'.$homeplus_addon_infos_field[ 'label' ].': '.$rc_service_infos[ WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$homeplus_addon_infos_slug ];
                                    break;
                                case 'textarea':
                                    $rc_shipping_infos_html .= '<br>'.$homeplus_addon_infos_field[ 'label' ].':<br>'.$rc_service_infos[ WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$homeplus_addon_infos_slug ];
                                    break;
                                case 'select':
                                    $rc_shipping_infos_html .= '<br>'.$homeplus_addon_infos_field[ 'label' ].': '.$homeplus_addon_infos_field[ 'options' ][ ''.$rc_service_infos[ WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$homeplus_addon_infos_slug ] ];
                                    break;
                                case 'checkbox':
                                    $rc_shipping_infos_html .= '<br>'.$homeplus_addon_infos_field[ 'label' ].': '.( $rc_service_infos[ WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$homeplus_addon_infos_slug ] === 1 ? __( 'Yes', 'relais-colis-woocommerce' ) : __( 'No', 'relais-colis-woocommerce' ) );
                                    break;
                                default:
                                    // Does nothing
                                    break;
                            }
                        }
                    }

                    break;
                default:
                    // Does nothing
                    break;
            }

            $html_content = '
                <div class="rc-shipping-info">
                    <h3>'.__( 'Relais Colis - Informations', 'relais-colis-woocommerce' ).'</h3>
                    <p>'.WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method_name( $rc_shipping_method ).'</p>
                    '.( !is_null( $rc_shipping_infos_html ) ? $rc_shipping_infos_html : '' ).'
                </div>';

            echo $html_content;
        }
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
            $wc_order->update_meta_data( WC_RC_Shipping_Constants::OPTION_RC_SHIPPING_METHOD, $rc_shipping_method );

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
        if ( WC()->session->__isset( 'rc_services' ) ) {

            $session_rc_service_fees = WC()->session->get( 'rc_services' );
            WP_Log::debug( __METHOD__.' - Checkout Block/Store API updates an order meta data.', [ '$session_rc_service_fees' => $session_rc_service_fees ], 'relais-colis-woocommerce' );

            // Fees are already taken into account, added to the cart during checkout

            // Update WooCommerce order meta data
            $wc_order->update_meta_data( 'rc_services', $session_rc_service_fees );

            // Save order
            $wc_order->save();
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
        if ( WC()->session->__isset( 'rc_service_infos' ) ) {

            $session_rc_service_infos = WC()->session->get( 'rc_service_infos' );
            WP_Log::debug( __METHOD__.' - Checkout Block/Store API updates an order meta data.', [ '$session_rc_service_infos' => $session_rc_service_infos ], 'relais-colis-woocommerce' );

            // Update WooCommerce order meta data
            $wc_order->update_meta_data( 'rc_service_infos', $session_rc_service_infos );

            // Save order
            $wc_order->save();
        }

        // Relay data
        if ( WC()->session->__isset( 'rc_relay_data' ) ) {

            $session_rc_relay_data = WC()->session->get( 'rc_relay_data' );
            WP_Log::debug( __METHOD__.' - Checkout Block/Store API updates an order meta data.', [ '$session_rc_relay_data' => $session_rc_relay_data ], 'relais-colis-woocommerce' );

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
            $wc_order->update_meta_data( 'rc_relay_data', $session_rc_relay_data );

            // Save order
            $wc_order->save();
        }
    }
}
