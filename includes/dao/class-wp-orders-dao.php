<?php

namespace RelaisColisWoocommerce\DAO;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Method_Home;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Method_Homeplus;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Method_Relay;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * This class manages the legacy (post) orders tables and its related data.
 *
 * @since 1.0.0
 */
class WP_Orders_DAO {

    use Singleton;

    /**
     * Get orders with Relais Colis method and RC status other than STATUS_RC_LIVRE or STATUS_RC_ECHEC_LIVRAISON, and which have not been updated since 1 day
     * @return array list of orders
     */
    public function get_rc_orders_to_be_updated() {

        global $wpdb;
        $table_postmeta = $wpdb->prefix.'postmeta';

        // SQL
        $sql = '
            SELECT DISTINCT pm1.post_id 
            FROM '.$table_postmeta.' pm1
            INNER JOIN '.$table_postmeta.' pm2 ON pm1.post_id = pm2.post_id
            INNER JOIN '.$table_postmeta.' pm3 ON pm1.post_id = pm3.post_id
            WHERE 
                pm1.meta_key = %s 
                AND pm1.meta_value NOT IN (%s, %s)
            
                AND pm2.meta_key =%s
                AND pm2.meta_value IN (%s, %s, %s)
            
                AND pm3.meta_key = %s
                AND pm3.meta_value < %s
            )
        ';

        $params = array(
            WC_RC_Shipping_Constants::OPTION_RC_ORDER_STATUS,
            WC_RC_Shipping_Constants::STATUS_RC_LIVRE,
            WC_RC_Shipping_Constants::STATUS_RC_ECHEC_LIVRAISON,
            WC_RC_Shipping_Constants::OPTION_RC_SHIPPING_METHOD,
            WC_RC_Shipping_Method_Relay::WC_RC_SHIPPING_METHOD_RELAY_ID,
            WC_RC_Shipping_Method_Home::WC_RC_SHIPPING_METHOD_HOME_ID,
            WC_RC_Shipping_Method_Homeplus::WC_RC_SHIPPING_METHOD_HOMEPLUS_ID,
            WC_RC_Shipping_Constants::OPTION_RC_ORDER_STATUS_LAST_UPDATE,
            date('Y-m-d H:i:s', strtotime('-1 day'))
        );

        $prepared_query = $wpdb->prepare( $sql, $params );

        $results = $wpdb->get_col($prepared_query);
        return $results;
    }
}