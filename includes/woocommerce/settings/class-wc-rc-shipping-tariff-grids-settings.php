<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Tariff_Grids_DAO;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Admin_Settings;

/**
 * WooCommerce Shipping Settings for Prices section
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Tariff_Grids_Settings {

    const SECTION_TARIFF_GRIDS = 'prices';

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
        add_filter( 'woocommerce_get_sections_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'filter_woocommerce_get_sections_rc' ) );

        // Register settings section
        add_action( 'woocommerce_settings_tabs_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS, array( $this, 'action_woocommerce_settings_rc_prices' ) );

        // Update settings section
        add_action( 'woocommerce_update_options_'.WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS.'_'.self::SECTION_TARIFF_GRIDS, array( $this, 'action_woocommerce_update_options_rc_prices' ) );
    }

    /**
     * Add section to the tab Relais Colis
     * @param $sections
     * @return mixed
     */
    public function filter_woocommerce_get_sections_rc( $sections ) {

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        // Only for B2C interaction mode
        //if ( WC_RC_Shipping_Config_Manager::instance()->is_c2c_interaction_mode() ) return $sections;

        $sections[ self::SECTION_TARIFF_GRIDS ] = __( 'Prices Grid', 'relais-colis-woocommerce' );
        return $sections;
    }

    /**
     * Add properties to the current section
     * @param $sections
     */
    public function action_woocommerce_settings_rc_prices() {

        global $current_section;
        if ( $current_section !== self::SECTION_TARIFF_GRIDS ) return;

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        woocommerce_admin_fields( $this->get_settings() );
    }

    /**
     * Update properties
     */
    public function action_woocommerce_update_options_rc_prices() {

        WP_Log::debug( __METHOD__, [ '$_POST' => $_POST ], 'relais-colis-woocommerce' );

        if ( !isset( $_POST[ 'tariffs' ] ) || !is_array( $_POST[ 'tariffs' ] ) ) {

            return;
        }

        $tariff_grids_dao = WP_Tariff_Grids_DAO::instance();

        // Empty table before reinsertion
        $tariff_grids_dao->delete_all_tariff_grids();

        $error_occurred = false;

        // Treat each tariff grid
        foreach ( $_POST[ 'tariffs' ] as $tariff ) {

            if ( !isset( $tariff[ 'method_name' ], $tariff[ 'criteria' ], $tariff[ 'lines' ] ) || !is_array( $tariff[ 'lines' ] ) ) {

                continue;
            }

            foreach ( $tariff[ 'lines' ] as $line ) {

                if ( !isset( $line[ 'min' ] ) || !is_numeric( $line[ 'min' ] ) ) {

                    WC_Admin_Settings::add_error( __( "A min value must be entered.", 'relais-colis-woocommerce' ) );
                    continue;
                }
                if ( !isset( $line[ 'price' ] ) || !is_numeric( $line[ 'price' ] ) ) {

                    WC_Admin_Settings::add_error( __( "A price value must be entered.", 'relais-colis-woocommerce' ) );
                    continue;
                }

                // Manage not defined "max"  → NULL in DB
                $min_value = floatval( $line[ 'min' ] );
                $max_value = ( isset( $line[ 'max' ] ) && $line[ 'max' ] !== '' ) ? floatval( $line[ 'max' ] ) : null;
                $price = floatval( $line[ 'price' ] );

                // Check if min is greater than max
                if ( !is_null( $max_value ) && ( $min_value >= $max_value ) ) {
                    /* translators: 1: min value, 2: max value */
                    $message =  sprintf( __( 'Min value (%1$s) must be less than Max value (%2$s).', 'relais-colis-woocommerce' ), $min_value, $max_value );
                    WC_Admin_Settings::add_error( $message );
                    WP_Log::warning( __METHOD__.' - Min value must be less than max value', [
                        'min_value' => $min_value,
                        'max_value' => $max_value
                    ], 'relais-colis-woocommerce' );
                    $error_occurred = true;
                    continue; // Skip this entry
                }

                try {
                    $tariff_grids_dao->insert_tariff_grid(
                        sanitize_text_field( $tariff[ 'method_name' ] ),
                        sanitize_text_field( $tariff[ 'criteria' ] ),
                        $min_value,
                        $max_value,
                        $price,
                        $tariff[ 'shipping_threshold' ]
                    );
                } catch ( WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception ) {

                    WP_Log::warning( __METHOD__.' - Error response', [
                        'code' => $wp_relais_colis_api_exception->getCode(),
                        'message' => $wp_relais_colis_api_exception->getMessage(),
                        'detail' => $wp_relais_colis_api_exception->get_detail()
                    ], 'relais-colis-woocommerce' );

                    WC_Admin_Settings::add_error( $wp_relais_colis_api_exception->getMessage() );
                    $error_occurred = true;
                }
            }
        }

        if ( !$error_occurred ) {

            WC_Admin_Settings::add_message( __( 'Tariff grids updated successfully!', 'relais-colis-woocommerce' ) );
        }
    }

    /**
     * Get the properties
     * @return array
     */
    private function get_settings() {

        WP_Log::debug( __METHOD__, [], 'relais-colis-woocommerce' );

        // Other tabs loaded only if RC API access is valid
        if ( !WC_RC_Shipping_Config_Manager::instance()->is_rc_api_valid_access() ) {

            return WC_RC_Shipping_Settings_Manager::instance()->get_invalid_licence_settings();
        }

        // Weight unit
        $option_rc_weight_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT );

        // Try and get better unit display
        $weight_units = WC_RC_Shipping_Constants::get_weight_units();
        if ( array_key_exists( $option_rc_weight_unit, $weight_units ) ) {

            $option_rc_weight_unit = $weight_units[ $option_rc_weight_unit ];
        }

        return [
            [
                'title' => __( 'Tariff Grids', 'relais-colis-woocommerce' ),
                'type' => 'title',
                /* translators: 1: weight unit */
                'desc' => sprintf( __( 'Add prices with a free threshold. The unit of weight is %s', 'relais-colis-woocommerce' ), $option_rc_weight_unit ),
                'id' => 'rc_prices_title',
            ],
            [
                'type' => WC_RC_Shipping_Field_Tariff_Grids::FIELD_RC_TARIFF_GRIDS,
                'id' => WC_RC_Shipping_Field_Tariff_Grids::FIELD_RC_TARIFF_GRIDS,
            ],
            [
                'type' => 'sectionend',
                'id' => 'rc_prices_grid_section_end',
            ],
        ];
    }
}
