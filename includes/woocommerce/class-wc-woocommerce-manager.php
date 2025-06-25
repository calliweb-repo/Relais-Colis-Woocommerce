<?php

namespace RelaisColisWoocommerce;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * WooCommerce Manager.
 *
 * @since     1.0.0
 */
class WC_WooCommerce_Manager {

    // Use Trait Singleton
    use Singleton;

    // TEST
    private static $hook_list = array();

    /**
     * Default init method called when instance created
     * This method can be overridden if needed.
     *
     * @since 1.0.0
     * @access protected
     */
    public function init() {

        // TEST
        add_action( 'woocommerce_init', function () {
            if ( $this->is_woocommerce_checkout_page_fse() ) WP_Log::debug( __METHOD__.' -  is_woocommerce_checkout_page_fse TRUE', [], 'relais-colis-woocommerce' );
            else  WP_Log::debug( __METHOD__.' -  is_woocommerce_checkout_page_fse FALSE', [], 'relais-colis-woocommerce' );

            if ( $this->is_woocommerce_checkout_page_old_shortcode() ) WP_Log::debug( __METHOD__.' -  is_woocommerce_checkout_page_old_shortcode TRUE', [], 'relais-colis-woocommerce' );
            else  WP_Log::debug( __METHOD__.' -  is_woocommerce_checkout_page_old_shortcode FALSE', [], 'relais-colis-woocommerce' );

            if ( $this->is_woocommerce_fse_checkout_enabled() ) WP_Log::debug( __METHOD__.' -  is_woocommerce_fse_checkout_enabled TRUE', [], 'relais-colis-woocommerce' );
            else  WP_Log::debug( __METHOD__.' -  is_woocommerce_fse_checkout_enabled FALSE', [], 'relais-colis-woocommerce' );

        });

    }

    /**
     * Check if HPOS is enabled
     * @return void
     */
    public function is_hpos_enabled() {

        if( OrderUtil::custom_orders_table_usage_is_enabled() ) {

            // HPOS is enabled.
            WP_Log::debug( __METHOD__.' - HPOS is enabled', [], 'relais-colis-woocommerce' );
            return true;

        } else {

            // CPT-based orders are in use.
            WP_Log::debug( __METHOD__.' - Legacy CPT-based orders are in use', [], 'relais-colis-woocommerce' );
            return false;
        }
    }

    /**
     * Using hooks to disable WooCommerce FSE checkout mode
     * Must be called before woocommerce_get_settings_checkout and woocommerce_checkout_block_theme trigger
     */
    public function disable_woocommerce_checkout_fse() {

        // Deactivate new WooCommerce (Full Site Editing - FSE) Blocs (Gutenberg)
        // And recreate checkout page with shortcode [woocommerce_checkout]
        add_filter( 'woocommerce_get_settings_checkout', function ( $settings ) {

            $settings[] = [
                'id' => 'woocommerce_checkout_block_theme',
                'type' => 'hidden',
                'default' => 'no',
            ];
            return $settings;

        }, 9999 );
        add_filter( 'woocommerce_blocks_checkout_enabled', '__return_false' );
    }

    /**
     * Check if the FSE Full Site Editing for WooCommerce is enabled
     * @return bool true if FSE, otherwise false
     */
    public function is_woocommerce_checkout_page_fse() {

        // Get checkout post id
        $checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
        WP_Log::debug( __METHOD__.' - Get Option woocommerce_checkout_page_id', [ 'woocommerce_checkout_page_id' => $checkout_page_id ], 'relais-colis-woocommerce' );

        // Only if checkout post id exists
        if ( $checkout_page_id === false ) return false;

        // Get checkout post content
        $post_content = get_post_field( 'post_content', $checkout_page_id );
        WP_Log::debug( __METHOD__, [ '$post_content' => $post_content ], 'relais-colis-woocommerce' );

        // Check if WooCommerce checkout page contains the checkout bloc
        if ( has_block( 'woocommerce/checkout', $post_content ) ) {

            WP_Log::debug( __METHOD__.' - has_block woocommerce/checkout TRUE', [], 'relais-colis-woocommerce' );
            return true;
        } else {

            WP_Log::debug( __METHOD__.' - has_block woocommerce/checkout FALSE', [], 'relais-colis-woocommerce' );
        }

        return false;
    }

    /**
     * Check if the FSE Full Site Editing for WooCommerce is enabled
     * @return bool true if FSE, otherwise false
     */
    public function is_woocommerce_checkout_page_old_shortcode() {

        // Get checkout post id
        $checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
        WP_Log::debug( __METHOD__.' - Get Option woocommerce_checkout_page_id', [ 'woocommerce_checkout_page_id' => $checkout_page_id ], 'relais-colis-woocommerce' );

        // Only if checkout post id exists
        if ( $checkout_page_id === false ) return false;

        // Get checkout post content
        $post_content = get_post_field( 'post_content', $checkout_page_id );
        WP_Log::debug( __METHOD__, [ '$post_content' => $post_content ], 'relais-colis-woocommerce' );

        // Search for classical [woocommerce_checkout] in post content
        if ( strpos( $post_content, '[woocommerce_checkout]' ) !== false ) {

            return true;
        }
        return false;
    }

    /**
     * Check if the FSE Full Site Editing for WooCommerce is enabled
     * @return bool true if FSE, otherwise false
     */
    public function is_woocommerce_fse_checkout_enabled() {

        $wc_current_theme_is_fse_theme = function_exists( 'wc_current_theme_is_fse_theme' ) && wc_current_theme_is_fse_theme();
        WP_Log::debug( __METHOD__.' -  function_exists wc_current_theme_is_fse_theme', [ 'wc_current_theme_is_fse_theme' => ( $wc_current_theme_is_fse_theme ? 'true' : 'false' ) ], 'relais-colis-woocommerce' );
        if ( $wc_current_theme_is_fse_theme ) return true;

        $wc_current_theme_supports_woocommerce_or_fse = function_exists( 'wc_current_theme_supports_woocommerce_or_fse' ) && wc_current_theme_supports_woocommerce_or_fse();
        WP_Log::debug( __METHOD__.' -  function_exists wc_current_theme_supports_woocommerce_or_fse', [ 'wc_current_theme_supports_woocommerce_or_fse' => ( $wc_current_theme_supports_woocommerce_or_fse ? 'true' : 'false' ) ], 'relais-colis-woocommerce' );
        if ( $wc_current_theme_supports_woocommerce_or_fse ) return true;

        $wc_current_theme_supports = function_exists( 'wc_current_theme_supports' ) && wc_current_theme_supports( 'block-based-checkout' );
        WP_Log::debug( __METHOD__.' -  function_exists wc_current_theme_supports', [ 'wc_current_theme_supports block-based-checkout' => ( $wc_current_theme_supports ? 'true' : 'false' ) ], 'relais-colis-woocommerce' );
        if ( $wc_current_theme_supports ) return true;

        return false;
    }
}
