<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Services_DAO;
use RelaisColisWoocommerce\WC_RC_Services_Manager;
use RelaisColisWoocommerce\WC_WooCommerce_Manager;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Cart;
use WC_Cart_Session;

/**
 * WooCommerce Relais Colis Block Manager for all choose services
 * Abstract is containing code necessary for old and FSE checkout, and home and home+ offers
 *
 * @since     1.0.0
 */
abstract class WC_RC_Choose_Services_Manager {

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // Register scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );

        // Relais Colis REST API used to update WooCommerce wth selected relay
        add_action( 'wp_ajax_update_rc_options', array( $this, 'action_wp_ajax_update_rc_options' ) );
        add_action( 'wp_ajax_nopriv_update_rc_options', array( $this, 'action_wp_ajax_update_rc_options' ) );

        // Relais Colis REST API used to update WooCommerce wth selected relay
        add_action( 'wp_ajax_reset_rc_infos', array( $this, 'action_wp_ajax_reset_rc_infos' ) );
        add_action( 'wp_ajax_nopriv_reset_rc_infos', array( $this, 'action_wp_ajax_reset_rc_infos' ) );

        /*add_action( 'woocommerce_checkout_update_order_review', function() {

            WP_Log::debug( __METHOD__, ["POST"=> $_POST], 'relais-colis-woocommerce');

        } );*/

