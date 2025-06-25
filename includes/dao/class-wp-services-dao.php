<?php

namespace RelaisColisWoocommerce\DAO;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WC_RC_Services_Manager;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

/**
 * This class manages the services tables and its related data.
 *
 * @since 1.0.0
 * 
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * This class is a DAO (Data Access Object) that requires direct database access.
 * The caching is implemented manually using wp_cache_* functions.
 */
class WP_Services_DAO {

    use Singleton;

    /**
     * Initialize the rc_services table with default data.
     */
    public function initialize_rc_services() {

        global $wpdb;

        // Define table name
        $table_services = $wpdb->prefix.'rc_services';

        $this->delete_all_services();

        // Loop through each service and insert it into the database
        foreach ( WC_RC_Services_Manager::instance()->get_fixed_services() as $slug => $fixed_service ) {

            // Determine the delivery method (Home or Home+)
            $name = $fixed_service[ 0 ];
            $delivery_methods = $fixed_service[ 1 ];
            $delivery_method = implode( ', ', array_keys( $delivery_methods ) );

            $client_choice = "no";
            if($slug ==="delivery_to_floor" ||
                $slug === "quick_assembly" ||
                $slug === "removal_old_equipment"
            ){
                $client_choice = "yes";
            }
            // Prepare data for insertion
            $data = [
                'name' => sanitize_text_field( $name ),
                'slug' => sanitize_text_field( $slug ),
                'client_choice' => $client_choice,
                'delivery_method' => sanitize_text_field( $delivery_method ),
                'enabled' => 'yes',
                'price' => 0.00, // Default price to 0.00
            ];

            // Insert data into the table
            $wpdb->insert( $table_services, $data );
        }
    }

    /**
     * Get a price by service slug
     * @param $slug
     * @return float
     */
    public function get_service_price_by_slug( $slug ) {

        global $wpdb;

        // Define table name
        $table_services = $wpdb->prefix.'rc_services';

        // Secure query with prepared statement
        $query = $wpdb->prepare( "
        SELECT price 
        FROM {$table_services}
        WHERE slug = %s 
        AND enabled = 'yes'
        LIMIT 1
    ", $slug );

        // Fetch the price
        $price = $wpdb->get_var(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query );

        return ( $price !== null ) ? floatval( $price ) : 0.00; // Ensure it's a valid float value
    }

    /**
     * Get data from rc_services with optional filtering by service ID.
     *
     * @param int|null $service_id Optional. Filter by a specific service ID.
     * @return array Results as an associative array.
     */
    public function get_services( $service_id = null ) {

        global $wpdb;
        $table_services = $wpdb->prefix.'rc_services';

        $query = "SELECT * FROM {$table_services}";

        if ( $service_id ) {

            $query .= $wpdb->prepare( " WHERE id = %d", $service_id );
        }
        $results = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query, ARRAY_A );
        foreach ( $results as &$result ) {

            $result['delivery_method'] = explode( ',', $result['delivery_method'] );
        }
        return $results;
    }

