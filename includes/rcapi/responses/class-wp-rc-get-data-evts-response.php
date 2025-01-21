<?php

namespace RelaisColisWoocommerce\RCAPI;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_class_Get_Data_Evts_Response
 *
 * This class represents the response object for the "07 - Récupération des évènements des colis d'une enseigne" operation
 * in the WP_Relais_Colis API. It is designed to process and store the data returned by the API regarding package events
 * for a specific enseigne.
 *
 * Example Response Format:
 * The response is structured as an XML document. Currently, the example response is empty:
 * ```xml
 * <?xml version="1.0" encoding="UTF-8"?>
 * <result/>
 * ```
 *
 * Notes:
 * - The `<result>` element serves as the root container for the response data.
 * - Future versions of the response may include nested elements providing detailed event information for each package.
 * - Once the structure of the API response is known, this class can be updated to parse and expose specific event details.
 *
 * Usage:
 * - Use this class to handle and interpret the data returned by the API.
 * - This response is expected to provide insights into the events or tracking history of packages linked to the enseigne.
 *
 * @since 1.0.0
 */
class WP_RC_Get_Data_Evts_Response extends WP_Relais_Colis_Response {

    // TODO

    private $mandatory_properties = array(
//        'id' => 'string',
//        'balance' => 'string',
    );

    /**
     * Template Method used to get specific mandatory properties
     * @return mixed
     */
    protected function get_mandatory_properties() {

        return $this->mandatory_properties;
    }

    /**
     * Get the customer ID.
     *
     * @return int|null Customer ID, or null if not available.

    public function get_id() {

        return $this->response_data->id ?? null;
    }*/
}