        // FSE checkout
        if ( WC_WooCommerce_Manager::instance()->is_woocommerce_checkout_page_fse() ) {

            // Copy all fees from session to cart to allow calculate fees and cart refresh
            add_action( 'woocommerce_before_calculate_totals', array( $this, 'action_woocommerce_before_calculate_totals' ), 1, 1 );
        }
        // Old checkout
        else {

            // Triggered by update_checkout JS call
            // Add a custom calculated fee conditionally to cart
            add_action( 'woocommerce_cart_calculate_fees', array( $this, 'action_woocommerce_cart_calculate_fees' ), 999, 1 );
        }
    }

    /**
     * Enqueue needed scripts
     */
    public function action_wp_enqueue_scripts() {

        // Enqueued only in concerned checkout page
        if ( !is_checkout() && !is_cart() ) return;

        // FSE checkout
        if ( WC_WooCommerce_Manager::instance()->is_woocommerce_checkout_page_fse() ) {

            // Load scripts
            WC_RC_Checkout_Scripts_Manager::instance()->load_fse_checkout_scripts();
            
        }
        // Old checkout
        else {

            // Load scripts
            WC_RC_Checkout_Scripts_Manager::instance()->load_old_checkout_scripts();
        }

        // Load scripts
        WC_RC_Checkout_Scripts_Manager::instance()->load_cart_scripts();
    }

    /**
     * FSE checkout
     * Triggered by update_checkout JS call
     * Add a custom calculated fee conditionally to cart
     * @return void
     */
    public function action_woocommerce_before_calculate_totals( $cart ) {

        WP_Log::debug( __METHOD__, [
            'POST' => $_POST,
            'is_admin' => is_admin(),
            'doing_ajax' => defined( 'DOING_AJAX' ) ? DOING_AJAX : 'false',
            'is_checkout' => is_checkout() ? 'true' : 'false',
            'is_rest' => defined( 'REST_REQUEST' ) ? REST_REQUEST : 'false',
        ], 'relais-colis-woocommerce' );

        if ( is_admin() && !defined( 'DOING_AJAX' ) || ( !is_checkout() && ( !WC_WooCommerce_Manager::instance()->is_woocommerce_checkout_page_fse() ) ) ) {

            WP_Log::debug( __METHOD__.' - Abort', [ 'POST' => $_POST ], 'relais-colis-woocommerce' );
            return;
        }

        // Calculate fees
        $this->calculate_fees( $cart );
    }

    /**
     * Triggered by update_checkout JS call
     * Add a custom calculated fee conditionally to cart
     * @return void
     */
    public function action_woocommerce_cart_calculate_fees( $cart ) {

        // Ignore internal call
        if ( is_admin() || !defined( 'DOING_AJAX' ) || !DOING_AJAX ) {

            WP_Log::debug( __METHOD__.' - Ignore internal call', [], 'relais-colis-woocommerce' );
            return;
        }

        // Ignore other than checkout page
        if ( !is_checkout() ) {

            WP_Log::debug( __METHOD__.' - Ignore other than checkout page', [], 'relais-colis-woocommerce' );
            return;
        }

        // Ignore FSE checkout mode
        if ( WC_WooCommerce_Manager::instance()->is_woocommerce_checkout_page_fse() ) {

            WP_Log::debug( __METHOD__.' - Ignore FSE checkout mode', [], 'relais-colis-woocommerce' );
            return;
        }


        // Ignore second hook call
        static $already_ran = false;
        if ($already_ran) {
            WP_Log::debug( __METHOD__.' - Ignore second hook call', [], 'relais-colis-woocommerce' );
            return;
        }
        $already_ran = true;

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10); // Limite à 10 niveaux pour éviter trop de bruit

        WP_Log::debug("Triggering `woocommerce_cart_calculate_fees`",
            [
                'is_admin()'   => is_admin() ? 'true' : 'false',
                'DOING_AJAX'   => defined('DOING_AJAX') && DOING_AJAX ? 'true' : 'false',
                'DOING_CRON'   => defined('DOING_CRON') && DOING_CRON ? 'true' : 'false',
                'IS_ADMIN'     => is_admin() ? 'true' : 'false',
                'IS_CHECKOUT'  => is_checkout() ? 'true' : 'false',
                'POST'         => $_POST, // Attention, peut contenir des données sensibles
                'BACKTRACE'    => array_column($backtrace, 'function') // Affiche seulement les fonctions de la stack
            ],
            'relais-colis-woocommerce'
        );

        // Calculate fees
        $this->calculate_fees( $cart );
    }

    /**
     * AJAX handler
     */
    public function action_wp_ajax_update_rc_options() {

        WP_Log::debug( __METHOD__.' - update_rc_options received', [ 'POST' => $_POST ], 'relais-colis-woocommerce' );

        // Check the nonce
        $nonce_check = check_ajax_referer( 'rc_choose_options', 'nonce', false );
        if ( !$nonce_check ) {
            wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );
        }

        if ( WC()->session->__isset( 'reset_rc_infos' ) ) {

            WC()->session->__unset( 'reset_rc_infos' ); // Fees

        }

        // Session will be updated
        WC()->session->__unset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES ); // Fees
        WC()->session->__unset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS ); // Additional infos
        $rc_services = array();
        $rc_service_infos = array();

        // Check if services sent
        if ( isset( $_POST[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES ] ) && is_array( $_POST[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES ] ) ) {

            $rc_services = $_POST[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES ];
        }
        if ( isset( $_POST[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS ] ) && is_array( $_POST[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS ] ) ) {

            $rc_service_infos = $_POST[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS ];
        }

        // Save sent services in session
        WC()->session->set( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES, $rc_services );
        WC()->session->set( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS, $rc_service_infos );
        WP_Log::debug( __METHOD__.' - Session content', [ 'rc_services' => WC()->session->get( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES ), 'rc_service_infos' => WC()->session->get( 'rc_service_infos' ) ], 'relais-colis-woocommerce' );

        // JSON response
        wp_send_json_success( [ 'message' => 'Services updated successfully', 'selected service fees' => $rc_services, 'selected service infos' => $rc_service_infos ] );
    }

    /**
     * AJAX handler
     */
    public function action_wp_ajax_reset_rc_infos() {

        WP_Log::debug( __METHOD__.' - reset_rc_infos received', [ 'POST' => $_POST ], 'relais-colis-woocommerce' );

        // Check the nonce
        $nonce_check = check_ajax_referer( 'rc_choose_options', 'nonce', false );
        if ( !$nonce_check ) {
            wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );
        }


        // Session will be updated
        if ( WC()->session->__isset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS ) ) {
            WC()->session->__unset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS );
        }
        if ( WC()->session->__isset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES ) ) {
            WC()->session->__unset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES );
        }
        $rc_services = array();
        $rc_service_infos = array();

        // Save sent services in session
        WC()->session->set( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES, $rc_services );
        WC()->session->set( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS, $rc_service_infos );

        // JSON response
        wp_send_json_success( [ 'message' => 'Service infos reset successfully' ] );
    }

    /**
     * Template Method
     * Render list of specific fields to add to form
     * @return mixed
     */
    abstract public function render_choose_specific_services_form();

    /**
     * Template Method
     * Get specific method name, WC_RC_Shipping_Constants METHOD_NAME_RELAIS_COLIS, METHOD_NAME_HOME, METHOD_NAME_HOME_PLUS
     * @return mixed
     */
    abstract public function get_specific_method_name();

    /**
     * Copy all fees from session to cart
     * @param $cart
     * @return void
     */
    protected function calculate_fees( WC_Cart $cart ) {

        // May reset fees
        // Old checkout
        if (isset($_POST['rc_reset_infos']) && $_POST['rc_reset_infos'] == '1') {

            WC()->session->__unset(WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES);
            WC()->session->__unset(WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS);

            WP_Log::debug(__METHOD__ . ' - Old checkout - Reset session due to rc_reset_infos parameter', [], 'relais-colis-woocommerce');
        }

        // FSE checkout
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!empty($_GET['rc_reset_infos'])) {
            //WC()->session->set('rc_selected_services', []); // Vide la session des services
            //WC()->session->set('rc_selected_service_infos', []); // Vide les détails des services

            WC()->session->__unset(WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES);
            WC()->session->__unset(WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS);

            WP_Log::debug(__METHOD__ . ' - FSE checkout - Reset session due to rc_reset_infos parameter', [], 'relais-colis-woocommerce');
        }

        if ( WC()->session->__isset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES ) ) {

            $session_rc_services = WC()->session->get( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES );

            //    [rc_services] => Array
            //        (
            //            [0] => rc_service_two_person_delivery
            //            [1] => Array
            //                (
            //                    [id] => rc_service_floor
            //                    [value] => rdc
            //                )
            //
            //            [2] => Array
            //                (
            //                    [id] => rc_service_type_habitat
            //                    [value] => 0
            //                )
            //
            //        )

            WP_Log::debug( __METHOD__.' Before adding fees:', [ 'session' => WC()->session, 'rc_services' => $session_rc_services, 'total_fees' => $cart->get_fee_total(), 'fees' => $cart->get_fees() ], 'relais-colis-woocommerce' );

            if ( !empty( $session_rc_services ) ) {
                foreach ( $session_rc_services as $rc_service_key => $rc_service ) {

                    if ( is_array( $rc_service ) ) continue;

                    // Service key must start with WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                    if ( strpos( $rc_service, WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) !== 0 ) continue;

                    // Extract slug
                    // Start after prefix WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                    $slug = substr( $rc_service, strlen( WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) );

                    // Get price only for services, not for addon infos
                    $fixed_services = WC_RC_Services_Manager::instance()->get_fixed_services();
                    if ( !array_key_exists( $slug, $fixed_services ) ) continue;

                    // Get price for this service
                    $service_price = WP_Services_DAO::instance()->get_service_price_by_slug( $slug );

                    // Get service label
                    $service_label = WC_RC_Services_Manager::instance()->get_fixed_service_name( $slug );

                    if ( !is_numeric( $service_price ) ) {
                        WP_Log::error( __METHOD__.' - Invalid amount detected in add_fee', [
                            'service_label' => $service_label,
                            'service_price' => $service_price
                        ], 'relais-colis-woocommerce' );

                        return;
                    }
                    WP_Log::debug( __METHOD__.' Adding fee', [ 'service_label' => $service_label, 'service_price' => $service_price ], 'relais-colis-woocommerce' );

                    // Adding fee with taxable `false` and `''` for tax_class
                    $cart->add_fee( $service_label, floatval( $service_price ), false, 'standard' );
                }
            } else {

                WP_Log::debug( __METHOD__.' Session is detected with no fee:', [  ], 'relais-colis-woocommerce' );
                //$cart->fees_api()->remove_all_fees();
                //$cart->calculate_totals();
                //$cart->fees_api()->add_fee([]);
            }

            WP_Log::debug( __METHOD__.' - Get fees from cart', [ 'cart fees' => $cart->get_fees(), 'total_fees' => $cart->get_fee_total() ], 'relais-colis-woocommerce' );
        }
        else {

            WP_Log::debug( __METHOD__.' - rc_services not isset in session', [ 'cart fees' => $cart->get_fees(), 'total_fees' => $cart->get_fee_total() ], 'relais-colis-woocommerce' );
        }
    }

    /**
     * Prebuild div bloc for services selection
     * @param $offer one of WC_RC_Shipping_Constants METHOD_NAME_RELAIS_COLIS, METHOD_NAME_HOME, METHOD_NAME_HOME_PLUS
     */
    protected function render_choose_services_form() {

        // Get offer
        $offer = $this->get_specific_method_name();

        // Get available services for products in cart
        $services = WC_RC_Services_Manager::instance()->get_available_services_from_cart( $offer );
        WP_Log::debug( __METHOD__.' - Get available services for products in cart', [ '$services' => $services ], 'relais-colis-woocommerce' );

        $html_content = '';

        if ( empty( $services ) ) {

            //$html_content .= '<p>'.__( 'No service available for this delivery method', 'relais-colis-woocommerce' ).'</p>';
            return $html_content;
        }

        // Rendering depends on offer
        $html_content = '<ul id="rc-choose-options-'.$offer.'" style="display:none;">'.__( 'Available services:', 'relais-colis-woocommerce' );

        $session_rc_services = array();
        if ( WC()->session->__isset( 'rc_services' ) ) {

            $session_rc_services = WC()->session->get( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES );
            WP_Log::debug( __METHOD__.' - Session content', [ '$session_rc_services' => $session_rc_services ], 'relais-colis-woocommerce' );
        }
        
        // Only for available home+ services
        foreach ( $services as $service_slug => $service ) {

            $html_content .= '<li class="service-fee">';
            $service_key = WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.esc_attr( $service_slug );

            ob_start();
            woocommerce_form_field(
                $service_key,
                array(
                    'type' => 'checkbox',
                    'class' => array( $service_key ),
                    'label' => esc_html( $service[ 'name' ] ).' : '.wc_price( $service[ 'price' ] ),
                ),
                in_array( $service_key, $session_rc_services ) ? '1' : ''
            );
            $html_content .= ob_get_clean();

            $html_content .= '</li>';
        }

        // Add specific fields
        $html_content .= $this->render_choose_specific_services_form();

        $html_content .= '</ul>';

        return $html_content;
    }
}