    /**
     * Get data from rc_services_rel_products for a given service_id or product_id.
     *
     * @param int|null $service_id Optional. Filter by a specific service ID.
     * @param int|null $product_id Optional. Filter by a specific product ID.
     * @return array Results as an associative array.
     */
    public function get_service_relations( $service_id = null, $product_id = null ) {

        global $wpdb;
        $table_services_rel_products = $wpdb->prefix.'rc_services_rel_products';

        $query = "SELECT * FROM {$table_services_rel_products}";

        if ( $service_id ) {

            $query .= $wpdb->prepare( " WHERE service_id = %d", $service_id );

        } elseif ( $product_id ) {

            $query .= $wpdb->prepare( " WHERE product_id = %d", $product_id );
        }
        return $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query, ARRAY_A );
    }

    /**
     * Load selected product ids for a service
     * @param $service_id
     * @return array
     */
    public function get_selected_products( $service_id ) {

        global $wpdb;
        $table_services_rel_products = $wpdb->prefix.'rc_services_rel_products';
        $table_posts = $wpdb->prefix.'posts';

        $query = "SELECT p.ID as product_id, p.post_title 
              FROM {$table_services_rel_products} rel
              JOIN {$table_posts} p ON rel.product_id = p.ID
              WHERE rel.service_id = %d AND p.post_type = 'product' AND p.post_status = 'publish'";

        $results = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->prepare( $query, $service_id ), ARRAY_A );

        // Pretreat response
        $products = array();
        foreach ( $results as $result ) {

            $products[ $result[ 'product_id' ] ] = $result[ 'post_title' ];
        }

        WP_Log::debug( __METHOD__, [ '$service_id' => $service_id, '$results' => $results, '$products' => $products ], 'relais-colis-woocommerce' );

        return $products;
    }

    /**
     * Insert a new service into rc_services.
     *
     * @param string $name The name of the service.
     * @param string $slug The slug of the service.
     * @param bool $client_choice Whether the client can choose this service, yes or no
     * @param array $delivery_method_list The delivery method associated with the service.
     * @param bool $enabled Whether the service is enabled or not, yes or no
     * @param float $price The price of the service.
     * @return int|false Inserted row ID on success, false on failure.
     */
    public function insert_service( $name, $slug, $client_choice, $delivery_method_list, $enabled, $price ) {

        global $wpdb;
        $table_services = $wpdb->prefix.'rc_services';

        $delivery_method = implode( ',', $delivery_method_list );

        // Data to insert into the table
        $data = [
            'name' => sanitize_text_field( $name ),
            'slug' => sanitize_text_field( $slug ),
            'client_choice' => ( $client_choice === 'yes' ? 'yes' : 'no' ),
            'delivery_method' => sanitize_text_field( $delivery_method ),
            'enabled' => ( $enabled === 'yes' ? 'yes' : 'no' ),
            'price' => floatval( $price ),
        ];

        // Insert data into the database
        $inserted = $wpdb->insert( $table_services, $data );

        // Return the inserted row ID or false on failure
        return $inserted ? $wpdb->insert_id : false;
    }

    /**
     * Update data in rc_services.
     *
     * @param int $service_id The ID of the service to update.
     * @param string $name The name of the service.
     * @param string $slug The slug of the service.
     * @param bool $client_choice Whether the client can choose this service, yes or no
     * @param array $delivery_method_list The delivery method associated with the service.
     * @param bool $enabled Whether the service is enabled or not, yes or no
     * @param float $price The price of the service.
     * @return int|false Rows affected on success, false on failure.
     */
    public function update_service( $service_id, $name, $slug, $client_choice, $delivery_method_list, $enabled, $price ) {

        global $wpdb;
        $table_services = $wpdb->prefix.'rc_services';

        $delivery_method = implode( ',', $delivery_method_list );

        WP_Log::debug( __METHOD__, [ '$service_id' => $service_id, '$name' => $name, '$slug' => $slug, '$client_choice' => $client_choice, '$delivery_method' => $delivery_method, '$enabled' => $enabled, '$price' => $price ], 'relais-colis-woocommerce' );

        // Data to update in the table
        $data = [
            'name' => sanitize_text_field( $name ),
            'slug' => sanitize_text_field( $slug ),
            'client_choice' => ( $client_choice === 'yes' ? 'yes' : 'no' ),
            'delivery_method' => sanitize_text_field( $delivery_method ),
            'enabled' => ( $enabled === 'yes' ? 'yes' : 'no' ),
            'price' => floatval( $price ),
        ];

        // Condition for the update
        $where = [ 'id' => intval( $service_id ) ];
        WP_Log::debug( __METHOD__, [ '$data' => $data, '$where' => $where ], 'relais-colis-woocommerce' );

        // Update the table
        return $wpdb->update( $table_services, $data, $where );
    }

    /**
     * Update data in rc_services_rel_products for a given service_id.
     * Deletes all existing relations for the service ID, then inserts new ones.
     *
     * @param int $service_id The service ID to update.
     * @param array $product_ids Array of product IDs to relate to the service.
     * @return void
     */
    public function update_service_relations( $service_id, $product_ids ) {

        global $wpdb;
        $table_services_rel_products = $wpdb->prefix.'rc_services_rel_products';

        // Delete existing relations for this service ID
        $this->delete_service_relations( $service_id );

        // Insert new relations
        foreach ( $product_ids as $product_id ) {

            $wpdb->insert( $table_services_rel_products, [
                'service_id' => $service_id,
                'product_id' => $product_id,
            ] );
        }
    }

    /**
     * Delete all data from rc_services.
     *
     * @return int|false Number of rows deleted on success, false on failure.
     */
    public function delete_all_services() {

        global $wpdb;
        $table_services = $wpdb->prefix.'rc_services';
        $wpdb->query( $wpdb->prepare("TRUNCATE TABLE %i", $table_services) );
    }

    /**
     * Delete all relations in rc_services_rel_products for a given service_id.
     *
     * @param int $service_id The service ID to delete relations for.
     * @return int|false Number of rows deleted on success, false on failure.
     */
    public function delete_service_relations( $service_id = null ) {

        global $wpdb;
        $table_services_rel_products = $wpdb->prefix.'rc_services_rel_products';

        if ( $service_id ) {
            $wpdb->delete( $table_services_rel_products, [ 'service_id' => $service_id ], [ '%d' ] );
        } else {
            $wpdb->query( $wpdb->prepare("TRUNCATE TABLE %i", $table_services_rel_products) );
        }

    }

    /**
     * Get all available services for a method and given products (or not)
     * @param $delivery_method h, hp or rc
     * @param $product_ids product id list
     * @return array|object|\stdClass[]|null
     */
    public function get_available_services( $delivery_method, $product_ids ) {

        global $wpdb;
        $table_services = $wpdb->prefix.'rc_services';
        $table_services_rel_products = $wpdb->prefix.'rc_services_rel_products';

        // Build request to get services
        $query = "
        SELECT s.id, s.name, s.slug, s.price
        FROM {$table_services} s
        LEFT JOIN {$table_services_rel_products} rp ON rp.service_id = s.id
        WHERE s.client_choice = 'yes'
          AND s.enabled = 'yes'
          AND FIND_IN_SET(%s, s.delivery_method) > 0
    ";

        // Si le service est lié à des produits spécifiques, filtrer en fonction des produits dans le panier
        if ( !empty( $product_ids ) ) {
            $query .= " AND (rp.product_id IN (".implode( ',', array_map( 'intval', $product_ids ) ).") OR rp.product_id IS NULL)";
        }

        // Préparer et exécuter la requête
        $results = $wpdb->get_results( $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query, $delivery_method ), ARRAY_A );

        return $results;
    }
}