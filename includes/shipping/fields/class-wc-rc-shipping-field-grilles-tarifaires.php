<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Relais_Colis_Woocommerce_Loader;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * WooCommerce Shipping rc_grilles_tarifaires field definition
 *
 * @since     1.0.0
 */
class WC_RC_Shipping_Field_Grilles_Tarifaires {

    const FIELD_RC_GRILLES_TARIFAIRES = 'rc_grilles_tarifaires';

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
        add_action( 'woocommerce_admin_field_'.self::FIELD_RC_GRILLES_TARIFAIRES, array( $this, 'action_woocommerce_admin_field_rc_grilles_tarifaires' ) );

        // Register scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
    }

    /**
     * Enqueue needed scripts
     */
    public function action_admin_enqueue_scripts() {

        // Enqueued only in concerned settings page
        $screen = get_current_screen();
        if ( ( $screen->id !== 'woocommerce_page_wc-settings' ) || !isset($_GET['tab']) || ( $_GET['tab'] !== WC_RC_Shipping_Settings::WC_RC_SHIPPING_SETTINGS ) ) {

            return;
        }

        // JS
        wp_enqueue_script( self::FIELD_RC_GRILLES_TARIFAIRES.'_js', Relais_Colis_Woocommerce_Loader::instance()->get_plugin_dir_url().'assets/js/field-grilles-tarifaires.js', array( 'jquery' ), '1.0', true );

        // Pass script params to JS

        wp_localize_script(
            self::FIELD_RC_GRILLES_TARIFAIRES.'_js',
            'rc_templates',
            [
                'grille_template' => $this->render_single_grille_template(),
                'line_template'   => $this->render_single_grille_line_template(),
            ]
        );
    }

    /**
     * Render field
     * @param $field
     */
    public function action_woocommerce_admin_field_rc_grilles_tarifaires( $field ) {

        $saved_grilles = get_option('rc_grilles_tarifaires', '[]');
        $saved_grilles = json_decode($saved_grilles, true) ?: [];

        $fixed_prestations = WC_RC_Shipping_Prestations_Settings::instance()->get_fixed_prestations();

        ?>
        <div id="rc-grilles-tarifaires">
            <button type="button" id="add-grille" class="button button-secondary">
                <?php _e('Ajouter une grille tarifaire', 'relais-colis-woocommerce'); ?>
            </button>
            <div id="grilles-container">
                <?php foreach ($saved_grilles as $grille_index => $grille): ?>
                    <?php self::render_single_grille($grille_index, $grille, $fixed_prestations); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }


    /**
     * Render a single grille.
     *
     * @param int $grille_index The index of the grille.
     * @param array $grille The grille data.
     * @param array $fixed_prestations Fixed prestations data.
     */
    private function render_single_grille($grille_index, $grille, $fixed_prestations) {
        ?>
        <div class="grille-container" data-index="<?= esc_attr($grille_index); ?>">
            <button type="button" class="remove-grille">❌</button>
            <div class="line-g">
                <label><?php _e('Delivery method name', 'relais-colis-woocommerce'); ?></label>
                <input type="text" name="grilles[<?= esc_attr($grille_index); ?>][method_name]"
                       value="<?= esc_attr($grille['method_name'] ?? ''); ?>"
                       placeholder="<?php _e('Example: Home delivery', 'relais-colis-woocommerce'); ?>">
            </div>
            <div class="grille-header">
                <div class="line-g">
                    <label><?php _e('Pricing criteria type', 'relais-colis-woocommerce'); ?></label>
                    <select name="grilles[<?= esc_attr($grille_index); ?>][critere]">
                        <option value="price" <?php selected($grille['critere'] ?? '', 'price'); ?>>
                            <?php _e('Total order price', 'relais-colis-woocommerce'); ?>
                        </option>
                        <option value="weight" <?php selected($grille['critere'] ?? '', 'weight'); ?>>
                            <?php _e('Order weight', 'relais-colis-woocommerce'); ?>
                        </option>
                    </select>
                </div>
            </div>
            <div class="lines-container">
                <?php foreach ($grille['lines'] ?? [] as $line_index => $line): ?>
                    <?php $this->render_single_grille_line($grille_index, $line_index, $line); ?>
                <?php endforeach; ?>
            </div>
            <button type="button" class="add-line button button-secondary">
                <?php _e('Add a line', 'relais-colis-woocommerce'); ?>
            </button>
        </div>
        <?php
    }

    /**
     * Render a single line inside a grille.
     *
     * @param int $grille_index The index of the grille.
     * @param int $line_index The index of the line.
     * @param array $line The line data.
     */
    private function render_single_grille_line($grille_index, $line_index, $line) {
        ?>
        <div class="line-row">
            <div class="line-g">
                <label><?php _e('Start value', 'relais-colis-woocommerce'); ?></label>
                <input type="number" name="grilles[<?= esc_attr($grille_index); ?>][lines][<?= esc_attr($line_index); ?>][min]"
                       value="<?= esc_attr($line['min'] ?? ''); ?>" placeholder="<?php _e('Min', 'relais-colis-woocommerce'); ?>">
            </div>
            <div class="line-g">
                <label><?php _e('End value', 'relais-colis-woocommerce'); ?></label>
                <input type="number" name="grilles[<?= esc_attr($grille_index); ?>][lines][<?= esc_attr($line_index); ?>][max]"
                       value="<?= esc_attr($line['max'] ?? ''); ?>" placeholder="<?php _e('Max', 'relais-colis-woocommerce'); ?>">
            </div>
            <div class="line-g">
                <label><?php _e('Price', 'relais-colis-woocommerce'); ?></label>
                <input type="number" step="0.01" name="grilles[<?= esc_attr($grille_index); ?>][lines][<?= esc_attr($line_index); ?>][price]"
                       value="<?= esc_attr($line['price'] ?? ''); ?>" placeholder="<?php _e('Price', 'relais-colis-woocommerce'); ?>">
            </div>
            <button type="button" class="remove-line">❌</button>
        </div>
        <?php
    }

    /**
     * Render the template for a single grille with placeholders.
     *
     * @return string The HTML template for a single grille.
     */
    private function render_single_grille_template() {
        ob_start();
        ?>
        <div class="grille-container" data-index="__INDEX__">
            <button type="button" class="remove-grille">❌</button>
            <div class="line-g">
                <label><?php _e('Delivery method name', 'relais-colis-woocommerce'); ?></label>
                <input type="text" name="grilles[__INDEX__][method_name]" placeholder="<?php _e('Example: Home delivery', 'relais-colis-woocommerce'); ?>">
            </div>
            <div class="grille-header">
                <div class="line-g">
                    <label><?php _e('Pricing criteria type', 'relais-colis-woocommerce'); ?></label>
                    <select name="grilles[__INDEX__][critere]">
                        <option value="price"><?php _e('Total order price', 'relais-colis-woocommerce'); ?></option>
                        <option value="weight"><?php _e('Order weight', 'relais-colis-woocommerce'); ?></option>
                    </select>
                </div>
            </div>
            <div class="lines-container"></div>
            <button type="button" class="add-line button button-secondary">
                <?php _e('Add a line', 'relais-colis-woocommerce'); ?>
            </button>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the template for a single line with placeholders.
     *
     * @return string The HTML template for a single line.
     */
    private function render_single_grille_line_template() {
        ob_start();
        ?>
        <div class="line-row">
            <div class="line-g">
                <label><?php _e('Start value', 'relais-colis-woocommerce'); ?></label>
                <input type="number" name="grilles[__GRILLE_INDEX__][lines][__LINE_INDEX__][min]" placeholder="<?php _e('Min', 'relais-colis-woocommerce'); ?>">
            </div>
            <div class="line-g">
                <label><?php _e('End value', 'relais-colis-woocommerce'); ?></label>
                <input type="number" name="grilles[__GRILLE_INDEX__][lines][__LINE_INDEX__][max]" placeholder="<?php _e('Max', 'relais-colis-woocommerce'); ?>">
            </div>
            <div class="line-g">
                <label><?php _e('Price', 'relais-colis-woocommerce'); ?></label>
                <input type="number" step="0.01" name="grilles[__GRILLE_INDEX__][lines][__LINE_INDEX__][price]" placeholder="<?php _e('Price', 'relais-colis-woocommerce'); ?>">
            </div>
            <button type="button" class="remove-line">❌</button>
        </div>
        <?php
        return ob_get_clean();
    }
}
