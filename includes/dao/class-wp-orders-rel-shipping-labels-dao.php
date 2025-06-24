<?php

namespace RelaisColisWoocommerce\DAO;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;

/**
 * DAO for managing the rc_orders_rel_shipping_labels table.
 * Handles the relationship between WooCommerce orders and shipping labels.
 *
 * @since 1.0.0
 */
class WP_Orders_Rel_Shipping_Labels_DAO {

    use Singleton;

    private $table_name;

    /**
     * Class constructor.
     * Initializes the table name dynamically using the WordPress prefix.
     */
    protected function __construct() {

        global $wpdb;
        $this->table_name = $wpdb->prefix.'rc_orders_rel_shipping_labels';
    }

    /**
     * Retrieve the shipping status linked to a given shipping label.
     *
     * @param string $shipping_label The unique shipping label.
     * @return int|null The order ID if found, null otherwise.
     */
    public function get_shipping_status_by_shipping_label( $shipping_label ) {

        global $wpdb;

        $sql = "SELECT shipping_status FROM {$this->table_name} WHERE shipping_label = %s LIMIT 1";
        $shipping_status = $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->prepare( $sql, $shipping_label ) );

        return $shipping_status ? $shipping_status : null;
    }

    /**
     * Retrieve the order ID linked to a given shipping label.
     *
     * @param string $shipping_label The unique shipping label.
     * @return int|null The order ID if found, null otherwise.
     */
    public function get_order_id_by_shipping_label( $shipping_label ) {

        global $wpdb;

        $sql = "SELECT order_id FROM {$this->table_name} WHERE shipping_label = %s LIMIT 1";
        $order_id = $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->prepare( $sql, $shipping_label ) );

        return $order_id ? intval( $order_id ) : null;
    }

    /**
     * Deletes all records related to a specific order ID, as well as orphaned entries
     * whose order_id no longer exists in the WooCommerce orders table.
     *
     * @param int $order_id The WooCommerce order ID that was deleted.
     */
    public function delete_shipping_labels_for_order_and_orphans( $order_id ) {

        global $wpdb;

        // Delete all entries directly linked to the given order ID
        $wpdb->delete(
            $this->table_name,
            [ 'order_id' => $order_id ],
            [ '%d' ]
        );

        // Find all order_ids in the table
        $all_order_ids = $wpdb->get_col( "SELECT DISTINCT order_id FROM {$this->table_name}" );

        if ( empty( $all_order_ids ) ) {
            return;
        }

        foreach ( $all_order_ids as $maybe_orphan_id ) {
            if ( ! wc_get_order( $maybe_orphan_id ) ) {
                $wpdb->delete(
                    $this->table_name,
                    [ 'order_id' => $maybe_orphan_id ],
                    [ '%d' ]
                );
            }
        }
    }

    /**
     * Insert a new relation between an order ID and a shipping label.
     * The initial shipping status is set to STATUS_RC_COLIS_ANNONCE.
     *
     * @param int $order_id The WooCommerce order ID.
     * @param string $shipping_label The shipping label associated with the order.
     * @return bool True if inserted successfully, false otherwise.
     */
    public function insert_shipping_label( $order_id, $shipping_label ) {

        global $wpdb;

        $data = [
            'order_id' => $order_id,
            'shipping_label' => $shipping_label,
            'shipping_status' => WC_RC_Shipping_Constants::STATUS_RC_COLIS_ANNONCE, // Default status
            'last_updated' => current_time( 'mysql' ), // Set timestamp
        ];

        $format = [ '%d', '%s', '%s', '%s' ];
        $inserted = $wpdb->insert( $this->table_name, $data, $format );

        return ( $inserted !== false );
    }

    /**
     * Update the shipping status based on the shipping label.
     *
     * @param string $shipping_label The unique shipping label.
     * @param string $new_status The new shipping status.
     * @return bool True if updated successfully, false otherwise.
     */
    public function update_shipping_status( $shipping_label, $new_status ) {

        global $wpdb;

        $data = [
            'shipping_status' => $new_status,
            'last_updated' => current_time( 'mysql' ), // Update timestamp
        ];
        $where = [ 'shipping_label' => $shipping_label ];
        $format = [ '%s', '%s' ];
        $where_format = [ '%s' ];

        $updated = $wpdb->update( $this->table_name, $data, $where, $format, $where_format );

        return ( $updated !== false );
    }

    /**
     * Retrieve all orders where last_updated is older than 1 day
     * and the shipping_status is NOT status_rc_echec_livraison or status_rc_livre.
     *
     * @return array List of order IDs.
     */
    public function get_orders_pending_update() {

        global $wpdb;

        $five_hours_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-5 hours' ) );

        $sql = "
            SELECT * 
            FROM {$this->table_name}
            WHERE last_updated < %s
            AND shipping_status NOT IN (%s, %s)
        ";

        $query = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql,
            $five_hours_ago,
            WC_RC_Shipping_Constants::STATUS_RC_LIVRE,
            WC_RC_Shipping_Constants::STATUS_RC_ECHEC_LIVRAISON
        );

        $results = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query, ARRAY_A );

        return $results;
    }

    /**
     * Update the shipping label PDF path based on the shipping label.
     *
     * @param string $shipping_label The unique shipping label.
     * @param string $pdf_path The file path of the shipping label PDF.
     * @return bool True if updated successfully, false otherwise.
     */
    public function update_shipping_label_pdf( $shipping_label, $pdf_path ) {

        global $wpdb;

        $data = [
            'shipping_label_pdf' => $pdf_path,
            'last_updated' => current_time( 'mysql' ), // Update timestamp
        ];
        $where = [ 'shipping_label' => $shipping_label ];
        $format = [ '%s', '%s' ];
        $where_format = [ '%s' ];

        $updated = $wpdb->update( $this->table_name, $data, $where, $format, $where_format );

        return ( $updated !== false );
    }

    /**
     * Check if there are any orders pending update.
     *
     * @return bool True if at least one order needs an update, false otherwise.
     */
    public function has_orders_pending_update() {

        global $wpdb;

        $one_day_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) );

        $sql = "
            SELECT EXISTS (
                SELECT 1 FROM {$this->table_name}
                WHERE last_updated < %s
                AND shipping_status NOT IN (%s, %s)
                LIMIT 1
            )
        ";

        $query = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql,
            $one_day_ago,
            WC_RC_Shipping_Constants::STATUS_RC_LIVRE,
            WC_RC_Shipping_Constants::STATUS_RC_ECHEC_LIVRAISON
        );

        return (bool)$wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query );
    }

    /**
     * Retrieve all shipping label information for a given order ID.
     *
     * @param int $order_id The WooCommerce order ID.
     * @return array|null An associative array of shipping label details or null if not found.
     */
    public function get_shipping_labels_by_order_id( $order_id ) {

        global $wpdb;

        $sql = "
        SELECT * FROM {$this->table_name}
        WHERE order_id = %d
    ";

        $query = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql, $order_id );
        $results = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query, ARRAY_A );

        return !empty( $results ) ? $results : null;
    }
}