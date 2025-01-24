<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping Settings for Informations section
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Informations_Settings {

    const SECTION_INFORMATIONS = 'informations';

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

        // Register settings section
        add_filter( 'woocommerce_get_sections_'.WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS, array( $this, 'filter_woocommerce_get_sections_rc' ) );

        // Register settings section
        add_action( 'woocommerce_settings_tabs_'.WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_settings_rc_informations' ) );

        // Update settings section
        add_action( 'woocommerce_update_options_'.WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS.'_'.self::SECTION_INFORMATIONS, array( $this, 'action_woocommerce_update_options_rc_informations' ) );

        // Need custom fields
        WC_RC_Shipping_Field_Action_Buttons::instance();
    }

    /**
     * Add section to the tab Relais Colis
     * @param $sections
     * @return mixed
     */
    public function filter_woocommerce_get_sections_rc( $sections ) {

        $sections[ self::SECTION_INFORMATIONS ] = __( 'Your Information', 'relais-colis-woocommerce' );
        return $sections;
    }

    /**
     * Add properties to the current section
     * @param $sections
     * @return mixed
     */
    public function action_woocommerce_settings_rc_informations() {

        global $current_section;
        if ( $current_section !== self::SECTION_INFORMATIONS ) return;

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce');

        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Update properties
     */
    public function action_woocommerce_update_options_rc_informations() {

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce');

        woocommerce_update_options( $this->get_settings() );
    }

    /**
     * Get the properties
     * @return array
     */
    private function get_settings() {

        return [
            // Vos informations
            [
                'title' => __('Your Information', 'relais-colis-woocommerce'), // Vos informations
                'type'  => 'title',
                'desc'  => __('Enter your activation key to synchronize your information.', 'relais-colis-woocommerce'), // Entrez votre clé d’activation pour synchroniser vos informations.
                'id'    => 'rc_informations_title',
            ],
            // Clé d'activation
            [
                'title'    => __('Activation Key', 'relais-colis-woocommerce'), // Clé d’activation
                'id'       => 'rc_api_key',
                'type'     => 'text',
                'default'  => '',
                'desc_tip' => __('Your C2C or B2C activation key.', 'relais-colis-woocommerce'), // Votre clé d’activation C2C ou B2C.
            ],
            // Boutons Extraire et rafraîchir
            [
                'type' => WC_RC_Shipping_Field_Action_Buttons::FIELD_RC_ACTION_BUTTONS, // Boutons Extraire et rafraîchir
                'id'   => WC_RC_Shipping_Field_Action_Buttons::FIELD_RC_ACTION_BUTTONS,
            ],
            // Section : Options B2C
            [
                'title' => __('B2C Options', 'relais-colis-woocommerce'), // Options B2C
                'type'  => 'title',
                'desc'  => __('Configure the options included in your B2C account.', 'relais-colis-woocommerce'), // Configurez les options incluses dans votre compte B2C.
                'id'    => 'rc_b2c_options_title',
            ],
            // Liste produits
            [
                'title'    => __('Product List', 'relais-colis-woocommerce'), // Liste de Produits
                'id'       => 'rc_b2c_product_list',
                'type'     => 'multiselect',
                'options'  => [
                    'product_a' => __('Product A', 'relais-colis-woocommerce'), // Produit A
                    'product_b' => __('Product B', 'relais-colis-woocommerce'), // Produit B
                    'product_c' => __('Product C', 'relais-colis-woocommerce'), // Produit C
                ],
            ],
            // Méthodes de livraison
            [
                'title'    => __('Delivery Methods', 'relais-colis-woocommerce'), // Méthodes de livraison
                'id'       => 'rc_b2c_shipping_methods',
                'type'     => 'multiselect',
                'options'  => [
                    'rendez_vous'        => __('Appointment Scheduling', 'relais-colis-woocommerce'), // Prise de rendez-vous
                    'etage'              => __('Delivery to Floor', 'relais-colis-woocommerce'), // Livraison à l’étage
                    'a_deux'             => __('Two-Person Delivery', 'relais-colis-woocommerce'), // Livraison à deux
                    'mes_electro'        => __('Large Appliance Delivery', 'relais-colis-woocommerce'), // M.E.S gros électroménager
                    'assemblage'         => __('Quick Assembly', 'relais-colis-woocommerce'), // Assemblage rapide
                    'hors_norme'         => __('Oversized Items', 'relais-colis-woocommerce'), // Hors Norme
                    'deballage'          => __('Product Unpacking', 'relais-colis-woocommerce'), // Déballage produit
                    'evacuation'         => __('Packaging Removal', 'relais-colis-woocommerce'), // Evacuation Emballage
                    'ancien_materiel'    => __('Old Equipment Removal', 'relais-colis-woocommerce'), // Reprise ancien matériel
                    'piece_souhaitee'    => __('Delivery to Desired Room', 'relais-colis-woocommerce'), // Livraison dans la pièce souhaitée
                    'pas_de_porte'       => __('Doorstep Delivery', 'relais-colis-woocommerce'), // Livraison au pas de porte
                ],
            ],
            // Attribution de prix
            [
                'title'    => __('Pricing Assignment', 'relais-colis-woocommerce'), // Attribution de Prix
                'id'       => 'rc_b2c_pricing',
                'type'     => 'text',
                'default'  => '',
                'desc'     => __('Offered by default', 'relais-colis-woocommerce'), // Par défaut offert
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rc_b2c_section_end',
            ],
            [
                'type' => 'sectionend',
                'id'   => 'rc_informations_section_end',
            ],
        ];
    }
}
