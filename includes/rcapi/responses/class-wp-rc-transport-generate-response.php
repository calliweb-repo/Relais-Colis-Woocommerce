<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\WPFw\Utils\WP_Log;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Transport_Generate_Response is a PDF file (transport)
 *
 * @since 1.0.0
 */
class WP_RC_Transport_Generate_Response {

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
     * Generate the PDF transport label from raw data
     * @return string|void the URL of the WP stored delivery label, as PDF
     */
    public function get_pdf_transport_label() {

        // Current date prefix
        $date_prefixed_filename = gmdate('Y-m-d-H-i-s') .'-'. $this->filename;

        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $date_prefixed_filename;

        // Write data as PDF
        if ( file_put_contents($file_path, $this->response_data) === false ) {

            WP_Log::warning( __METHOD__.': cannot write PDF file ', [ 'file_path' => $file_path ], 'relais-colis-woocommerce' );
            return null;
        }

        $file_url = $upload_dir['url'] . '/' . $date_prefixed_filename;
        return $file_url;
    }
}
