<?php

namespace RelaisColisWoocommerce\DAO;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * This class manages the WooCommerce products
 *
 * @since 1.0.0
 * 
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * This class is a DAO (Data Access Object) that requires direct database access.
 * The caching is implemented manually using wp_cache_* functions.
 */
class WP_Products_DAO {

    use Singleton;

    private $cache_group = 'rc_products';

    /**
     * Load all products from DB
     * @return array
     */
    public function get_product_list( $post_title_filter = null, $limit = 50, $service_id = null ) {
        global $wpdb;

        // Define table names
        $table_posts = $wpdb->posts;
        $table_services_rel_products = $wpdb->prefix.'rc_services_rel_products';

        // Start SQL query
        $sql = "
        SELECT p.ID, p.post_title 
        FROM {$table_posts} AS p
        WHERE p.post_type = 'product' 
        AND p.post_status = 'publish'
    ";

        $params = [];

        // Exclude already assigned products if service_id is provided
        if ( !is_null( $service_id ) ) {
            $sql .= " AND p.ID NOT IN (SELECT product_id FROM {$table_services_rel_products} WHERE service_id = %d)";
            $params[] = $service_id;
        }

        // Apply post_title filter if provided
        if ( !is_null( $post_title_filter ) ) {
            $sql .= " AND p.post_title LIKE %s";
            $params[] = '%'.$wpdb->esc_like( $post_title_filter ).'%';
        }

        // Limit the number of results
        $sql .= " LIMIT %d";
        $params[] = $limit;

        // Prepare SQL statement
        $statement = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql, ...$params );

        // Execute query
        $products = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $statement );

        // Convert results into an associative array
        $product_options = [];
        foreach ( $products as $product ) {
            $product_options[ $product->ID ] = $product->post_title;
        }

        return $product_options;
    }
}