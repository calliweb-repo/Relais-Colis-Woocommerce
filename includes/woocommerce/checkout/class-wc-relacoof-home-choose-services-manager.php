<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Relais Colis Block Manager for home services
 * Abstract is containing code necessary for old and FSE checkout modes
 *
 * @since     1.0.0
 */
class WC_Relacoof_Home_Choose_Services_Manager extends WC_Relacoof_Choose_Services_Manager {

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

        wp_localize_script( WC_Relacoof_Checkout_Scripts_Manager::PREFIX_RC.'_js', 'relacoof_choose_options_h',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'relacoof_choose_options' ),
                'html' => $html_content,
                'div_id' => 'relacoof-choose-options-h'
            )
        );
    }


    /**
     * Template Method
     * Render list of specific fields to add to form
     * @return mixed
     */
    public function render_choose_specific_services_form() {

        return '';
    }

    /**
     * Template Method
     * Get specific method name, WC_Relacoof_Shipping_Constants METHOD_NAME_RELAIS_COLIS, METHOD_NAME_HOME, METHOD_NAME_HOME_PLUS
     * @return mixed
     */
    public function get_specific_method_name() {

        return WC_Relacoof_Shipping_Constants::METHOD_NAME_HOME;
    }
}