<?php

namespace Monri\Payments\Model\Ui\WSPay;

use Monri\Payments\Gateway\Config\WSPayVaultConfig;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterface;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * TokenUiComponentProvider constructor.
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param Json $jsonSerializer
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        Json $jsonSerializer
    ) {
        $this->componentFactory = $componentFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Get UI component for token
     *
     * @param PaymentTokenInterface $paymentToken
     * @return TokenUiComponentInterface
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken): TokenUiComponentInterface
    {
        $jsonDetails = $this->jsonSerializer->unserialize($paymentToken->getTokenDetails() ?: '{}');
        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code' => WSPayVaultConfig::CODE,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash()
                ],
                'name' => 'Monri_Payments/js/view/method-renderer/vault'
            ]
        );

        return $component;
    }
}
