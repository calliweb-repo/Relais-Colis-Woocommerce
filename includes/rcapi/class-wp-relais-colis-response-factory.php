<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\WPFw\Traits\Singleton;
use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 *  Response factory
 *
 * @since 1.0.0
 */
class WP_Relais_Colis_Response_Factory {

    use Singleton;

    /**
     * Check if a response is JSON ncoded
     * @param $response_data the raw response data
     * @return boolean true if JSON, false otherwise
     */
    private function is_json_response( $response_data ) {

        json_decode( $response_data );
        return ( json_last_error() === JSON_ERROR_NONE );
    }

    /**
     * Generic treatment for a response handling
     *
     * @param string $request_type one of REQUEST_GET_B2C_CONFIGURATION, REQUEST_GET_C2C_CONFIGURATION ...
     * @param boolean $raw to get a raw response, instead of a formatted one
     * @return array|mixed|object|WP_Relais_Colis_Response|null
     * @throws WP_Relais_Colis_API_Exception
     */
    public function get_rc_api_response( string $request_type, $response_data, $response_headers, $raw = false ) {

        WP_Log::debug( __METHOD__, [ 'request_type' => $request_type, 'response data' => $response_data ], 'relais-colis-woocommerce' );

        if ( empty( $response_data ) ) return null;

        if ( $raw ) return $response_data;
        else {

            // Check response content type
            $response_content_type = $response_headers['content-type'];
            if ( strpos( $response_content_type, 'text/html') !== false ) {

                // Pb occured... HTML response not permitted
                throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE]) );
            }

            //if ( $this->is_json_response( $response_data ) ) {
            if ( strpos( $response_content_type, 'application/json') !== false ) {

                WP_Log::debug( __METHOD__.' - This is a JSON response', [], 'relais-colis-woocommerce' );

                // May be an error ...
                $response = new WP_Relais_Colis_Error_Response( $response_data );
                WP_Log::debug( __METHOD__.' - JSON response', [ 'title' => $response->title, 'status' => $response->status, 'detail' => $response->detail ], 'relais-colis-woocommerce' );
                throw new WP_Relais_Colis_API_Exception( esc_html($response->title), esc_html($response->status), esc_html($response->detail) );

            } else {

                // Build response
                switch ( $request_type ) {
                    case WP_Relais_Colis_API::REQUEST_GET_B2C_CONFIGURATION:
                        if ( strpos( $response_content_type, 'text/xml') === false ) {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE]) );
                        }
                        $response = new WP_RC_Get_Configuration_Response( $response_data );
                        break;
                    case WP_Relais_Colis_API::REQUEST_GET_C2C_CONFIGURATION:
                        if ( strpos( $response_content_type, 'text/xml') === false ) {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE]) );
                        }
                        $response = new WP_RC_Get_Configuration_Response( $response_data );
                        break;
                    case WP_Relais_Colis_API::REQUEST_B2C_RELAY_PLACE_ADVERTISEMENT:
                    case WP_Relais_Colis_API::REQUEST_B2C_HOME_PLACE_ADVERTISEMENT:
                        if ( strpos( $response_content_type, 'text/xml') === false ) {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE]) );
                        }
                        // New case, entry is received but containing error: prefix
                        //    [response] => Array
                        //        (
                        //            [headers] => Array
                        //                (
                        //                    [WpOrg\Requests\Utility\CaseInsensitiveDictionary] => Array
                        //                        (
                        //                        )
                        //
                        //                )
                        //
                        //            [body] => <xml version="1.0" encoding="UTF-8">
                        //<result>
                        //  <entry><![CDATA[error:MISSING_FIELD_VALUE]]></entry>
                        //</result>
                        //
                        //            [response] => Array
                        //                (
                        //                    [code] => 200
                        //                    [message] => OK
                        //                )

                        $response = new WP_RC_Place_Advertisement_Response( $response_data );


                        
                        if ( $response->validate() ) {

                            $entry = $response->entry;


                            
                            WP_Log::debug(__METHOD__ . ' - Valid response', ['Entry' => $entry,], 'relais-colis-woocommerce');

                            if ( is_array($entry) ) {
                                foreach ($entry as $item) {
                                    if ( strpos( $item, 'error:' ) !== false ) {
                                        if ( strpos( $item, 'Not enough money in balance' ) !== false ) {
                                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_NOT_ENOUGH_MONEY_IN_BALANCE)), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_NOT_ENOUGH_MONEY_IN_BALANCE]) );
                                        }
                                    }
                                }
                            }else{
                                if ( strpos( $entry, 'error:' ) !== false ) {
                                    if ( strpos( $entry, 'Not enough money in balance' ) !== false ) {
                                        throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_NOT_ENOUGH_MONEY_IN_BALANCE)), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_NOT_ENOUGH_MONEY_IN_BALANCE]) );
                                    } else {
                                        throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE]) );
                                    }
                                }
                            }
                        }


                        break;
                    case WP_Relais_Colis_API::REQUEST_C2C_RELAY_PLACE_ADVERTISEMENT:
                        if ( strpos( $response_content_type, 'text/xml') === false ) {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE]) );
                        }
                        // New case, entry is received but containing error: prefix
                        //    [response] => Array
                        //        (
                        //            [headers] => Array
                        //                (
                        //                    [WpOrg\Requests\Utility\CaseInsensitiveDictionary] => Array
                        //                        (
                        //                        )
                        //
                        //                )
                        //
                        //            [body] => <xml version="1.0" encoding="UTF-8">
                        //<result>
                        //  <entry><![CDATA[error:MISSING_FIELD_VALUE]]></entry>
                        //</result>
                        //
                        //            [response] => Array
                        //                (
                        //                    [code] => 200
                        //                    [message] => OK
                        //                )

                        $response = new WP_RC_Place_Advertisement_Response( $response_data );
                        if ( $response->validate() ) {

                            $entry = $response->entry;

                            WP_Log::debug(__METHOD__ . ' - Valid response', ['Entry' => $entry,], 'relais-colis-woocommerce');

                            if ( strpos( $entry, 'error:' ) !== false ) {
                                if ( strpos( $entry, 'Not enough money in balance' ) !== false ) {
                                    throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_NOT_ENOUGH_MONEY_IN_BALANCE)), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_NOT_ENOUGH_MONEY_IN_BALANCE]) );
                                } else {
                                    throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE]) );
                                }
                            }
                        }


                        break;
                    case WP_Relais_Colis_API::REQUEST_B2C_PLACE_RETURN_V2:
                    case WP_Relais_Colis_API::REQUEST_B2C_PLACE_RETURN_V3:
                        if ( strpos( $response_content_type, 'text/xml') === false ) {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE]) );
                        }
                        $response = new WP_RC_B2C_Place_Return_Response( $response_data );

                        // Check response response_status
                        if ( $response->get_response_status() == 'Error' ) {

                            $error_type = $response->get_error_type();
                            $error_description = $response->get_error_description();

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html($error_type).' - '.esc_html($error_description), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_PLACE_RETURN_ERROR]) );

