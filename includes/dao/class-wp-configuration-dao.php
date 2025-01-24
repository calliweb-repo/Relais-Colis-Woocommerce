<?php

namespace RelaisColisWoocommerce\DAO;

defined( 'ABSPATH' ) or exit;

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

    const OPTION_RC_PREFIX = 'rc_configuration_';

    public static function get_html_title( $rc_configuration_slug ) {
        switch ( $rc_configuration_slug ) {
            case 'enseigne_id':
                return __( 'Enseigne ID', 'relais-colis-woocommerce' );
            case 'enseigne_id_light':
                return __( 'Enseigne ID Light', 'relais-colis-woocommerce' );
            case 'enseigne_nom':
                return __( 'Enseigne Name', 'relais-colis-woocommerce' );
            case 'activation_key':
                return __( 'Activation Key', 'relais-colis-woocommerce' );
            case 'active':
                return __( 'Active', 'relais-colis-woocommerce' );
            case 'useidens':
                return __( 'Use Enseigne ID', 'relais-colis-woocommerce' );
            case 'address_line1':
                return __( 'Address Line 1', 'relais-colis-woocommerce' );
            case 'postal_code':
                return __( 'Postal Code', 'relais-colis-woocommerce' );
            case 'city':
                return __( 'City', 'relais-colis-woocommerce' );
            case 'livemapping_api':
                return __( 'Live Mapping API', 'relais-colis-woocommerce' );
            case 'livemapping_pid':
                return __( 'Live Mapping PID', 'relais-colis-woocommerce' );
            case 'livemapping_key':
                return __( 'Live Mapping Key', 'relais-colis-woocommerce' );
            case 'folder':
                return __( 'Folder', 'relais-colis-woocommerce' );
            case 'return_version':
                return __( 'Return Version', 'relais-colis-woocommerce' );
            case 'return_login':
                return __( 'Return Login', 'relais-colis-woocommerce' );
            case 'return_pass':
                return __( 'Return Password', 'relais-colis-woocommerce' );
            case 'agency_code':
                return __( 'Agency Code', 'relais-colis-woocommerce' );
            case 'return_site':
                return __( 'Return Site', 'relais-colis-woocommerce' );
            case 'updated_by':
                return __( 'Updated By', 'relais-colis-woocommerce' );
            case 'created_at':
                return __( 'Created At', 'relais-colis-woocommerce' );
            case 'updated_at':
                return __( 'Updated At', 'relais-colis-woocommerce' );
            default:
                return __( 'Unknown Field', 'relais-colis-woocommerce' );
        }
    }

    /**
     * Insert a new enseigne configuration as WordPress options, and its options into the database.
     *
     * @param WP_RC_Get_Configuration_Response $response The enseigne configuration response object.
     * @return int|false The inserted activation key ID, or false on failure.
     */
    public function insert_rc_get_configuration_data( WP_RC_Get_Configuration_Response $response ) {

        // Extract data from the response object
        $enseigne_id = sanitize_text_field( $response->get_ens_id() );
        $enseigne_id_light = sanitize_text_field( $response->get_ens_id_light() );
        $enseigne_nom = sanitize_text_field( $response->get_ens_name() );
        $activation_key = sanitize_text_field( $response->get_activation_key() );
        $active = $response->is_active();
        $useidens = $response->use_id_ens();
        $address_line1 = sanitize_text_field( $response->get_address1() );
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
        $created_at = date( 'Y-m-d H:i:s', strtotime( $response->get_created_at() ) );
        $updated_at = date( 'Y-m-d H:i:s', strtotime( $response->get_updated_at() ) );

        // RC Configuration stored as options
        update_option( self::OPTION_RC_PREFIX.'enseigne_id', $enseigne_id );
        update_option( self::OPTION_RC_PREFIX.'enseigne_id_light', $enseigne_id_light );
        update_option( self::OPTION_RC_PREFIX.'enseigne_nom', $enseigne_nom );
        update_option( self::OPTION_RC_PREFIX.'activation_key', $activation_key );
        update_option( self::OPTION_RC_PREFIX.'active', $active );
        update_option( self::OPTION_RC_PREFIX.'useidens', $useidens );
        update_option( self::OPTION_RC_PREFIX.'address_line1', $address_line1 );
        update_option( self::OPTION_RC_PREFIX.'postal_code', $postal_code );
        update_option( self::OPTION_RC_PREFIX.'city', $city );
        update_option( self::OPTION_RC_PREFIX.'livemapping_api', $livemapping_api );
        update_option( self::OPTION_RC_PREFIX.'livemapping_pid', $livemapping_pid );
        update_option( self::OPTION_RC_PREFIX.'livemapping_key', $livemapping_key );
        update_option( self::OPTION_RC_PREFIX.'folder', $folder );
        update_option( self::OPTION_RC_PREFIX.'return_version', $return_version );
        update_option( self::OPTION_RC_PREFIX.'return_login', $return_login );
        update_option( self::OPTION_RC_PREFIX.'return_pass', $return_pass );
        update_option( self::OPTION_RC_PREFIX.'agency_code', $agency_code );
        update_option( self::OPTION_RC_PREFIX.'return_site', $return_site );
        update_option( self::OPTION_RC_PREFIX.'updated_by', $updated_by );
        update_option( self::OPTION_RC_PREFIX.'created_at', $created_at );
        update_option( self::OPTION_RC_PREFIX.'updated_at', $updated_at );

        // Insert related options
        global $wpdb;
        $options = $response->get_options();

        // Define table names
        $rc_configuration_options_table = $wpdb->prefix.'rc_configuration_options';

        if ( !empty( $options ) ) {

            foreach ( $options as $option ) {

                $option_id = sanitize_text_field( $option[ 'id' ] );
                $name = sanitize_text_field( $option[ 'name' ] );
                $value = sanitize_text_field( $option[ 'value' ] );
                $active = filter_var( $option[ 'active' ], FILTER_VALIDATE_BOOLEAN );

                $result = $wpdb->insert(
                    $rc_configuration_options_table,
                    [
                        'option_id' => $option_id,
                        'name' => $name,
                        'value' => $value,
                        'active' => $active,
                        'created_at' => $created_at, // Converted timestamp
                        'updated_at' => $updated_at, // Converted timestamp
                    ],
                    [ '%d', '%d', '%s', '%s', '%d', '%s', '%s' ]
                );

                if ( $result === false ) {
                    WP_Log::debug( __METHOD__.' - Failed to insert rc_configuration_option: '.$wpdb->last_error, [], 'relais-colis-woocommerce' );
                }
            }
        }
    }

    /**
     * Unique and simple access point to retrieve all options related to rc_configuration
     * @return array
     */
    public function get_rc_configuration() {

        static $cached_config = null;

        if ( $cached_config === null ) {
            $prefix = self::OPTION_RC_PREFIX;
            $cached_config = [
                'enseigne_id' => get_option( $prefix.'enseigne_id', '' ),
                'enseigne_id_light' => get_option( $prefix.'enseigne_id_light', '' ),
                'enseigne_nom' => get_option( $prefix.'enseigne_nom', '' ),
                'activation_key' => get_option( $prefix.'activation_key', '' ),
                'active' => (bool)get_option( $prefix.'active', false ),
                'useidens' => (bool)get_option( $prefix.'useidens', false ),
                'address_line1' => get_option( $prefix.'address_line1', '' ),
                'postal_code' => get_option( $prefix.'postal_code', '' ),
                'city' => get_option( $prefix.'city', '' ),
                'livemapping_api' => get_option( $prefix.'livemapping_api', '' ),
                'livemapping_pid' => get_option( $prefix.'livemapping_pid', '' ),
                'livemapping_key' => get_option( $prefix.'livemapping_key', '' ),
                'folder' => get_option( $prefix.'folder', '' ),
                'return_version' => get_option( $prefix.'return_version', '' ),
                'return_login' => get_option( $prefix.'return_login', '' ),
                'return_pass' => get_option( $prefix.'return_pass', '' ),
                'agency_code' => get_option( $prefix.'agency_code', '' ),
                'return_site' => get_option( $prefix.'return_site', '' ),
                'updated_by' => absint( get_option( $prefix.'updated_by', 0 ) ),
                'created_at' => get_option( $prefix.'created_at', '' ),
                'updated_at' => get_option( $prefix.'updated_at', '' ),
            ];
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

            $sql .= ' AND active=%d';
            $params[] = 1;
        } elseif ( !is_null( $active ) && !$active ) {

            $sql .= ' AND active=%d';
            $params[] = 0;
        }

        // Prepare statement
        $prepared_statement = $wpdb->prepare( $sql, $params );

        // Execute query
        $options = $wpdb->get_results( $prepared_statement, ARRAY_A );
        return $options;
    }
}