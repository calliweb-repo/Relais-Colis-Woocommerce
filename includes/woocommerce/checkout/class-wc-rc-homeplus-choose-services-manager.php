<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WC_RC_Services_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Relais Colis Block Manager for home+ services
 * Abstract is containing code necessary for old and FSE checkout modes
 *
 * @since     1.0.0
 */
class WC_RC_Homeplus_Choose_Services_Manager extends WC_RC_Choose_Services_Manager {

    use Singleton;

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        parent::init();
    }

    /**
     * Enqueue needed scripts
     */
    public function action_wp_enqueue_scripts() {

        parent::action_wp_enqueue_scripts();

        // Prebuild div bloc for services selection
        // Inject HTML via JS localize parameter
        $html_content = $this->render_choose_services_form();

        wp_localize_script( WC_RC_Checkout_Scripts_Manager::PREFIX_RC.'_js', 'rc_choose_options_hp',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'rc_choose_options' ),
                'html' => $html_content,
                'div_id' => 'rc-choose-options-hp',
                'rc_action' => 'update_rc_options'
            )
        );
    }

    /**
     * Template Method
     * Render list of specific fields to add to form
     * @return mixed
     */
    public function render_choose_specific_services_form() {

        $html_content = '';

        $session_rc_services = array();
        if ( WC()->session->__isset( WC_RC_Shipping_Constants::ORDER_META_DATA_RC_SERVICE_INFOS ) ) {

            $session_rc_services = WC()->session->get( 'rc_service_infos' );
            WP_Log::debug( __METHOD__.' - Session content', [ 'session service infos' => $session_rc_services ], 'relais-colis-woocommerce' );
        }

        // Addon home+ infos
        foreach ( WC_RC_Services_Manager::instance()->get_homeplus_addon_infos_fields() as $addon_infos_field_slug => $addon_infos_field ) {

            $html_content .= '<li class="service-info">';

            // Default value
            $default_value = '';
            $service_key = WC_RC_Services_Manager::HTML_SERVICES_ID_PREFIX.$addon_infos_field_slug;
            if ( in_array( $service_key, $session_rc_services ) ) {

                switch ( $addon_infos_field['type'] ) {
                    case 'checkbox':
                        $default_value = '1';
                        break;
                    case 'text':
                    case 'select':
                    case 'textarea':
                    default:
                        $default_value = $session_rc_services[$service_key];
                        break;
                }
            }

            ob_start();
            woocommerce_form_field(
                $service_key,
                $addon_infos_field,
                $default_value
            );
            $html_content .= ob_get_clean();

            $html_content .= '</li>';

        }
        return $html_content;
    }

    /**
     * Template Method
     * Get specific method name, WC_RC_Shipping_Constants METHOD_NAME_RELAIS_COLIS, METHOD_NAME_HOME, METHOD_NAME_HOME_PLUS
     * @return mixed
     */
    public function get_specific_method_name() {

        return WC_RC_Shipping_Constants::METHOD_NAME_HOME_PLUS;
    }
}
