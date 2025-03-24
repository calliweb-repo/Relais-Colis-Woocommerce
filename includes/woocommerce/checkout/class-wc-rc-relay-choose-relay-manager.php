<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WC_WooCommerce_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use Exception;

/**
 * WooCommerce Relais Colis Block Manager for relais picking.
 *
 * @since     1.0.0
 */
class WC_RC_Relay_Choose_Relay_Manager {

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

        // Register scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );

        // Inject JS template at the end of page
        add_action( 'wp_footer', array( $this, 'action_wp_footer' ) );

        // Relais Colis REST API used to update WooCOmmerce with selected relay
        add_action( 'wp_ajax_update_relay', array( $this, 'wp_ajax_update_relay' ) );
        add_action( 'wp_ajax_nopriv_update_relay', array( $this, 'wp_ajax_update_relay' ) );

        /**
         * Provides an opportunity to check cart items before checkout. This generally occurs during checkout validation.
         *
         * @see WC_Checkout::validate_checkout()
         * @since 3.0.0 or earlier
         */
        //add_action( 'woocommerce_check_cart_items', array( $this, 'action_woocommerce_check_cart_items' ) );
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'action_woocommerce_after_checkout_validation' ), 10, 2 );
    }

    /**
     * Provides an opportunity to check cart items before checkout. This generally occurs during checkout validation.
     * Used to check that a relay point has been selected
     * Old shortcode checkout mode
     *
     * @see WC_Checkout::validate_checkout()
     * @since 3.0.0 or earlier
     */
    public function action_woocommerce_after_checkout_validation( $data, $errors ) {

        WP_Log::notice( __METHOD__, [], 'relais-colis-woocommerce' );

        if ( !WC()->session->__isset( 'chosen_shipping_methods' ) || empty( WC()->session->get( 'chosen_shipping_methods' ) )  ) return;

        $chosen_shipping = WC()->session->get( 'chosen_shipping_methods' )[ 0 ];

        // Check if it is the RC relais mode
        if ( $chosen_shipping !== WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID ) return;
        WP_Log::notice( __METHOD__, [ '$chosen_shipping' => $chosen_shipping ], 'relais-colis-woocommerce' );

        // Must have selected a relay
        // Get rc_relay_data from WC session
        if ( ( !WC()->session->__isset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA ) )
            || empty( WC()->session->get( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA ) ) ) {

            WP_Log::debug( __METHOD__.' - Please select a relay point', [], 'relais-colis-woocommerce' );
            $errors->add( 'shipping', __( 'Please select a relay point', 'woocommerce' ) );
        }
    }


    /**
     * Enqueue needed scripts
     */
    public function action_wp_enqueue_scripts() {

        // Enqueued only in concerned checkout page
        if ( !is_checkout() ) return;

        // Relais colis plugin URL
        $plugin_url = Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url();
        $prefix_rc = WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID;

        // JS - JQuery, UI, Dialog, Leaflet, Lodash
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( "jquery-ui-dialog" );
        wp_enqueue_script( $prefix_rc.'_jquery_ui_js', $plugin_url.'assets/js/livemapping/jquery-ui.min.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_script( $prefix_rc.'_leaflet_js', $plugin_url.'assets/js/livemapping/leaflet.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_script( $prefix_rc.'_lodash_js', $plugin_url.'assets/js/livemapping/lodash.min.js', array( 'jquery' ), '1.0', true );

        // JS - Relais Colis
        wp_enqueue_script( $prefix_rc.'_js', $plugin_url.'assets/js/livemapping/choose-shipping-relay.js', array( 'jquery' ), '1.0', true );

        // CSS - JQuery, UI, Dialog, Leaflet
        wp_enqueue_style( $prefix_rc.'_font_awesome_css', "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css", array(), '6.0.0' );
        wp_enqueue_style( $prefix_rc.'_jquery_ui_css', "https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css" );
        wp_enqueue_style( $prefix_rc.'_leaflet_css', $plugin_url.'assets/css/livemapping/leaflet.css', array(), '1.0', 'all' );

        // CSS - Relais Colis
        wp_enqueue_style( $prefix_rc.'_listerelais_css', $plugin_url.'assets/css/livemapping/listerelais.css', array(), '1.0', 'all' );
        wp_enqueue_style( $prefix_rc.'_css', $plugin_url.'assets/css/livemapping/choose-shipping-relay.css', array(), '1.0', 'all' );

        // Get Wooc customer address
        $shipping_address = array(
            'address' => WC()->customer->get_shipping_address(),
            'postcode' => WC()->customer->get_shipping_postcode(),
            'city' => WC()->customer->get_shipping_city(),
        );
        WP_Log::debug( __METHOD__, [ '$shipping_address' => $shipping_address ], 'relais-colis-woocommerce' );

        wp_localize_script( WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID.'_js', 'rc_choose_relay',
            array(
                'map_c2c_apikey' => 'JSBS20210825143149943937534800',
                'map_c2c_enscode' => 'CC',
                'img_livemapping_path' => Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/img/livemapping/',
                'rc_shipping_address' => $shipping_address,
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'relais_colis_checkout' )
            )
        );
    }

    /**
     * Inject HTML via JS localize parameter
     */
    public function action_wp_footer() {

        // Enqueued only in concerned checkout page
        if ( !is_checkout() ) return;

        include Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_path().'templates/choose-relay-modale.php';
    }

    public function wp_ajax_update_relay() {

        WP_Log::debug( __METHOD__, [ 'POST' => $_POST ], 'relais-colis-woocommerce' );

        $nonce_check = check_ajax_referer( 'relais_colis_checkout', 'nonce', false );
        if ( !$nonce_check ) {

            WP_Log::error( __METHOD__.' - Nonce verification failed', [ 'received_nonce' => $_POST[ 'nonce' ] ?? 'MISSING' ], 'relais-colis-woocommerce' );
            wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );
        }
        //            [rc_relay_data] => Array
        //                (
        //                    [IconeLogo] => logoOuvert.png
        //                    [AffichageLien] => OK
        //                    [Distance] => 70
        //                    [Xeett] => G40A5
        //                    [Nomrelais] => CARREFOUR CITY CENTRE
        //                    [Lon] => 5.72674
        //                    [Lat] => 45.1891
        //                    [Nomdepositaire] => CARREFOUR CITY CENTRE
        //                    [Geocoadresse] => 9 RUE GUETAL
        //                    [Complementadresse] =>
        //                    [Postalcode] => 38000
        //                    [Commune] => GRENOBLE
        //                    [Countrycode] => FRA
        //                    [Urlrelais] => javascript:window.open(\'https://service.relaiscolis.com/tracking/point_relaiscolis.aspx?RelCode=G40A5\', \'window\', \'toolbar=no,status=no,menubar=no,scrollbars=auto,resizable=no,width=430,height=457,left=0,top=0\')
        //                    [Depositaireetat] => A
        //                    [Depositairenom] => Mr WAGNER Kevin
        //                    [Photopath] => https://service.relaiscolis.com/PhotosRelais/284190.JPG
        //                    [Photoname] => 284190.JPG
        //                    [Horairelundimatin] => 08:00-12:00
        //                    [Horairelundiapm] => 12:00-21:00
        //                    [Horairemardimatin] => 08:00-12:00
        //                    [Horairemardiapm] => 12:00-21:00
        //                    [Horairemercredimatin] => 08:00-12:00
        //                    [Horairemercrediapm] => 12:00-21:00
        //                    [Horairejeudimatin] => 08:00-12:00
        //                    [Horairejeudiapm] => 12:00-21:00
        //                    [Horairevendredimatin] => 08:00-12:00
        //                    [Horairevendrediapm] => 12:00-21:00
        //                    [Horairesamedimatin] => 08:00-12:00
        //                    [Horairesamediapm] => 12:00-21:00
        //                    [Horairedimanchematin] => 10:00-12:00
        //                    [Horairedimancheapm] => 12:00-17:00
        //                    [Datecreation] => 06/10/2023
        //                    [Datepremiercolis] => 08/10/2023
        //                    [Datederniercolis] =>
        //                    [Datefermeture] =>
        //                    [Agencecode] => G4
        //                    [Agencenom] => CIBLEX GRENOBLE POUR RC
        //                    [Agenceadresse1] => 25 RUE LOUI GAGNIERE
        //                    [Agenceadresse2] =>
        //                    [Agencecodepostal] => 38950
        //                    [Agenceville] => SAINT-MARTIN-LE-VINOUX
        //                    [Icone] => 1
        //                    [Relaismax] => 0
        //                    [Relaissmart] => 0
        //                    [formattedAddressLine] => 9 RUE GUETAL
        //                    [countryLabel] => France
        //                    [countryISO] => FRA
        //                    [streetLabel] => 9 RUE GUETAL
        //                    [Info1] =>
        //                    [Info2] =>
        //                    [Info3] =>
        //                    [Info4] =>
        //                    [Info5] =>
        //                    [Info6] =>
        //                    [Info7] =>
        //                    [Info8] =>
        //                    [Info9] =>
        //                    [Info10] =>
        //                    [Pseudorvc] => 06366
        //                    [MessageConges] =>
        //                    [AgenceCountryISO] => FRA
        //                    [IsLocker] => 0
        //                    [RelaisId] => 201773-02
        //                )

        // Retrieve relay data
        if ( !isset( $_POST[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA ] ) ) {

            WP_Log::error( __METHOD__.' - Missing rc_relay_data', [], 'relais-colis-woocommerce' );
            wp_send_json_error( [ 'message' => 'Missing relay information' ] );
        }

        // Secured JSON decode
        $rc_relay_data = $_POST[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA ];
        if ( !$rc_relay_data || !is_array( $rc_relay_data ) ) {

            WP_Log::error( __METHOD__.' - Invalid rc_relay_data format', [
                'rc_relay_data' => $_POST[ WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA ]
            ], 'relais-colis-woocommerce' );

            wp_send_json_error( [ 'message' => 'Invalid relay information format' ] );
        }

        // Liste des clés autorisées pour éviter l'injection de données indésirables
        $allowed_keys = [
            'IconeLogo', 'AffichageLien', 'Distance', 'Xeett', 'Nomrelais', 'Lon', 'Lat',
            'Nomdepositaire', 'Geocoadresse', 'Complementadresse', 'Postalcode', 'Commune',
            'Countrycode', 'Urlrelais', 'Depositaireetat', 'Depositairenom', 'Photopath', 'Photoname',
            'Horairelundimatin', 'Horairelundiapm', 'Horairemardimatin', 'Horairemardiapm',
            'Horairemercredimatin', 'Horairemercrediapm', 'Horairejeudimatin', 'Horairejeudiapm',
            'Horairevendredimatin', 'Horairevendrediapm', 'Horairesamedimatin', 'Horairesamediapm',
            'Horairedimanchematin', 'Horairedimancheapm', 'Datecreation', 'Datepremiercolis',
            'Datederniercolis', 'Datefermeture', 'Agencecode', 'Agencenom', 'Agenceadresse1',
            'Agenceadresse2', 'Agencecodepostal', 'Agenceville', 'Icone', 'Relaismax',
            'Relaissmart', 'formattedAddressLine', 'countryLabel', 'countryISO', 'streetLabel',
            'Info1', 'Info2', 'Info3', 'Info4', 'Info5', 'Info6', 'Info7', 'Info8', 'Info9',
            'Info10', 'Pseudorvc', 'MessageConges', 'AgenceCountryISO', 'IsLocker', 'RelaisId'
        ];

        // Filtrage et nettoyage des données pour éviter les injections XSS et SQL
        $sanitized_rc_relay_data = [];
        foreach ( $rc_relay_data as $key => $value ) {
            if ( in_array( $key, $allowed_keys ) ) {
                $sanitized_rc_relay_data[ $key ] = sanitize_text_field( $value );
            }
        }

        // Check that we have data
        if ( empty( $sanitized_rc_relay_data ) ) {
            WP_Log::error( __METHOD__.' - No valid relay data after sanitization', [], 'relais-colis-woocommerce' );
            wp_send_json_error( [ 'message' => 'No valid relay data' ] );
        }

        // Store in WC session
        WC()->session->set( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA, $sanitized_rc_relay_data );


        WP_Log::debug( __METHOD__.' - Relay data stored successfully', [
            'rc_relay_data' => $sanitized_rc_relay_data
        ], 'relais-colis-woocommerce' );

        // Success response with sanitized data
        wp_send_json_success( [
            'message' => 'Relay information saved successfully',
            WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA => $sanitized_rc_relay_data
        ] );
    }
}
