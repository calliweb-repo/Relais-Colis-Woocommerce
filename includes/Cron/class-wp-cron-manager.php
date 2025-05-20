<?php

namespace RelaisColisWoocommerce\Cron;

defined('ABSPATH') or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\DAO\WP_Orders_Rel_Shipping_Labels_DAO;
use RelaisColisWoocommerce\RCAPI\WP_RC_Get_Packages_Status;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API;
use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use WC_Order;

/**
 * This class manages the update order cron job.
 *
 * @since 1.0.0
 */
class WP_Cron_Manager
{

    use Singleton;

    public function __construct()
    {
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
        add_action('schedule_update_order', array($this, 'execute'));
    }

    public function activate()
    {
        if (!wp_next_scheduled('schedule_update_order')) {
            wp_schedule_event(time(), 'six_times_per_day', 'schedule_update_order');
        }
    }

    public function deactivate()
    {
        wp_clear_scheduled_hook('schedule_update_order');
    }

    public function execute()
    {
        // Get pending shipping status
        $orders_pending_update = WP_Orders_Rel_Shipping_Labels_DAO::instance()->get_orders_pending_update();
        WP_Log::debug(__METHOD__, ['$orders_pending_update' => $orders_pending_update], 'relais-colis-woocommerce');

        // If not empty, need to update a few shipping status
        if (empty($orders_pending_update)) return;

        // Call API
        try {
            // Get shipping labels
            $parcel_numbers = array();
            foreach ($orders_pending_update as $order_pending_update) {

                $parcel_numbers[] = $order_pending_update['shipping_label'];
            }
            $params = array(
                WP_RC_Get_Packages_Status::PARCEL_NUMBERS => $parcel_numbers,
            );

            $packages_status = WP_Relais_Colis_API::instance()->get_packages_status($params, false);

            if (is_null($packages_status)) {

                WP_Log::debug(__METHOD__ . ' - No response', [], 'relais-colis-woocommerce');
                return;
            }

            // Get RC statuses shipping_label=>shipping_status
            $rc_statuses = $packages_status->get_simplified_rc_statuses();
            WP_Log::debug(__METHOD__, ['rc_statuses' => $rc_statuses], 'relais-colis-woocommerce');

            // Update RC status for these orders, requesting RC API /api/package/getDataEvts endpoint
            foreach ($rc_statuses as $rc_shipping_label => $rc_status) {

                // Update the status iin DB
                WP_Orders_Rel_Shipping_Labels_DAO::instance()->update_shipping_status($rc_shipping_label, $rc_status);
            }
        } catch (WP_Relais_Colis_API_Exception $wp_relais_colis_api_exception) {

            WP_Log::debug(__METHOD__ . ' - Error response', ['code' => $wp_relais_colis_api_exception->getCode(), 'message' => $wp_relais_colis_api_exception->getMessage(), 'detail' => $wp_relais_colis_api_exception->get_detail()], 'relais-colis-woocommerce');
        }
    }

    function add_cron_interval($schedules)
    {
        $schedules['six_times_per_day'] = array(
            'interval' => 86400 / 6,
            'display'  => 'Six fois par jour'
        );
        return $schedules;
    }
}
