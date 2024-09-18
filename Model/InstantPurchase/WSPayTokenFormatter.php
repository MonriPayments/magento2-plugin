<?php

namespace Monri\Payments\Model\InstantPurchase;

use Monri\Payments\Gateway\Helper\CcTypeMapper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InstantPurchase\PaymentMethodIntegration\PaymentTokenFormatterInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class WSPayTokenFormatter implements PaymentTokenFormatterInterface
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * TokenFormatter constructor.
     *
     * @param Json $jsonSerializer
     */
    public function __construct(
        Json $jsonSerializer
    ) {
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @inheritDoc
     */
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $details = $this->jsonSerializer->unserialize($paymentToken->getTokenDetails() ?: '{}');
        if (!isset($details['type'], $details['maskedCC'], $details['expirationDate'])) {
            throw new \InvalidArgumentException('Invalid WSPay credit card token details.');
        }

        $ccType = CcTypeMapper::getCcTypeName($details['type']);

        $formatted = sprintf(
            '%s: %s, %s: %s (%s: %s)',
            __('Credit Card'),
            $ccType,
            __('ending'),
            $details['maskedCC'],
            __('expires'),
            $details['expirationDate']
        );

        return $formatted;
    }
}
