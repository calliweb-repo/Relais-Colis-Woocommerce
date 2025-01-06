<?php

namespace Relais\WooCommerce\API\Package;

use RelaisWoocommerce\API\AbstractApi;

require_once RC_API . 'abstractApi.php';

/**
 * Class booking
 *
 * @author Team Calliweb dev@calliweb.fr
 * @copyright Copyright © 2024 Calliweb (https://www.calliweb.fr)
 * @package booking
 */
class  booking extends AbstractApi
{
    /** @var string */
    const ENDPOINT = 'api/package/placeAdvertisement';
    const ACTIVITY_CODE = '05';
    const ACTIVITY_CODE_HOME = '08';
    const ACTIVITY_CODE_DRIVE = '07';

    public function __construct(string $environment = null, string $activationKey = null)
    {
        parent::__construct($environment, $activationKey);

        $this->setDefaultBody(array_merge($this->getDefaultBody(), [
            'activityCode' => $this->getActivityCode(),
            'deliveryPaymentMethod' => '3',
            'deliveryType' => '00',
            'language' => 'FR',
            'orderType' => '1',
            'orderTypeSub' => '1',
            'pickingSite' => '0',
            'productFamily' => '08',
            'sensitiveProduct' => '0'
        ]));
    }

    protected function generateCustomerInformation()
    {
        return [
            'customerId' => '',
            'customerFullname' => '',
            'customerEmail' => '',
            'customerPhone' => '',
            'customerMobile' => '',
        ];
    }

    protected function generateGlobalOrderInformation()
    {
        return [
            'agencyCode' => '',
            'orderReference' => '',
            'shippingAddress1' => '',
            'shippingAddress2' => '',
            'shippingPostcode' => '',
            'shippingCity' => '',
            'shippingCountryCode' => '',
            'shippmentWeight' => '',
            'weight' => '',
            'xeett' => '' // Id du point relais
        ];
    }

    protected function generateB2COrderInformation()
    {
        return [
            'pseudoRvc' => '', // Id sur la map
        ];
    }

    protected function generateC2COrderInformation()
    {
        return [
            'address1Expediteur' => '',
            'address2Expediteur' => '',
            'emailExpediteur' => '',
            'cityExpediteur' => '',
            'nameExpediteur' => '',
            'phoneExpediteur' => '',
            'postcodeExpediteur' => ''
        ];
    }

    protected function generateC2CInformation()
    {
        // Add customer information /
        return [
            'hash_token' => '',
        ];
    }

    protected function getActivityCode()
    {
        $contractType = $this->getContractType(); // @TODO Define contract type
    }
}
