<?php

declare(strict_types=1);

namespace Monri\Payments\Gateway\Config;

use Monri\Payments\Gateway\Config;

class GooglePay extends Config
{
    public const CODE = 'monri_google_pay';
    public const PAYMENT_ACTION = 'payment_action';

    /**
     * Get payment create url
     *
     * @param int|null $storeId
     * @return string
     */
    public function getGatewayPaymentCreateURL($storeId = null)
    {
        return $this->getGatewayResourceURL('v2/payment/new', $storeId);
    }

    /**
     * Get components javascript url
     *
     * @param null|int $storeId
     * @return string
     */
    public function getComponentsJsURL($storeId = null)
    {
        return $this->getGatewayResourceURL('dist/components.js', $storeId);
    }

    /**
     * Get configured payment action
     *
     * @param int|null $storeId
     * @return string
     */
    public function getPaymentAction($storeId = null)
    {
        return $this->getValue(self::PAYMENT_ACTION, $storeId);
    }

    public function getIsTestMode($storeId = null) {
        return $this->getIsSandboxMode( $storeId ) === true ? 'test' : 'prod';
    }
}
