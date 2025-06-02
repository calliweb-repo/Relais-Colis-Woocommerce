<?php
// @phpcs:disable WordPress.Security.NonceVerification.Recommended
namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\DAO\WP_Products_DAO;
use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping rc_multiselect_products field definition
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Field_Multiselect_Products {

    const FIELD_RC_MULTISELECT_PRODUCTS = 'rc_multiselect_products';

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
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_MULTISELECT_PRODUCTS, array( $this, 'action_woocommerce_admin_field_rc_multiselect_products' ), 10, 1 );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );

        // Use AJAX handler
        WC_RC_Ajax_Get_Wc_Products::instance();
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

        // JS
        wp_enqueue_script( self::FIELD_RC_MULTISELECT_PRODUCTS.'_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/field-multiselect-products.js', array( 'jquery' ), '1.0', true );
        wp_enqueue_script( self::FIELD_RC_MULTISELECT_PRODUCTS.'select2_fr_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/select2-fr.js', array( 'jquery' ), '1.0', true );

        // CSS (if needed in the future)
        wp_enqueue_style(self::FIELD_RC_MULTISELECT_PRODUCTS.'_css', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/css/field-multiselect-products.css', array(), '1.0', 'all');

        // Pass AJAX URL and nonce to JavaScript
        wp_localize_script(
            self::FIELD_RC_MULTISELECT_PRODUCTS.'_js',
            'rc_multiselect_params',
            array(
                'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
                'nonce'    => wp_create_nonce( 'rc_multiselect_products_nonce' ),
            )
        );
    }

    /**
     * Get HTML for tooltips.
     *
     * @param array $data Data for the tooltip.
     * @return string
     */
    private function get_tooltip_html( $data ) {

        if ( true === $data[ 'desc_tip' ] ) $tip = $data[ 'description' ];
        elseif ( !empty( $data[ 'desc_tip' ] ) ) $tip = $data[ 'desc_tip' ];
        else $tip = '';

        return $tip ? wc_help_tip( $tip, true ) : '';
    }

    /**
     * Get HTML for descriptions.
     *
     * @param array $data Data for the description.
     * @return string
     */
    private function get_description_html( $data ) {

        if ( true === $data[ 'desc_tip' ] ) $description = '';
        elseif ( !empty( $data[ 'desc_tip' ] ) ) $description = $data[ 'description' ];
        elseif ( !empty( $data[ 'description' ] ) ) $description = $data[ 'description' ];
        else $description = '';

        return $description ? '<p class="description">'.wp_kses_post( $description ).'</p>'."\n" : '';
    }

    /**
     * Render field
     * @param $field
     */
    public function action_woocommerce_admin_field_rc_multiselect_products( $field ) {

        WP_Log::debug( __METHOD__, [ '$field' => $field ], 'relais-colis-woocommerce' );

        // Ensure the field ID exists
        if ( empty( $field[ 'id' ] ) ) {

            return;
        }

        // Set defaults
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'select_buttons'    => false,
        );
        $field  = wp_parse_args( $field, $defaults );

        // Retrieve selected product IDs
        $selected_products = isset( $field[ 'default' ] ) && is_array( $field[ 'default' ] ) ? $field[ 'default' ] : [];

        // Render the multi-select field
        $field_key = $field[ 'id' ];

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $field[ 'title' ] ); ?><?php echo wp_kses_post( $this->get_tooltip_html( $field ) ); // WPCS: XSS ok. ?></label>
            </th>
            <td class="forminp <?php echo esc_attr( $field['class'] ?? '' ); ?>">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $field[ 'title' ] ); ?></span>
                    </legend>
                    <select multiple="multiple"
                            class="multiselect wc-enhanced-select"
                            name="<?php echo esc_attr( $field_key ); ?>[]"
                            id="<?php echo esc_attr( $field_key ); ?>"
                            data-service-id="<?php echo esc_attr( $field['service_id'] ?? '' ); ?>"
                            style="<?php echo esc_attr( $field[ 'css' ] ); ?>"
                            <?php disabled( $field[ 'disabled' ], true ); ?>
                            >

                        <?php foreach ( $selected_products as $product_id => $product_name ) : ?>

                                <option value="<?php echo esc_attr( $product_id ); ?>" <?php selected( array_key_exists( $product_id, $selected_products ), true ); ?>><?php echo esc_html( $product_name ); ?></option>

                        <?php endforeach; ?>

                    </select>
                    <?php echo wp_kses_post( $this->get_description_html( $field ) ); // WPCS: XSS ok. ?>
                </fieldset>
            </td>
        </tr>
        <?php

        echo wp_kses_post( ob_get_clean() );
    }
}
