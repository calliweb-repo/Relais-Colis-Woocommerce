<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Orders_Rel_Shipping_Labels_DAO;
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
        
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_shipping_label_scripts' ) );
        add_action( 'wp_ajax_update_shipping_label', array( $this, 'ajax_update_shipping_label' ) );
        

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
                            <p><strong>'.__( 'Relay ID:', 'relais-colis-woocommerce' ).'</strong> '.esc_html( $rc_relay_data['Xeett'] ).'</p>
                            <p><strong>'.__( 'Relay Name:', 'relais-colis-woocommerce' ).'</strong> '.esc_html( $nom_relais ).'</p>
                            <p><strong>'.__( 'Address:', 'relais-colis-woocommerce' ).'</strong> '.esc_html( $adresse_relais ).', '.esc_html( $code_postal ).' '.esc_html( $ville ).', '.esc_html( $pays ).'</p>
                            <p><a href="'.esc_url( $google_maps_url ).'" target="_blank">'.__( 'View on Google Maps', 'relais-colis-woocommerce' ).'</a></p>
                            <p><strong>'.__( 'Opening Hours', 'relais-colis-woocommerce' ).'</strong></p>
                            <ul>
                        ';
                        foreach ( $horaires as $jour => $horaire ) {
                            $rc_shipping_infos_html .= '<li>'.esc_html( $jour ).': '.esc_html( $horaire ).'</li>';
                        }
                        $rc_shipping_infos_html .= '</ul>';

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

            // Follow links
            // Syntax: https://service.relaiscolis.com/wssuivicoliscritere/PageSuivi.aspx?Ref=4H091500000201
            // Load packages
            [ $colis, $items ] = WC_Order_Packages_Manager::instance()->load_order_packages( $wc_order->get_id() );
            $follow_links_html = '';

            foreach ( $colis as $c_colis ) {

                if ( array_key_exists( 'shipping_label', $c_colis ) ) {

                    $shipping_label = $c_colis[ 'shipping_label' ];
                    $link = 'https://service.relaiscolis.com/wssuivicoliscritere/PageSuivi.aspx?Ref='.$shipping_label;

                    // Build follow link
                    $follow_links_html .= '<li><a href="'.$link.'" target="_blank">'.__( 'Package', 'relais-colis-woocommerce' ).' <span>'.$shipping_label.'</span></a></li>';

                }
            }
            if ( !empty( $follow_links_html ) ) {

                $rc_shipping_infos_html .= '<div class="rc-tracking-links-info" data-order-id="'.$wc_order->get_id().'"><p><strong>'.__( 'Tracking links', 'relais-colis-woocommerce' ).'</strong> <a href="#" class="edit-shipping-labels" style="text-decoration:none; float:right; font-size:0.8em; color:#999;" title="'.__('Edit shipping labels', 'relais-colis-woocommerce').'"><span class="dashicons dashicons-edit"></span></a></p>';
                $rc_shipping_infos_html .= '<ul>'.$follow_links_html.'</ul></div>';
            }

            // Get logo
            $plugin_url = Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url();
            $logo_url = $plugin_url.'assets/img/livemapping/rc_long_logo.png';

            $html_content = '
                <div class="rc-shipping-info"><img src="'.esc_url( $logo_url ).'" alt="Relais Colis" class="rc-logo">
                    <h3>'.__( 'Relais Colis - Informations', 'relais-colis-woocommerce' ).'</h3>
                    <p><strong>'.__( 'RC Shipping method', 'relais-colis-woocommerce' ).' : </strong> '.WC_RC_Shipping_Method_Manager::instance()->get_rc_shipping_method_name( $rc_shipping_method ).'</p>
                    '.( !is_null( $rc_shipping_infos_html ) ? $rc_shipping_infos_html : '' ).'
                </div>';

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $html_content;
        }
    }

    /**
     * Enqueue scripts for shipping label editing
     */
    public function enqueue_shipping_label_scripts() {

        WP_Log::error( __METHOD__.' ENQUEUE SCRIPTS', [], 'relais-colis-woocommerce' );
        
        $screen = get_current_screen();
        
        if($screen && $screen->id !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders'){
            return;
        }

        // Enqueue dashicons if not already loaded
        wp_enqueue_style('dashicons');

        // Get plugin URL
        $plugin_url = Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url();
        
        // Enqueue notre CSS personnalisé
        wp_enqueue_style('rc-shipping-labels-editor', $plugin_url . 'assets/css/admin/shipping-labels-editor.css', array(), '1.0.0');
        
        // Enqueue notre script JavaScript
        wp_enqueue_script('rc-shipping-labels-editor', $plugin_url . 'assets/js/admin/shipping-labels-editor.js', array('jquery'), '1.0.0', true);

        // Get nonce
        wp_localize_script('rc-shipping-labels-editor', 'rc_shipping_labels_editor', array(
            'nonce' => wp_create_nonce('woocommerce-order')
        ));
    }

    /**
     * Handle AJAX request to update shipping label
     */
    public function ajax_update_shipping_label() {
        WP_Log::error( __METHOD__, [ '$_POST' => $_POST ], 'relais-colis-woocommerce' );
        check_ajax_referer('woocommerce-order', 'security');

        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error(array('message' => 'Vous n\'avez pas les permissions nécessaires.'));
            return;
        }

        // Nouveau : gestion du mode multi-update
        if (!empty($_POST['changes'])) {
            $changes = json_decode(stripslashes($_POST['changes']), true);
            if (empty($changes) || !is_array($changes)) {
                wp_send_json_error(array('message' => 'Aucune modification reçue.'));
                return;
            }
            $errors = [];
            $success = 0;
            foreach ($changes as $change) {
                $order_id = isset($change['order_id']) ? intval($change['order_id']) : 0;
                $old_label = isset($change['old_label']) ? sanitize_text_field($change['old_label']) : '';
                $new_label = isset($change['new_label']) ? sanitize_text_field($change['new_label']) : '';
                if (!$order_id || !$old_label || !$new_label) {
                    $errors[] = "Données manquantes pour la commande $order_id";
                    continue;
                }
                $result = $this->update_single_shipping_label($order_id, $old_label, $new_label);
                if ($result !== true) {
                    $errors[] = $result;
                } else {
                    $success++;
                }
            }
            if (empty($errors)) {
                wp_send_json_success(array('message' => "$success étiquette(s) mise(s) à jour avec succès."));
            } else {
                wp_send_json_error(array('message' => "$success succès, ".count($errors)." erreur(s) :\n".implode("\n", $errors)));
            }
            return;
        }

        // Ancien mode : un seul changement
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $old_label = isset($_POST['old_label']) ? sanitize_text_field($_POST['old_label']) : '';
        $new_label = isset($_POST['new_label']) ? sanitize_text_field($_POST['new_label']) : '';
        if (!$order_id || !$old_label || !$new_label) {
            wp_send_json_error(array('message' => 'Données manquantes pour la mise à jour.'));
            return;
        }
        $result = $this->update_single_shipping_label($order_id, $old_label, $new_label);
        if ($result === true) {
            wp_send_json_success(array('message' => 'Étiquette mise à jour avec succès.'));
        } else {
            wp_send_json_error(array('message' => $result));
        }
    }

    // Nouvelle méthode factorisée pour la mise à jour d'une seule étiquette
    private function update_single_shipping_label($order_id, $old_label, $new_label) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return 'Commande introuvable.';
        }
        global $wpdb;
        $colis = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rc_orders_rel_shipping_labels WHERE order_id = {$order_id}", ARRAY_A);
        $found = false;
        foreach ($colis as $key => $c_colis) {
            if (isset($c_colis['shipping_label']) && $c_colis['shipping_label'] === $old_label) {
                $colis[$key]['shipping_label'] = $new_label;
                $found = true;
                $colis_id = $c_colis['id'] ?? 0;
                if ($colis_id) {
                    $table_name = $wpdb->prefix . 'rc_orders_rel_shipping_labels';
                    $wpdb->update(
                        $table_name,
                        array('shipping_label' => $new_label),
                        array('id' => $colis_id),
                        array('%s'),
                        array('%d')
                    );
                }
                break;
            }
        }
        $rc_colis = $order->get_meta('_rc_colis', true);
        if (is_array($rc_colis)) {
            foreach ($rc_colis as $key => $colis_meta) {
                if (isset($colis_meta['shipping_label']) && $colis_meta['shipping_label'] === $old_label) {
                    $rc_colis[$key]['shipping_label'] = $new_label;
                    $found = true;
                    break;
                }
            }
            $order->update_meta_data('_rc_colis', $rc_colis);
            $order->save();
        }
        if ($found) {
            return true;
        } else {
            return "Étiquette $old_label non trouvée dans la commande $order_id.";
        }
    }
}
