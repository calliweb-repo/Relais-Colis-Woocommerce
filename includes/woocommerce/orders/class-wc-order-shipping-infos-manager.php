<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WC_RC_Services_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * Class WC_Order_Shipping_Infos_Manager
 *
 * This class is responsible for rendering infos about an order (opening hours...)
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Order_Shipping_Infos_Manager {

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

    }

    /**
     * Render a block for opening hours
     * @param $wc_order
     * @return void
     */
    public function render_shipping_infos( $wc_order ) {

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
                    $rc_relay_data = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_RELAY_DATA );
                    WP_Log::debug( __METHOD__, [ '$rc_relay_data' => $rc_relay_data ], 'relais-colis-woocommerce' );
                    if ( !empty( $rc_relay_data ) ) {

                        // Extract informations
                        $nom_relais = $rc_relay_data[ 'Nomrelais' ] ?? __( 'Unknown Relay', 'relais-colis-woocommerce' );
                        $adresse_relais = $rc_relay_data[ 'Geocoadresse' ] ?? __( 'No address available', 'relais-colis-woocommerce' );
                        $code_postal = $rc_relay_data[ 'Postalcode' ] ?? '';
                        $ville = $rc_relay_data[ 'Commune' ] ?? '';
                        $pays = $rc_relay_data[ 'countryLabel' ] ?? '';
                        $horaires = [
                            __( 'Monday', 'relais-colis-woocommerce' ) => $rc_relay_data[ 'Horairelundimatin' ].' / '.$rc_relay_data[ 'Horairelundiapm' ],
                            __( 'Tuesday', 'relais-colis-woocommerce' ) => $rc_relay_data[ 'Horairemardimatin' ].' / '.$rc_relay_data[ 'Horairemardiapm' ],
                            __( 'Wednesday', 'relais-colis-woocommerce' ) => $rc_relay_data[ 'Horairemercredimatin' ].' / '.$rc_relay_data[ 'Horairemercrediapm' ],
                            __( 'Thursday', 'relais-colis-woocommerce' ) => $rc_relay_data[ 'Horairejeudimatin' ].' / '.$rc_relay_data[ 'Horairejeudiapm' ],
                            __( 'Friday', 'relais-colis-woocommerce' ) => $rc_relay_data[ 'Horairevendredimatin' ].' / '.$rc_relay_data[ 'Horairevendrediapm' ],
                            __( 'Saturday', 'relais-colis-woocommerce' ) => $rc_relay_data[ 'Horairesamedimatin' ].' / '.$rc_relay_data[ 'Horairesamediapm' ],
                            __( 'Sunday', 'relais-colis-woocommerce' ) => $rc_relay_data[ 'Horairedimanchematin' ].' / '.$rc_relay_data[ 'Horairedimancheapm' ]
                        ];
                        $google_maps_url = "https://www.google.com/maps/search/?api=1&query=".urlencode( $adresse_relais.', '.$code_postal.' '.$ville.', '.$pays );

                        $rc_shipping_infos_html = '
                            <p><strong>'.__( 'Relay Name:', 'relais-colis-woocommerce' ).'</strong> '.esc_html( $nom_relais ).'</p>
                            <p><strong>'.__( 'Address:', 'relais-colis-woocommerce' ).'</strong> '.esc_html( $adresse_relais ).', '.esc_html( $code_postal ).' '.esc_html( $ville ).', '.esc_html( $pays ).'</p>
                            <p><a href="'.esc_url( $google_maps_url ).'" target="_blank">'.__( 'View on Google Maps', 'relais-colis-woocommerce' ).'</a></p>
                            <p><strong>'.__( 'Opening Hours', 'relais-colis-woocommerce' ).'</strong></p>
                            <ul>
                        ';
                        foreach ( $horaires as $jour => $horaire ) {
                            $rc_shipping_infos_html .= '<li>'.esc_html( $jour ).': '.esc_html( $horaire ).'</li>';
                        }
                        $rc_shipping_infos_html .= '</ul></div>';

                    }
                    break;
                case WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID:

                    // Check if rc_services
                    $rc_services = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES );
                    WP_Log::debug( __METHOD__, [ '$rc_services' => $rc_services ], 'relais-colis-woocommerce' );
                    if ( !empty( $rc_services ) ) {

                        $rc_shipping_infos_html = '<h4>'.__( 'Services', 'relais-colis-woocommerce' ).'</h4>';

                        foreach ( $rc_services as $rc_service ) {

                            // Service key must start with WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                            if ( strpos( $rc_service, WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) !== 0 ) continue;

                            // Extract slug
                            // Start after prefix WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX
                            $slug = substr( $rc_service, strlen( WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX ) );

                            $rc_shipping_infos_html .= '<p>'.WC_RC_Services_Manager::instance()->get_fixed_service_name( $slug ).'</p>';
                        }
                    }

                    break;
                case WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID:

                    // Check if rc_services
                    $rc_services = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICES );
                    WP_Log::debug( __METHOD__, [ '$rc_services' => $rc_services ], 'relais-colis-woocommerce' );

                    // Check if rc_service_infos
                    $rc_service_infos = $wc_order->get_meta( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS );
                    WP_Log::debug( __METHOD__, [ '$rc_service_infos' => $rc_service_infos ], 'relais-colis-woocommerce' );

                    // Title
                    if ( !empty( $rc_services ) && !empty( $rc_service_infos ) ) {

                        $rc_shipping_infos_html = '<h4>'.__( 'Services', 'relais-colis-woocommerce' ).'</h4>';
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

                            $rc_shipping_infos_html .= '<p>'.WC_RC_Services_Manager::instance()->get_fixed_service_name( $slug ).'</p>';
                        }
                    }

                    // Service infos content
                    if ( !empty( $rc_service_infos ) && is_array( $rc_service_infos ) ) {

                        $rc_shipping_infos_html .= '<h4>'.__( 'Relais Colis - Additional infos', 'relais-colis-woocommerce' ).'</h4>';

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
                                    $rc_shipping_infos_html .= '<p><strong>'.$homeplus_addon_infos_field[ 'label' ].':</strong> '.$rc_service_infos[ WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$homeplus_addon_infos_slug ].'</p>';
                                    break;
                                case 'textarea':
                                    $rc_shipping_infos_html .= '<p><strong>'.$homeplus_addon_infos_field[ 'label' ].':</strong><br>'.$rc_service_infos[ WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$homeplus_addon_infos_slug ].'</p>';
                                    break;
                                case 'select':
                                    $rc_shipping_infos_html .= '<p><strong>'.$homeplus_addon_infos_field[ 'label' ].':</strong> '.$homeplus_addon_infos_field[ 'options' ][ ''.$rc_service_infos[ WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$homeplus_addon_infos_slug ] ].'</p>';
                                    break;
                                case 'checkbox':
                                    $rc_shipping_infos_html .= '<p><strong>'.$homeplus_addon_infos_field[ 'label' ].':</strong> '.( $rc_service_infos[ WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$homeplus_addon_infos_slug ] === 1 ? __( 'Yes', 'relais-colis-woocommerce' ) : __( 'No', 'relais-colis-woocommerce' ) ).'</p>';
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

            // Get logo
            $plugin_url = Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url();
            $logo_url = $plugin_url.'assets/img/livemapping/rc_long_logo.png';

            $html_content = '
                <div class="rc-shipping-info"><img src="'.esc_url( $logo_url ).'" alt="Relais Colis" class="rc-logo">
                    <h3>'.__( 'Relais Colis - Informations', 'relais-colis-woocommerce' ).'</h3>
                    <p><strong>'.__( 'RC Shipping method', 'relais-colis-woocommerce' ).'</strong> '.WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method_name( $rc_shipping_method ).'</p>
                    '.( !is_null( $rc_shipping_infos_html ) ? $rc_shipping_infos_html : '' ).'
                </div>';

            echo $html_content;
        }
    }
}
