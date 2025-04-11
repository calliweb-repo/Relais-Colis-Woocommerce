<?php

namespace RelaisColisWoocommerce\Cron;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * This class manages the update order cron job.
 *
 * @since 1.0.0
 */
class WP_Cron_Manager {

    use Singleton;

    public function __construct() {
        add_filter('cron_schedules', array($this, 'add_cron_interval'));
        add_action('schedule_update_order', array($this, 'execute'));
    }

    public function activate() {
        if (!wp_next_scheduled('schedule_update_order')) {
            wp_schedule_event(time(), 'six_times_per_day', 'schedule_update_order');
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook('schedule_update_order');
    }

    public function execute() {
        // TODO: Implement execute() method.
    }

    function add_cron_interval($schedules) {
        $schedules['six_times_per_day'] = array(
            'interval' => 86400 / 6,
            'display'  => 'Six fois par jour'
        );
        return $schedules;
    }
}
