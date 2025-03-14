<?php

namespace RelaisColisWoocommerce\Shipping;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Order;

/**
 * Class WC_Emails_Orders_Manager
 *
 * This class is responsible for managing WooCommerce orders infos in emails
 *
 * @package   RelaisColisWoocommerce\Shipping
 * @author    Ludovic Maillet / Sukellos
 * @version   1.0.0
 * @since     1.0.0
 */
class WC_Emails_Orders_Manager {

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

        // Display infos on customer emails
        add_action( 'woocommerce_email_order_meta', array( $this, 'action_woocommerce_email_order_meta' ), 10, 3 );
    }

    /**
     * Display infos on customer order details page, in My account -> Orders -> Order page
     * @param $wc_order
     * @param $sent_to_admin
     * @param $plain_text
     * @return void
     */
    public function action_woocommerce_email_order_meta( $wc_order, $sent_to_admin, $plain_text ) {

        WP_Log::debug( __METHOD__, ['$wc_order'=>$wc_order, '$sent_to_admin'=>$sent_to_admin ], 'relais-colis-woocommerce' );

        // Check if order is defined
        if ( !$wc_order instanceof WC_Order ) {
            return;
        }

        WC_Order_Shipping_Infos_Manager::instance()->render_shipping_infos( $wc_order );
    }
}
