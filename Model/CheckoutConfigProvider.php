<?php

declare(strict_types=1);

namespace Monri\Payments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Monri\Payments\Gateway\Config\Components as Config;

/**
 * Class CheckoutConfigProvider
 */
class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $config = [];
        if ($this->config->getValue('active')) {
            $config = [
                'payment' => [
                    Config::CODE => [
                        'componentsJsUrl' => $this->config->getComponentsJsURL(),
                        'authenticityToken' => $this->config->getClientAuthenticityToken(),
                        'locale' => $this->config->getGatewayLanguage()
                    ]
                ]
            ];
        }
        return $config;
    }
}
