<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended
namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Tariff_Grids_DAO;
use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping rc_prices_grid field definition
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Field_Tariff_Grids {

    const FIELD_RC_TARIFF_GRIDS = 'rc_prices_grid';

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

        // Render custom fields
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_TARIFF_GRIDS, array( $this, 'action_woocommerce_admin_field_rc_prices_grid' ) );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
    }

    /**
     * Enqueue needed scripts
     */
    public function action_admin_enqueue_scripts() {

        // Enqueued only in concerned settings page
        $screen = get_current_screen();
        if ( ( $screen->id !== 'woocommerce_page_wc-settings' ) || !isset( $_GET[ 'tab' ] ) || ( $_GET[ 'tab' ] !== WC_RC_Shipping_Settings_Manager::WC_RC_SHIPPING_SETTINGS ) ) {

            return;
        }

        // CSS
        wp_enqueue_style( 'font-awesome', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/font-awesome-6.5.1.min.css' );
        wp_enqueue_style( self::FIELD_RC_TARIFF_GRIDS.'_css', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/field-tariff-grids.css', array(), '1.0', 'all' );

        // JS
        wp_enqueue_script( self::FIELD_RC_TARIFF_GRIDS.'_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/field-tariff-grids.js', array( 'jquery' ), '1.0', true );


        // Weight unit
        $option_rc_weight_unit = get_option( WC_RC_Shipping_Constants::OPTION_RC_WEIGHT_UNIT );

        // Try and get better unit display
        $weight_units = WC_RC_Shipping_Constants::get_weight_units();
        if ( array_key_exists( $option_rc_weight_unit, $weight_units ) ) {

            $option_rc_weight_unit = $weight_units[ $option_rc_weight_unit ];
        }

        // Check which offers are enabled
        $shipping_config_manager = WC_RC_Shipping_Config_Manager::instance();
        $available_offers = array();

        if ( $shipping_config_manager->has_delivery_offer_enabled( WC_RC_Shipping_Constants::OFFER_RELAIS_COLIS ) ) {
            $available_offers[] = array( 'value' => 'rc', 'label' => 'Relais Colis' );
        }
        if ( $shipping_config_manager->has_delivery_offer_enabled( WC_RC_Shipping_Constants::OFFER_HOME ) ) {
            $available_offers[] = array( 'value' => 'h', 'label' => 'Relais Colis Home' );
        }
        if ( $shipping_config_manager->has_delivery_offer_enabled( WC_RC_Shipping_Constants::OFFER_HOME_PLUS ) ) {
            $available_offers[] = array( 'value' => 'hp', 'label' => 'Relais Colis Home+' );
        }

        // Pass script params to JS
        wp_localize_script( self::FIELD_RC_TARIFF_GRIDS.'_js', 'rc_ajax', array(
            'delete_label' => __( 'Delete', 'relais-colis-woocommerce' ), // Supprimer
            'delivery_method_label' => __( 'Delivery method name', 'relais-colis-woocommerce' ), // Nom de la méthode de livraison
            'criteria_label' => __( 'Criteria type', 'relais-colis-woocommerce' ), // Type de critère tarifaire
            'total_price_label' => __( 'Total order price', 'relais-colis-woocommerce' ), // Prix total de la commande
            'weight_label' => __( 'Order weight', 'relais-colis-woocommerce' ) . $option_rc_weight_unit, // Poids de la commande
            'tariff_ranges_label' => __( 'Tariff ranges', 'relais-colis-woocommerce' ), // Plages tarifaires
            'add_line_label' => __( 'Add a line', 'relais-colis-woocommerce' ), // Ajouter une ligne
            'shipping_threshold_label' => __( 'Shipping threshold', 'relais-colis-woocommerce' ), // Seuil de livraison
            'weight_unit_label' => $option_rc_weight_unit,
            'available_offers' => $available_offers,
        ) );

        // Load tariff grids
        $tariff_grids = WP_Tariff_Grids_DAO::instance()->get_grouped_tariff_grids();

        ?>
        <script type="text/javascript">
            var groupedTariffs = <?php echo json_encode($tariff_grids); ?>;
        </script>
        <?php
    }

    /**
     * Render field
     * @param $field
     */
    public function action_woocommerce_admin_field_rc_prices_grid( $value ) {

        ?>
        <div id="rc-tariff-container">
            <button type="button" id="add-tariff"
                    class="button button-primary"><?php esc_html_e( 'Add a new tariff grid', 'relais-colis-woocommerce' ); ?></button>
            <div id="tariffs-list">
                <!-- Prices grid are injected here using jQuery -->
            </div>
        </div>
        <?php
    }
}
