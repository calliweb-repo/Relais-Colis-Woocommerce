<?php

namespace RelaisColisWoocommerce\RCAPI;

use RelaisColisWoocommerce\Shipping\WC_RC_Shipping_Constants;

defined( 'ABSPATH' ) or exit;

/**
 * WP_RC_Get_Packages_Status_Response
 *
 * This class processes the tracking response from Relais Colis, providing structured shipment status information.
 *
 * The response typically includes key tracking details such as:
 *
 * Root Object:
 * - An associative array containing tracking details for a specific parcel.
 *
 * Response Fields:
 * 1. `parcelNumber` (string)
 *     - The unique tracking number assigned to the parcel.
 *
 * 2. `RangPaquet` (int)
 *     - The parcel sequence number (useful when multiple parcels exist in a shipment).
 *
 * 3. `Libelle` (string)
 *     - A short description of the current status of the package.
 *     - Typically wrapped in `<![CDATA[]]>` to handle special characters if returned in XML format.
 *
 * 4. `LibelleDetaille` (string)
 *     - A more detailed version of `Libelle`, providing additional context about the shipment status.
 *
 * 5. `Categorie` (string)
 *     - The **category of the status**, typically grouping similar events under one category.
 *
 * 6. `Date` (datetime)
 *     - The timestamp of the current tracking event.
 *     - Formatted as `YYYY-MM-DDTHH:MM:SS`.
 *
 * 7. `GMT` (string)
 *     - The timezone associated with the event timestamp (e.g., `"GMT+1"`).
 *
 * 8. `Etape` (int)
 *     - The **tracking step index** (useful for ordering events in the shipment lifecycle).
 *
 * 9. `CodeEVT` (string)
 *     - The **event code** associated with the tracking update (e.g., `"APF"`).
 *
 * 10. `CodeJUS` (string)
 *     - A **justification code** (if applicable) related to the event.
 *
 * 11. `CodeTrack` (string)
 *     - A **general tracking code** categorizing the event (e.g., `"AN"` for "Announced").
 *
 * Example JSON Response:
 * ```json
 * [
 *    {
 *        "parcelNumber": "4H013000253201",
 *        "RangPaquet": 1,
 *        "Libelle": "Your package has been announced.",
 *        "LibelleDetaille": "Your package has been announced.",
 *        "Categorie": "Package announced.",
 *        "Date": "2025-02-17T18:00:00",
 *        "GMT": "GMT+1",
 *        "Etape": 0,
 *        "CodeEVT": "APF",
 *        "CodeJUS": "",
 *        "CodeTrack": "AN"
 *    }
 * ]
 * ```
 *
 * @since 1.0.0
 */
class WP_RC_Get_Packages_Status_Response extends WP_Relais_Colis_Response {


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
     * Get response as an array
     * @return array|mixed
     */
    public function get_rc_statuses() {

        return isset( $this->response_data )
            ? json_decode( json_encode( $this->response_data ), true )
            : [];
    }

    /**
     * Get the RC status with a simplified format
     *
     * @return int|null Customer ID, or null if not available.
     */
    public function get_simplified_rc_statuses() {


        // TODO
        // MOCK
        // List of ORDER_REFERENCE => RC_STATUS
        return array(
            '4H013000011101' => 'status_rc_expedie',
            '4H013000011501' => 'status_rc_expedie',
            '4H013000011501' => 'status_rc_expedie',
        );

        //Voilà la manière dont les codes EVT et JUS sont traités actuellement:
        //
        //if ($evts_code == 'APF') {
        //                if ($evts_justif == 'RDF') {
        //                    $status = Installer::RC_RETOURNE;
        //                } else {
        //                    $status = Installer::RC_EXPEDIE;
        //                }
        //            } elseif ($evts_code == 'RST') {
        //                if ($evts_justif == 'REC') {
        //                    $status = Installer::RC_DEPOSE_EN_RELAIS;
        //                } else {
        //                    $status = Installer::RC_LIVRAISON_EN_COURS;
        //                }
        //            } elseif ($evts_code == 'DEP' && $evts_justif == 'REL') {
        //                $status = Installer::RC_DEPOSE_EN_RELAIS;
        //            } elseif ($evts_code == 'SOL') {
        //                $status = Installer::RC_LIVRE;
        //            } elseif ($evts_code == 'REN') {
        //                if ($evts_justif == 'LIV') {
        //                    $status = Installer::RC_LIVRE;
        //                } else {
        //                    $status = Installer::RC_EN_COURS_DE_RETOUR;
        //                }
        //            } elseif ($evts_code == 'SOR') {
        //                if ($evts_justif == 'LIV') {
        //                    $status = Installer::RC_LIVRE;
        //                } elseif ($evts_justif == 'RDF') {
        //                    $status = Installer::RC_RETOURNE;
        //                } else {
        //                    $status = Installer::RC_ECHEC_DE_LIVRAISON;
        //                }
        //            }

        // To be returned, an associative array : parcel_number => status
        $statuses = array();

        // New simplified RC status
        $rc_status = $this->get_rc_statuses();

        foreach ( $rc_status as $c_rc_status ) {

            // Get parcel number
            $parcel_number = $c_rc_status->{WC_RC_Shipping_Constants::PACKAGES_STATUS_PARCEL_NUMBER} ?? null;

            // Get event code and justif
            $code_event = $c_rc_status->{WC_RC_Shipping_Constants::PACKAGES_STATUS_CODE_EVT} ?? null;
            $code_justif = $c_rc_status->{WC_RC_Shipping_Constants::PACKAGES_STATUS_CODE_JUS} ?? null;

            if ( is_null( $parcel_number ) || is_null( $code_event ) || is_null( $code_justif ) ) {
                continue;
            }

            // Apply RC algo
            $status = null;
            if ( $code_event == 'APF' ) {

                if ( $code_justif == 'RDF' ) {
                    $status = WC_RC_Shipping_Constants::STATUS_RC_RETOURNE;
                } else {
                    $status = WC_RC_Shipping_Constants::STATUS_RC_EXPEDIE;
                }
            } elseif ( $code_event == 'RST' ) {

                if ( $code_justif == 'REC' ) {
                    $status = WC_RC_Shipping_Constants::STATUS_RC_DEPOSE_EN_RELAIS;
                } else {
                    $status = WC_RC_Shipping_Constants::STATUS_RC_LIVRAISON_EN_COURS;
                }
            } elseif ( $code_event == 'DEP' && $code_justif == 'REL' ) {

                $status = WC_RC_Shipping_Constants::STATUS_RC_DEPOSE_EN_RELAIS;

            } elseif ( $code_event == 'SOL' ) {

                $status = WC_RC_Shipping_Constants::STATUS_RC_LIVRE;

            } elseif ( $code_event == 'REN' ) {

                if ( $code_justif == 'LIV' ) {
                    $status = WC_RC_Shipping_Constants::STATUS_RC_LIVRE;
                } else {
                    $status = WC_RC_Shipping_Constants::STATUS_RC_EN_COURS_DE_RETOUR;
                }
            } elseif ( $code_event == 'SOR' ) {

                if ( $code_justif == 'LIV' ) {
                    $status = WC_RC_Shipping_Constants::STATUS_RC_LIVRE;
                } elseif ( $code_justif == 'RDF' ) {
                    $status = WC_RC_Shipping_Constants::STATUS_RC_RETOURNE;
                } else {
                    $status = WC_RC_Shipping_Constants::STATUS_RC_ECHEC_LIVRAISON;
                }
            }

            // Build return
            $statuses[ $parcel_number ] = $status;
        }
        return $statuses;
    }
}
