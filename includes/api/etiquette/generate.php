<?php

namespace Relais\Woocommerce\API\Etiquette;

use RelaisWoocommerce\API\AbstractApi;

require_once RC_API . 'abstractApi.php';

/**
 * Class Generate
 *
 * @author Team Calliweb dev@calliweb.fr
 * @copyright Copyright © 2024 Calliweb (https://www.calliweb.fr)
 * @package Generate
 */
class Generate extends AbstractApi
{
    /** @var string */
    const ENDPOINT = 'etiquette/generate';

    public function __construct(string $environment = null, string $activationKey = null)
    {
        parent::__construct($environment, $activationKey);

        $this->setDefaultBody(array_merge($this->getDefaultBody(), [
            'format' => '',
            'pdf' => ''
        ]));
    }

    protected function generateMassActionBody($labels): array
    {
        $massActionBody = [];
        $labelIteration = 1;
        foreach ($labels as $label) {
            $massActionBody['etiquette'.$labelIteration] = [$label];
            $labelIteration++;
        }

        return $massActionBody;
    }
}