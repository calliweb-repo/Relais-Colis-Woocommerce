<?php

namespace Relais\WooCommerce\API\Enseigne;

use RelaisWoocommerce\API\AbstractApi;

require_once RC_API . 'abstractApi.php';

/**
 * Class Configuration
 *
 * @author Team Calliweb dev@calliweb.fr
 * @copyright Copyright © 2024 Calliweb (https://www.calliweb.fr)
 * @package Configuration
 */
class Configuration extends AbstractApi
{
    /** @var string */
    const ENDPOINT = 'api/enseigne/getConfiguration';
}
