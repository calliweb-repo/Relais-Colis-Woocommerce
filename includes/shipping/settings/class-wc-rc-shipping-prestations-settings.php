<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping Settings for Prestations section
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Prestations_Settings {

    const SECTION_PRESTATIONS = 'prestations';

    // Fixed services
    private $fixed_prestations = array(
        'Prise de Rendez-vous',
        'Livraison à l’étage',
        'Livraison à deux',
        'M.E.S gros électroménager',
        'Assemblage rapide',
        'Hors Norme',
        'Déballage produit',
        'Evacuation Emballage',
        'Reprise de votre ancien matériel',
        'Livraison dans la pièce souhaitée',
        'Livraison au pas de porte',
    );

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
        add_action( 'woocommerce_settings_tabs_'.WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_settings_rc_prestations' ) );

        // Update settings section
        add_action( 'woocommerce_update_options_'.WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS.'_'.self::SECTION_PRESTATIONS, array( $this, 'action_woocommerce_update_options_rc_prestations' ) );

        // Need custom fields
        WC_RC_Shipping_Field_Prestations::instance();
        WC_RC_Shipping_Field_Grilles_Tarifaires::instance();
    }

    /**
     * Getter for fixed_prestations
     * @return string[]
     */
    public function get_fixed_prestations() {

        return $this->fixed_prestations;
    }

    /**
     * Add section to the tab Relais Colis
     * @param $sections
     * @return mixed
     */
    public function filter_woocommerce_get_sections_rc( $sections ) {

        $sections[ self::SECTION_PRESTATIONS ] = __( 'Services', 'relais-colis-woocommerce' );
        return $sections;
    }

    /**
     * Add properties to the current section
     * @param $sections
     */
    public function action_woocommerce_settings_rc_prestations() {

        global $current_section;
        if ( $current_section !== self::SECTION_PRESTATIONS ) return;

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce');

        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Update properties
     */
    public function action_woocommerce_update_options_rc_prestations() {

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce');

        // Sauvegarde des grilles tarifaires
        if ( isset( $_POST[ 'grilles' ] ) && is_array( $_POST[ 'grilles' ] ) ) {

            $grilles_data = [];
            foreach ( $_POST[ 'grilles' ] as $grille_index => $grille ) {

                if ( !empty( $grille[ 'prestation_name' ] ) && !empty( $grille[ 'critere' ] ) ) {

                    $lines = [];
                    if ( isset( $grille[ 'lines' ] ) && is_array( $grille[ 'lines' ] ) ) {

                        foreach ( $grille[ 'lines' ] as $line_index => $line ) {
                            if ( isset( $line[ 'min' ], $line[ 'max' ], $line[ 'price' ] ) ) {

                                $lines[] = [
                                    'min' => sanitize_text_field( $line[ 'min' ] ),
                                    'max' => sanitize_text_field( $line[ 'max' ] ),
                                    'price' => sanitize_text_field( $line[ 'price' ] ),
                                ];
                            }
                        }
                    }
                    $grilles_data[] = [
                        'method_name' => sanitize_text_field( $grille[ 'method_name' ] ), // Enregistrement du nouveau champ
                        'prestation_name' => sanitize_text_field( $grille[ 'prestation_name' ] ),
                        'critere' => sanitize_text_field( $grille[ 'critere' ] ),
                        'lines' => $lines,
                    ];
                }
            }
            update_option( WC_RC_Shipping_Field_Grilles_Tarifaires::FIELD_RC_GRILLES_TARIFAIRES, wp_json_encode( $grilles_data ) );
        }

        $prestations_data = [];
        foreach ( WC_RC_Shipping_Prestations_Settings::instance()->get_fixed_prestations() as $index => $name ) {

            $prestations_data[] = [
                'name' => $name,
                'client_choice' => sanitize_text_field( $_POST[ 'client_choice' ][ $index ] ?? '' ),
                'method' => sanitize_text_field( $_POST[ 'delivery_method' ][ $index ] ?? '' ),
                'active' => isset( $_POST[ 'active' ][ $index ] ) ? 'yes' : 'no',
                'price' => sanitize_text_field( $_POST[ 'price' ][ $index ] ?? '' ),
            ];
        }
        update_option( WC_RC_Shipping_Field_Prestations::FIELD_RC_PRESTATIONS, wp_json_encode( $prestations_data ) );
    }

    /**
     * Get the properties
     * @return array
     */
    private function get_settings() {

        return [
            [
                'title' => __( 'Services', 'relais-colis-woocommerce' ), // Prestations
                'type' => 'title',
                'desc' => __( 'Add services with a free threshold.', 'relais-colis-woocommerce' ), // Ajoutez des prestations avec un seuil de gratuité.
                'id' => 'rc_prestations_title',
            ],
            [
                'type' => WC_RC_Shipping_Field_Prestations::FIELD_RC_PRESTATIONS,
                'id' => WC_RC_Shipping_Field_Prestations::FIELD_RC_PRESTATIONS,
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_prestations_section_end',
            ],
            [
                'title' => __( 'Pricing Grids', 'relais-colis-woocommerce' ), // Grilles Tarifaires
                'type' => 'title',
                'desc' => __( 'Define pricing tiers based on weight or total value.', 'relais-colis-woocommerce' ), // Définissez des tranches tarifaires basées sur le poids ou la valeur totale.
                'id' => 'rc_grilles_tarifaires_title',
            ],
            [
                'type' => WC_RC_Shipping_Field_Grilles_Tarifaires::FIELD_RC_GRILLES_TARIFAIRES,
                'id' => WC_RC_Shipping_Field_Grilles_Tarifaires::FIELD_RC_GRILLES_TARIFAIRES,
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_grilles_tarifaires_section_end',
            ],
        ];
    }
}
