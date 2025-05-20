<?php

namespace RelaisColisWoocommerce\WPFw\Api;

defined( 'ABSPATH' ) or exit;

/**
 * Base XML API response class.
 *
 * @since 1.0.0
 */
abstract class WP_API_XML_Response extends WP_API_Response {

    /** @var \SimpleXMLElement XML object */
    protected $response_xml;

    /**
     * Build an XML object from the raw response.
     *
     * @since 1.0.0
     * @param string $raw_response The raw response XML
     */
    public function __construct( $raw_response ) {

        parent::__construct( $raw_response );

        // LIBXML_NOCDATA ensures that any XML fields wrapped in [CDATA] will be included as text nodes
        $this->response_xml = new \SimpleXMLElement( $raw_response, LIBXML_NOCDATA );

        /**
         * workaround to convert the horrible data structure that SimpleXMLElement returns
         * and provide a nice array of stdClass objects. Note there is some fidelity lost
         * in the conversion (such as attributes), but implementing classes can access
         * the response_xml member directly to retrieve them as needed.
         */
        $this->response_data = json_decode( json_encode( $this->response_xml ) );
    }


    /**
     * Get the string representation of this response.
     *
     * @since 1.0.0
     * @return string
     */
    public function to_string() {

        $response = $this->raw_response;

        $dom = new \DOMDocument();

        // suppress errors for invalid XML syntax issues
        if ( @$dom->loadXML( $response ) ) {
            $dom->formatOutput = true;
            $response = $dom->saveXML();
        }

        return $response;
    }


    /**
     * Get the string representation of this response with any and all sensitive elements masked
     * or removed.
     *
     * @since 1.0.0
     * @see WP_API_Response::to_string_safe()
     * @return string
     */
    public function to_string_safe() {

        return $this->to_string();
    }

}
