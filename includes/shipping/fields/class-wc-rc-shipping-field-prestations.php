<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping rc_prestations field definition
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Field_Prestations {

    const FIELD_RC_PRESTATIONS = 'rc_prestations';

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
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_PRESTATIONS, array( $this, 'action_woocommerce_admin_field_rc_prestations' ) );
    }


    /**
     * Render field
     * @param $field
     */
    public function action_woocommerce_admin_field_rc_prestations( $field ) {

        $fixed_prestations = WC_RC_Shipping_Prestations_Settings::instance()->get_fixed_prestations();

        $saved_prestations = get_option( 'rc_prestations', '[]' );
        $saved_prestations = json_decode( $saved_prestations, true );

        if ( !is_array( $saved_prestations ) ) {
            $saved_prestations = [];
        }

        ?>
        <div id="rc-prestations-editor">
            <div id="prestations-container">
                <header class="prestah">
                    <span><?php _e( 'Service name', 'relais-colis-woocommerce' ); ?></span>
                    <span><?php _e( 'Client choice', 'relais-colis-woocommerce' ); ?></span>
                    <span><?php _e( 'Delivery method', 'relais-colis-woocommerce' ); ?></span>
                    <span><?php _e( 'Active', 'relais-colis-woocommerce' ); ?></span>
                    <span><?php _e( 'Price', 'relais-colis-woocommerce' ); ?></span>
                </header>
                <?php foreach ( $fixed_prestations as $index => $prestation_name ): ?>
                    <?php
                    $saved_prestation = $saved_prestations[ $index ] ?? [];
                    $client_choice = esc_attr( $saved_prestation[ 'client_choice' ] ?? '' );
                    $method = esc_attr( $saved_prestation[ 'method' ] ?? '' );
                    $active = isset( $saved_prestation[ 'active' ] ) && $saved_prestation[ 'active' ] === 'yes' ? 'checked' : '';
                    $price = esc_attr( $saved_prestation[ 'price' ] ?? '' );
                    ?>
                    <div class="prestation-container" data-index="<?= esc_attr( $index ); ?>">
                        <div class="line-g">
                            <?= esc_html( $prestation_name ); ?>
                        </div>

                        <div class="line-g">
                            <select name="client_choice[<?= esc_attr( $index ); ?>]">
                                <option value="yes" <?php selected( $client_choice, 'yes' ); ?>><?php _e( 'Yes', 'relais-colis-woocommerce' ); ?></option>
                                <option value="no" <?php selected( $client_choice, 'no' ); ?>><?php _e( 'No', 'relais-colis-woocommerce' ); ?></option>
                            </select>
                        </div>

                        <div class="line-g">
                            <select name="delivery_method[<?= esc_attr( $index ); ?>]">
                                <option value="home" <?php selected( $method, 'home' ); ?>><?php _e( 'Home', 'relais-colis-woocommerce' ); ?></option>
                                <option value="home_plus" <?php selected( $method, 'home_plus' ); ?>><?php _e( 'Home+', 'relais-colis-woocommerce' ); ?></option>
                            </select>
                        </div>

                        <div class="line-g">
                            <input type="checkbox" name="active[<?= esc_attr( $index ); ?>]"
                                   value="yes" <?= $active; ?>>
                        </div>

                        <div class="line-g">
                            <input type="text" name="price[<?= esc_attr( $index ); ?>]" value="<?= $price; ?>"
                                   placeholder="<?php _e( 'Price in €', 'relais-colis-woocommerce' ); ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
}
