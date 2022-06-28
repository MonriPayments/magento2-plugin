<?php

declare(strict_types=1);

namespace Monri\Payments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Monri\Payments\Gateway\Config\Components as Config;
use Magento\Checkout\Model\Session;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * CheckoutConfigProvider constructor.
     *
     * @param Config $config
     * @param Session $session
     */
    public function __construct(
        Config $config,
        Session $session
    ) {
        $this->config = $config;
        $this->checkoutSession = $session;
    }

    /**
     * @inheritDoc
     */
    public function getConfig()
    {
        $config = [];
        if ($this->config->getValue('active')) {
            $quote = $this->checkoutSession->getQuote();

            $config = [
                'payment' => [
                    Config::CODE => [
                        'componentsJsUrl' => $this->config->getComponentsJsURL($quote->getStoreId()),
                        'authenticityToken' => $this->config->getClientAuthenticityToken($quote->getStoreId()),
                        'locale' => $this->config->getGatewayLanguage($quote->getStoreId()),
                    ]
                ]
            ];
        }
        return $config;
    }
}
