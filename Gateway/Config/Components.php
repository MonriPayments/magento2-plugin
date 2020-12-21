<?php

declare(strict_types=1);

namespace Monri\Payments\Gateway\Config;

/**
 * Class Components
 */
class Components extends \Monri\Payments\Gateway\Config
{
    const CODE = 'monri_components';

    /**
     * @param $resource
     * @param $object
     * @param null $storeId
     * @return string
     */
    public function getGatewayPaymentCreateURL($storeId = null)
    {
        return $this->getGatewayResourceURL('v2/payment/new', $storeId);
    }

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getComponentsJsURL($storeId = null)
    {
        return $this->getGatewayResourceURL('dist/components.js', $storeId);
    }
}