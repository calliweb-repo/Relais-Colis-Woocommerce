<?php

namespace RelaisColisWoocommerce\DAO;

defined( 'ABSPATH' ) or exit;

use RelaisColisWoocommerce\RCAPI\WP_RC_Get_Infos_Response;
use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;
use RelaisColisWoocommerce\WPFw\Traits\Singleton;

/**
 * This class manages the information data
 *
 * @since 1.0.0
 */
class WP_Information_DAO {

    use Singleton;

    /**
     * Insert or update customer information as WordPress options.
     *
     * @param WP_RC_Get_Infos_Response $response The customer information response object.
     * @return void
     */
    public function replace_rc_get_information_data( WP_RC_Get_Infos_Response $response ) {

        // Extract data from the response object
        $result_id = absint( $response->get_id() );
        $firstname = sanitize_text_field( $response->get_firstname() );
        $lastname = sanitize_text_field( $response->get_lastname() );
        $email = sanitize_email( $response->get_email() );
        $balance = $response->get_balance();
        $account_status = sanitize_text_field( $response->get_account_status() );
        $account_type = sanitize_text_field( $response->get_account_type() );
        $code_enseigne = sanitize_text_field( $response->get_code_enseigne() );

        // Store information in WordPress options
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_RESULT_ID, $result_id );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_FIRSTNAME, $firstname );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_LASTNAME, $lastname );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_EMAIL, $email );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_BALANCE, $balance );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_STATUS, $account_status );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_TYPE, $account_type );
        update_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_CODE_ENSEIGNE, $code_enseigne );
    }

    /**
     * Delete overall enseigne information as WordPress options, and its options into the database.
     *
     * @return int|false The inserted activation key ID, or false on failure.
     */
    public function delete_rc_get_information_data() {

        // RC Information stored as options
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_RESULT_ID );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_FIRSTNAME );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_LASTNAME );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_EMAIL );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_BALANCE );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_STATUS );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_TYPE );
        delete_option( WC_RC_Shipping_Constants::RC_OPTION_PREFIX.WC_RC_Shipping_Constants::INFORMATION_CODE_ENSEIGNE );

        // And C2C hash token
        delete_option( WC_RC_Shipping_Constants::OPTION_C2C_HASH_TOKEN);
    }

    /**
     * Unique and simple access point to retrieve all options related to rc_information
     * @return array
     */
    public function get_rc_information() {

        //static $cached_information = null;

        //if ( $cached_information === null ) {
            $prefix = WC_RC_Shipping_Constants::RC_OPTION_PREFIX;
            $cached_information = [
                WC_RC_Shipping_Constants::INFORMATION_RESULT_ID => get_option( $prefix.WC_RC_Shipping_Constants::INFORMATION_RESULT_ID, '' ),
                WC_RC_Shipping_Constants::INFORMATION_FIRSTNAME => get_option( $prefix.WC_RC_Shipping_Constants::INFORMATION_FIRSTNAME, '' ),
                WC_RC_Shipping_Constants::INFORMATION_LASTNAME => get_option( $prefix.WC_RC_Shipping_Constants::INFORMATION_LASTNAME, '' ),
                WC_RC_Shipping_Constants::INFORMATION_EMAIL => get_option( $prefix.WC_RC_Shipping_Constants::INFORMATION_EMAIL, '' ),
                WC_RC_Shipping_Constants::INFORMATION_BALANCE => (float)get_option( $prefix.WC_RC_Shipping_Constants::INFORMATION_BALANCE, 0 ),
                WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_STATUS => get_option( $prefix.WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_STATUS, '' ),
                // WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_TYPE => get_option( $prefix.WC_RC_Shipping_Constants::INFORMATION_ACCOUNT_TYPE, '' ),
                WC_RC_Shipping_Constants::INFORMATION_CODE_ENSEIGNE => get_option( $prefix.WC_RC_Shipping_Constants::INFORMATION_CODE_ENSEIGNE, '' ),
            ];
        //}

        return $cached_information;
    }
}