<?php

namespace RelaisColisWoocommerce\DAO;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\RCAPI\WP_Relais_Colis_API_Exception;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * This class manages the tariff grids tables and its related data.
 *
 * @since 1.0.0
 */
class WP_Tariff_Grids_DAO {

    use Singleton;

    /**
     * Insert a new tariff grid in DB
     * @param $method_name method name can be Home, Home+ or Relais Colis
     * @param $criteria one of TARIFF_CRITERIA_PRICE or TARIFF_CRITERIA_WEIGHT
     * @param $min_value minimal value
     * @param $max_value maximal value
     * @param $price price
     * @return void
     */
    public function insert_tariff_grid( $method_name, $criteria, $min_value, $max_value, $price, $shipping_threshold ) {

        global $wpdb;
        $table_name = $wpdb->prefix.'rc_tariff_grids';

        // Manage NULL value for max_value (unlimited max)
        $max_value = ( $max_value === '' || $max_value === null ) ? null : floatval( $max_value );

        // Check if conflict
        if ( $this->check_tariff_conflict( $method_name, $criteria, $min_value, $max_value ) ) {

            // Pb occured... criteria conflict
            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::TARIFF_GRIDS_CRITERIA_CONFLICT)), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[ WP_Relais_Colis_API_Exception::TARIFF_GRIDS_CRITERIA_CONFLICT ]) );
        }

        if ( is_null( $shipping_threshold ) || $shipping_threshold =="" ) {
            $shipping_threshold = null;
        } else {
            $shipping_threshold = floatval( $shipping_threshold );
        }

        $wpdb->insert(
            $table_name,
            [
                'method_name' => sanitize_text_field( $method_name ),
                'criteria' => sanitize_text_field( $criteria ),
                'min_value' => floatval( $min_value ),
                'max_value' => $max_value,
                'price' => floatval( $price ),
                'shipping_threshold' => $shipping_threshold,
            ],
            [ '%s', '%s', '%f', ( $max_value === null ? 'NULL' : '%f' ), '%f', '%f' ]
        );
    }

    /**
     * Get a new tariff grid in DB
     * @param $method_name method name can be Home, Home+ or Relais Colis
     * @param $criteria_value the criteria value
     * @param $criteria_type one of TARIFF_CRITERIA_PRICE or TARIFF_CRITERIA_WEIGHT
     * @return string|null
     */
    public function get_shipping_price( $method_name, $criteria_value, $criteria_type ) {

        global $wpdb;
        $table_name = $wpdb->prefix.'rc_tariff_grids';

        $query = $wpdb->prepare( "
            SELECT price FROM $table_name
            WHERE method_name = %s
            AND criteria = %s
            AND min_value <= %f
            AND (
                    ( max_value IS NULL ) OR 
                    ( max_value IS NOT NULL AND max_value >= %f) 
                    )
            LIMIT 1
        ", $method_name, $criteria_type, $criteria_value, $criteria_value );

        return $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query );
    }

    /**
     * Checks if an interval already exists and overlaps with an existing rule.
     *
     * Three cases of overlap are covered:
     * 1. min_value of the new rule is in an existing rule.
     * 2. max_value of the new rule is in an existing rule.
     * 3. The new rule completely encompasses an existing rule.
     *
     * If a rule already exists, the function returns true (conflict detected).
     *
     * @param $method_name method name can be Home, Home+ or Relais Colis
     * @param $criteria one of TARIFF_CRITERIA_PRICE or TARIFF_CRITERIA_WEIGHT
     * @param $min_value minimal value
     * @param $max_value maximal value
     * @return bool
     */
    public function check_tariff_conflict( $method_name, $criteria, $min_value, $max_value ) {

        global $wpdb;
        $table_name = $wpdb->prefix.'rc_tariff_grids';

        // Check if another criteria already exists for the same method
        $existing_criteria = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT criteria FROM {$table_name} WHERE method_name = %s",
            $method_name
        ));

        if ( !empty($existing_criteria) && !in_array($criteria, $existing_criteria, true) ) {
            WP_Log::debug( __METHOD__, [
                'criteria_conflict' => 'Another criteria already exists for this method_name',
                '$method_name' => $method_name,
                '$existing_criteria' => $existing_criteria,
                '$new_criteria' => $criteria,
            ], 'relais-colis-woocommerce' );

            return true;
        }

        // Query with not null max
        if ( !is_null( $max_value ) ) {

            $query = $wpdb->prepare( "
                SELECT COUNT(*) FROM $table_name
                WHERE method_name = %s
                AND criteria = %s
                AND (
                    ( max_value IS NULL ) OR 
                    ( max_value IS NOT NULL AND max_value > %f) 
                    )
            ", $method_name, $criteria, $min_value );
        } else {
            $query = $wpdb->prepare( "
                SELECT COUNT(*) FROM $table_name
                WHERE method_name = %s
                AND criteria = %s
                AND (
                    ( max_value IS NULL ) OR 
                    ( max_value IS NOT NULL AND max_value > %f) 
                    )
            ", $method_name, $criteria, $min_value );
        }

        $is_conflict = $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query ) > 0;

        WP_Log::debug( __METHOD__, [
            'query' => $query,
            '$method_name' => $method_name,
            '$criteria' => $criteria,
            '$min_value' => $min_value,
            '$max_value' => (is_null($max_value)?'NULL':$max_value),
            '$is_conflict' => ($is_conflict?'true':'false'),
        ], 'relais-colis-woocommerce' );

        return $is_conflict;
    }

    /**
     * Delete all data from rc_tariff_grids.
     *
     * @return int|false Number of rows deleted on success, false on failure.
     */
    public function delete_all_tariff_grids() {

        global $wpdb;
        $table_name = $wpdb->prefix.'rc_tariff_grids';
        return $wpdb->query( "TRUNCATE TABLE {$table_name}" );
    }

    /**
     * Load all tariff grids
     * @return array|object|\stdClass[]|null
     */
    public function get_all_tariff_grids() {

        global $wpdb;
        $table_name = $wpdb->prefix.'rc_tariff_grids';

        $query = $wpdb->prepare("SELECT * FROM %i ORDER BY method_name, min_value ASC", $table_name);
        return $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query, ARRAY_A );
    }

    /**
     * Load all tariff grids grouped by method / criteria
     * @return array
     */
    public function get_grouped_tariff_grids() {

        global $wpdb;
        $table_name = $wpdb->prefix.'rc_tariff_grids';

        $query = $wpdb->prepare("SELECT * FROM %i ORDER BY method_name, min_value ASC", $table_name);
        $results = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query, ARRAY_A );

        $grouped_tariffs = [];

        foreach ( $results as $row ) {
            $key = $row[ 'method_name' ].'|'.$row[ 'criteria' ];

            if ( !isset( $grouped_tariffs[ $key ] ) ) {

                $grouped_tariffs[ $key ] = [
                    'method_name' => $row[ 'method_name' ],
                    'criteria' => $row[ 'criteria' ],
                    'shipping_threshold' => $row[ 'shipping_threshold' ],
                    'lines' => []
                ];
            }

            $grouped_tariffs[ $key ][ 'lines' ][] = [
                'min_value' => $row[ 'min_value' ],
                'max_value' => $row[ 'max_value' ],
                'price' => $row[ 'price' ]
            ];
        }


        return $grouped_tariffs;
    }

    /**
     * Get the shipping threshold for a given method and criteria
     * @param $method_name method name can be Home, Home+ or Relais Colis
     * @param $criteria one of TARIFF_CRITERIA_PRICE or TARIFF_CRITERIA_WEIGHT
     * @return float|null
     */
    public function get_shipping_threshold( $method_name, $criteria_type ) {

        global $wpdb;
        $table_name = $wpdb->prefix.'rc_tariff_grids';

        $query = $wpdb->prepare( "
            SELECT shipping_threshold FROM %i
            WHERE method_name = %s
            AND criteria = %s
            ORDER BY min_value ASC
            LIMIT 1
        ", $table_name, $method_name, $criteria_type );

        return $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query );
    }
}