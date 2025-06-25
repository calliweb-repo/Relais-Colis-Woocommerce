<?php

namespace RelaisColisWoocommerce\DAO;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\RCAPI\WP_RC_Get_Configuration_Response;

/**
 * This class manages the `rc_configuration` table and its related data.
 *
 * @since 1.0.0
 */
class WP_Configuration_DAO {

    use Singleton;

    /**
     * Insert a new enseigne configuration as WordPress options, and its options into the database.
     *
     * @param WP_RC_Get_Configuration_Response $response The enseigne configuration response object.
     * @return int|false The inserted activation key ID, or false on failure.
     */
    public function replace_rc_get_configuration_data( WP_RC_Get_Configuration_Response $response ) {

        // Extract data from the response object
        $enseigne_id = sanitize_text_field( $response->get_ens_id() );
        $enseigne_id_light = sanitize_text_field( $response->get_ens_id_light() );
        $enseigne_nom = sanitize_text_field( $response->get_ens_name() );
        $activation_key = sanitize_text_field( $response->get_activation_key() );
        $active = $response->is_active();
        $useidens = $response->use_id_ens();
        $address_line1 = sanitize_text_field( $response->get_address1() );
        $address_line2 = sanitize_text_field( $response->get_address2() );
        $postal_code = sanitize_text_field( $response->get_postcode() );
        $city = sanitize_text_field( $response->get_city() );
        $livemapping_api = sanitize_text_field( $response->get_livemapping_api() );
        $livemapping_pid = sanitize_text_field( $response->get_livemapping_pid() );
        $livemapping_key = sanitize_text_field( $response->get_livemapping_key() );
        $folder = sanitize_text_field( $response->get_folder() );
        $return_version = sanitize_text_field( $response->get_return_version() );
        $return_login = sanitize_text_field( $response->get_return_login() );
        $return_pass = sanitize_text_field( $response->get_return_pass() );
        $agency_code = sanitize_text_field( $response->get_agency_code() );
        $return_site = sanitize_text_field( $response->get_return_site() );
        $updated_by = absint( $response->get_updated_by() );
        $created_at = gmdate('Y-m-d H:i:s', strtotime($response->get_created_at()));
        $updated_at = gmdate('Y-m-d H:i:s', strtotime($response->get_updated_at()));
        $osm_live_mapping_key = sanitize_text_field( $response->get_osm_live_mapping_key() );
        $osm_live_mapping_ens = sanitize_text_field( $response->get_osm_live_mapping_ens() );

        // RC Configuration stored as options
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID, $enseigne_id );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID_LIGHT, $enseigne_id_light );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_NOM, $enseigne_nom );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ACTIVATION_KEY, $activation_key );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ACTIVE, $active );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_USEIDENS, $useidens );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE1, $address_line1 );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE2, $address_line2 );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_POSTAL_CODE, $postal_code );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_CITY, $city );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_API, $livemapping_api );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_PID, $livemapping_pid );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_KEY, $livemapping_key );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_FOLDER, $folder );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_VERSION, $return_version );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_LOGIN, $return_login );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_PASS, $return_pass );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_AGENCY_CODE, $agency_code );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_SITE, $return_site );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_BY, $updated_by );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_CREATED_AT, $created_at );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_AT, $updated_at );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_OSM_LIVEMAPPING_KEY, $osm_live_mapping_key );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_OSM_LIVEMAPPING_ENS, $osm_live_mapping_ens );

        // Insert related options
        global $wpdb;
        $options = $response->get_options();

        // Define table names
        $rc_configuration_options_table = $wpdb->prefix.'rc_configuration_options';

        if ( !empty( $options ) ) {

            // First empty the table
            $wpdb->query( "TRUNCATE TABLE {$rc_configuration_options_table}" );

            foreach ( $options as $option ) {

                $option_id = sanitize_text_field( $option[ WC_RC_Shipping_Constants::CONFIGURATION_OPTION_ID ] );
                $name = sanitize_text_field( $option[ WC_RC_Shipping_Constants::CONFIGURATION_OPTION_NAME ] );
                $value = sanitize_text_field( $option[ WC_RC_Shipping_Constants::CONFIGURATION_OPTION_VALUE ] );
                $active = filter_var( $option[ WC_RC_Shipping_Constants::CONFIGURATION_OPTION_ACTIVE ], FILTER_VALIDATE_BOOLEAN );

                $result = $wpdb->insert(
                    $rc_configuration_options_table,
                    [
                        'option_'.WC_RC_Shipping_Constants::CONFIGURATION_OPTION_ID => $option_id,
                        WC_RC_Shipping_Constants::CONFIGURATION_OPTION_NAME => $name,
                        WC_RC_Shipping_Constants::CONFIGURATION_OPTION_VALUE => $value,
                        WC_RC_Shipping_Constants::CONFIGURATION_OPTION_ACTIVE => $active,
                    ],
                    [ '%d', '%s', '%s', '%d' ]
                );

                if ( $result === false ) {
                    WP_Log::debug( __METHOD__.' - Failed to insert rc_configuration_option: '.$wpdb->last_error, [], 'relais-colis-woocommerce' );
                }
            }
        }
    }

    /**
     * Delete overall enseigne configuration as WordPress options, and its options into the database.
     *
     * @return int|false The inserted activation key ID, or false on failure.
     */
    public function delete_rc_get_configuration_data() {

        // RC Configuration stored as options
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID_LIGHT );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_NOM );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ACTIVE );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_USEIDENS );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ACTIVATION_KEY );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE1 );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE2 );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_POSTAL_CODE );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_CITY );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_API );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_PID );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_KEY );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_FOLDER );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_VERSION );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_LOGIN );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_PASS );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_AGENCY_CODE );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_SITE );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_BY );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_CREATED_AT );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_AT );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_OSM_LIVEMAPPING_KEY );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::CONFIGURATION_OSM_LIVEMAPPING_ENS );

        // Delete related options
        global $wpdb;

        // Define table names
        $rc_configuration_options_table = $wpdb->prefix.'rc_configuration_options';

        $wpdb->query( "TRUNCATE TABLE {$rc_configuration_options_table}" );
    }

    /**
     * Unique and simple access point to retrieve all options related to rc_configuration
     * @param $view true to get only fields which must be displayed in admin area
     * @return array
     */
    public function get_rc_configuration( $view = false ) {

        // Vérifier si nous sommes en mode C2C
        $is_c2c = 'c2c' === get_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX . 'interaction_mode', 'b2c' );

        $prefix = WC_RC_Shipping_Constants::RC_OPTION_PREFIX;
        $cached_config = [
            WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID => get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID, '' ),
            WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_NOM => get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_NOM, '' ),
            WC_RC_Shipping_Constants::CONFIGURATION_ACTIVATION_KEY => get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_ACTIVATION_KEY, '' ),
            WC_RC_Shipping_Constants::CONFIGURATION_ACTIVE => (bool)get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_ACTIVE, false ),
            
        ];
        if ( !$is_c2c ) {
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE1] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE1, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE2] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_ADDRESS_LINE2, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_POSTAL_CODE] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_POSTAL_CODE, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_CITY] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_CITY, '' );

        }
        if ( !$view ) {

            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID_LIGHT] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_ENSEIGNE_ID_LIGHT, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_USEIDENS] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_USEIDENS, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_API] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_API, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_PID] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_PID, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_KEY] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_LIVEMAPPING_KEY, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_FOLDER] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_FOLDER, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_RETURN_VERSION] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_VERSION, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_RETURN_LOGIN] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_LOGIN, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_RETURN_PASS] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_PASS, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_AGENCY_CODE] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_AGENCY_CODE, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_BY] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_BY, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_RETURN_SITE] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_RETURN_SITE, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_CREATED_AT] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_CREATED_AT, '' );
            $cached_config[WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_AT] = get_option( $prefix.WC_RC_Shipping_Constants::CONFIGURATION_UPDATED_AT, '' );
        }

        return $cached_config;
    }

    /**
     * Get options related to rc_configuration
     * @param boolean|void $active can filter by active if true or false
     * @return array
     */
    public function get_rc_configuration_options( $active = null ) {

        global $wpdb;

        // Define table names
        $rc_configuration_options_table = $wpdb->prefix.'rc_configuration_options';

        // Request
        $sql = "SELECT * FROM $rc_configuration_options_table WHERE 1=1";
        $params = array();

        if ( !is_null( $active ) && $active ) {

            $sql .= ' AND '.WC_RC_Shipping_Constants::CONFIGURATION_OPTION_ACTIVE.'=%d';
            $params[] = 1;
        } elseif ( !is_null( $active ) && !$active ) {

            $sql .= ' AND '.WC_RC_Shipping_Constants::CONFIGURATION_OPTION_ACTIVE.'=%d';
            $params[] = 0;
        }

        // Prepare statement
        $prepared_statement = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql, $params );

        // Execute query
        $options = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $prepared_statement, ARRAY_A );
        return $options;
    }
}