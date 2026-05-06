<?php

declare(strict_types=1);

namespace Monri\Payments\Model\Ui\Redirect;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Monri\Payments\Gateway\VaultConfig;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment identifier
     */
    private const CODE = 'monri_payments';

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(RemoteAddress $remoteAddress)
    {
        $this->remoteAddress = $remoteAddress;
    }

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
                    'vaultCode' => VaultConfig::CODE,
                    'customerIp' => $this->remoteAddress->getRemoteAddress()
                ]
            ]
        ];
    }
}
