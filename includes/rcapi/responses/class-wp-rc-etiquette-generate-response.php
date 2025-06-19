<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Etiquette_Generate_Response is a PDF file (etiquette)
 *
 * @since 1.0.0
 */
class WP_RC_Etiquette_Generate_Response {

    private $filename;
    private $response_data;

    /**
     * Constructor
     */
    public function __construct( $response_data, string $filename ) {

        $this->filename = $filename;
        $this->response_data = $response_data;
    }

    /**
     * Generate the PDF delivery label from raw data
     * @return string|void the URL of the WP stored delivery label, as PDF
     */
    public function get_pdf_delivery_label() {

        WP_Log::debug( __METHOD__, [ '$this->filename' => '##'.$this->filename.'##' ], 'relais-colis-woocommerce' );

        // Current date prefix
        $date_prefixed_filename = gmdate('Y-m-d-H-i-s') .'-'. trim($this->filename, "\xC2\xA0\x20");

        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $date_prefixed_filename;
        WP_Log::debug( __METHOD__, [ '$file_path' => '##'.$file_path.'##' ], 'relais-colis-woocommerce' );

        // Write data as PDF
        if ( file_put_contents($file_path, $this->response_data) === false ) {

            WP_Log::warning( __METHOD__.': cannot write PDF file ', [ 'file_path' => $file_path ], 'relais-colis-woocommerce' );
            return null;
        }

        $file_url = $upload_dir['url'] . '/' . $date_prefixed_filename;
        return $file_url;
    }
}
