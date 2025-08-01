<?php

declare(strict_types=1);

namespace Monri\Payments\Model\Ui\Redirect;

use Magento\Checkout\Model\ConfigProviderInterface;
use Monri\Payments\Gateway\VaultConfig;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment identifier
     */
    private const CODE = 'monri_payments';

    /**
     * Retrieve assoc array of payment method configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => [
                    'vaultCode' => VaultConfig::CODE
                ]
            ]
        ];
    }
}