/*
                            // TEST
                            $xml_response = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<result>
    <entry>
        <id>1</id>
        <enseigne_id>112</enseigne_id>
        <order_id><![CDATA[160]]></order_id>
        <customer_id><![CDATA[12345]]></customer_id>
        <customer_fullname><![CDATA[John Doe]]></customer_fullname>
        <customer_phone><![CDATA[0123456789]]></customer_phone>
        <customer_mobile><![CDATA[0987654321]]></customer_mobile>
        <customer_company><![CDATA[Company XYZ]]></customer_company>
        <customer_address1><![CDATA[123 Main Street]]></customer_address1>
        <customer_address2><![CDATA[Building B]]></customer_address2>
        <customer_postcode><![CDATA[75001]]></customer_postcode>
        <customer_city><![CDATA[Paris]]></customer_city>
        <customer_country><![CDATA[FR]]></customer_country>
        <reference><![CDATA[REF123456]]></reference>
        <response_status><![CDATA[Success]]></response_status>
        <return_number><![CDATA[RCBC0000012222]]></return_number>
        <number_cab><![CDATA[CAB-RCBC0000012222]]></number_cab>
        <limit_date><![CDATA[2025-03-25 00:00:00]]></limit_date>
        <image_url><![CDATA[https://equidassur.fr/wp-content/uploads/2021/01/Les-robes-du-cheval-2.jpg]]></image_url>
        <bordereau_smart_url><![CDATA[http://sukellos.com]]></bordereau_smart_url>
        <created_at><![CDATA[2025-03-10 00:00:00]]></created_at>
        <token><![CDATA[ETYE4466G666GGGE0002]]></token>
    </entry>
</result>
XML;
                            $response = new WP_RC_B2C_Place_Return_Response( $xml_response );
*/
                        }

                        break;
                    case WP_Relais_Colis_API::REQUEST_B2C_GENERATE:
                    case WP_Relais_Colis_API::REQUEST_BULK_GENERATE:

                        // Response from RCAPI can have 2 different Content-Type :
                        // REQUEST_B2C_GENERATE - Relay & Home
                        //  B2C Relais & Home will return:
                        //      Content-Type: application/pdf, PDF NOT encoded
                        //  C2C Relais will return:
                        //      Content-Type: application/octet-stream, NOT encoded

                        // REQUEST_BULK_GENERATE - B2C - Relay & Home
                        //  B2C Relais & Home will return:
                        //      Content-Type: application/pdf, PDF NOT encoded
                        //  C2C Relais will return:
                        //      Content-Type: application/octet-stream, Base64 encoded


                        if ( ( strpos( $response_content_type, 'application/pdf') === false ) && ( strpos( $response_content_type, 'application/octet-stream') === false ) )  {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE])  );
                        }

                        $content_disposition = $response_headers['content-disposition'];
                        $filename = 'etiquette.pdf';

                        // Extract filename from content-disposition
                        // [content-disposition] => inline; filename="Etiquette.pdf"; filename*=UTF-8''Etiquette.pdf
                        if (preg_match('/filename="([^"]+)"/', $content_disposition, $matches)) {

                            $filename = $matches[1];
                        }
                        WP_Log::debug( __METHOD__, ['$response_content_type'=>$response_content_type], 'relais-colis-wocommerce' );

                        // C2C will return:
                        //      Content-Type: application/octet-stream, Base64 encoded
                        //      Content-Disposition: attachment; filename="CC070000092001.pdf"
                        if ( strpos( $response_content_type, 'application/octet-stream') !== false )  {

                            WP_Log::debug( __METHOD__.' - application/octet-stream detected', ['$response_content_type'=>$response_content_type], 'relais-colis-wocommerce' );
                            $response_data = base64_decode( $response_data );
                        }
                        // B2C will return:
                        //      Content-Type: application/pdf, PDF directly encoded
                        //      Content-Disposition: inline; filename="Etiquette.pdf"; filename*=UTF-8''Etiquette.pdf
                        // Nothing else to do

                        $response = new WP_RC_Etiquette_Generate_Response( $response_data, $filename );
                        break;
                    case WP_Relais_Colis_API::REQUEST_C2C_GENERATE:

                        // C2C will return:
                        //      Content-Type: application/octet-stream, NOT encoded

                        if ( ( strpos( $response_content_type, 'application/pdf') === false ) && ( strpos( $response_content_type, 'application/octet-stream') === false ) )  {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE])  );
                        }

                        $content_disposition = $response_headers['content-disposition'];
                        $filename = 'etiquette.pdf';

                        // Extract filename from content-disposition
                        // [content-disposition] => inline; filename="Etiquette.pdf"; filename*=UTF-8''Etiquette.pdf
                        if (preg_match('/filename="([^"]+)"/', $content_disposition, $matches)) {

                            $filename = $matches[1];
                        }
                        WP_Log::debug( __METHOD__, ['$response_content_type'=>$response_content_type], 'relais-colis-wocommerce' );

                        $response = new WP_RC_Etiquette_Generate_Response( $response_data, $filename );
                        break;
                    case WP_Relais_Colis_API::REQUEST_TRANSPORT_GENERATE:
                        if ( ( strpos( $response_content_type, 'application/pdf') === false ) && ( strpos( $response_content_type, 'application/octet-stream') === false ) )  {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE])  );
                        }

                        // Extract filename from content-disposition
                        // [content-disposition] => inline; filename="Etiquette.pdf"; filename*=UTF-8''Etiquette.pdf
                        $content_disposition = $response_headers['content-disposition'];
                        $filename = 'transport.pdf';
                        if (preg_match('/filename="([^"]+)"/', $content_disposition, $matches)) {

                            $filename = $matches[1];
                        }

                        // C2C will return:
                        //      Content-Type: application/octet-stream, Base64 encoded
                        //      Content-Disposition: attachment; filename="CC070000092001.pdf"
                        if ( strpos( $response_content_type, 'application/octet-stream') === false )  {

                            $response_data = base64_decode( $response_data );
                        }
                        // B2C will return:
                        //      Content-Type: application/pdf, PDF directly encoded
                        //      Content-Disposition: inline; filename="Etiquette.pdf"; filename*=UTF-8''Etiquette.pdf
                        // Nothing else to do

                        $response = new WP_RC_Transport_Generate_Response( $response_data, $filename );
                        break;
                    case WP_Relais_Colis_API::REQUEST_C2C_GET_INFOS:
                        if ( strpos( $response_content_type, 'text/xml') === false ) {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE])  );
                        }
                        $response = new WP_RC_Get_Infos_Response( $response_data );
                        break;
                    case WP_Relais_Colis_API::REQUEST_C2C_GET_PACKAGES_PRICE:
                        if ( strpos( $response_content_type, 'text/xml') === false ) {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE])  );
                        }
                        $response = new WP_RC_C2C_Get_Packages_Price_Response( $response_data );
                        break;
                    case WP_Relais_Colis_API::REQUEST_GET_PACKAGES_STATUS:
                        if ( strpos( $response_content_type, 'text/xml') === false ) {

                            // Pb occured... HTML response not permitted
                            throw new WP_Relais_Colis_API_Exception( esc_html(WP_Relais_Colis_API_Exception::get_i18n_message(WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE)).' '.esc_html($response_content_type), esc_html(WP_Relais_Colis_API_Exception::ERROR_CODES[WP_Relais_Colis_API_Exception::RC_API_INVALID_RESPONSE_CONTENT_TYPE])  );
                        }
                        $response = new WP_RC_Get_Packages_Status_Response( $response_data );
                        break;
                    default:
                        return null;
                }


                // Error
                //[stdClass] => Array
                //                (
                //                    [code] => 404
                //                    [message] => Not Found
                //                )
                if ( property_exists( $response, 'code' ) && property_exists( $response, 'message' ) && !is_null( $response->code ) && !is_null( $response->message ) ) {

                    throw new WP_Relais_Colis_API_Exception( esc_html($response->message), esc_html($response->code) );
                }

                return $response;
            }
        }
    }
}
