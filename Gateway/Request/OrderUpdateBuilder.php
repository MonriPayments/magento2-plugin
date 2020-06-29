<?php

namespace Monri\Payments\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Monri\Payments\Gateway\Config;
use Monri\Payments\Helper\Formatter;
use Monri\Payments\Model\Crypto\Digest;

class OrderUpdateBuilder implements BuilderInterface
{
    const TRANSACTION_GROUP_FIELD = 'transaction';

    const AMOUNT_FIELD = 'amount';

    const CURRENCY_FIELD = 'currency';

    const DIGEST_FIELD = 'digest';

    const AUTHENTICITY_TOKEN_FIELD = 'authenticity-token';

    const ORDER_NUMBER_FIELD = 'order-number';

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Digest
     */
    private $digest;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Formatter $formatter,
        Digest $digest,
        Config $config
    ) {
        $this->formatter = $formatter;
        $this->digest = $digest;
        $this->config = $config;
    }

    /**
     * Builds the order capture request.
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        $amount = SubjectReader::readAmount($buildSubject);

        $gatewayAmount = $this->formatter->formatPrice($amount);
        $clientKey = $this->config->getClientKey($order->getStoreId());
        $orderNumber = $order->getIncrementId();
        $currencyCode = $order->getOrderCurrencyCode();
        $authenticityToken = $this->config->getClientAuthenticityToken($order->getStoreId());

        $digest = $this->digest->build($clientKey, $orderNumber, $currencyCode, $gatewayAmount);

        return [
            self::TRANSACTION_GROUP_FIELD => [
                self::AMOUNT_FIELD => $gatewayAmount,
                self::CURRENCY_FIELD => $currencyCode,
                self::DIGEST_FIELD => $digest,
                self::AUTHENTICITY_TOKEN_FIELD => $authenticityToken,
                self::ORDER_NUMBER_FIELD => $orderNumber,
                '__store' => $order->getStoreId(),
            ]
        ];
    }
}
