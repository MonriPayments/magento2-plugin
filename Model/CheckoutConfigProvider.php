<?php

declare(strict_types=1);

namespace Monri\Payments\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Monri\Payments\Gateway\Config\Components as Config;
use Magento\Checkout\Model\Session;
use Monri\Payments\Gateway\Response\Components\PaymentCreateHandler;

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
            $payment = $quote->getPayment();

            $config = [
                'payment' => [
                    Config::CODE => [
                        'componentsJsUrl' => $this->config->getComponentsJsURL(),
                        'authenticityToken' => $this->config->getClientAuthenticityToken(),
                        'locale' => $this->config->getGatewayLanguage(),
                        'transactionTime' => $payment->getAdditionalInformation(PaymentCreateHandler::TIME_LIMIT_TTL),
                        'transactionTimeLimit' => Config::TRANSACTION_TTL,
                    ]
                ]
            ];
        }
        return $config;
    }
}
