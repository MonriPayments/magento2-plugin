<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Request\Components;

use InvalidArgumentException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Monri\Payments\Gateway\Config\Components as ComponentsConfig;
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
     * @var ComponentsConfig
     */
    private $config;

    public function __construct(
        Formatter $formatter,
        Digest $digest,
        ComponentsConfig $config
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

        try {
            $amount = SubjectReader::readAmount($buildSubject);
        } catch (InvalidArgumentException $e) {
            // Assume entire amount if no amount is set.
            $amount = $payment->getAmountOrdered();
        }

        $gatewayAmount = $this->formatter->formatPrice($amount);
        $orderNumber = $payment->getAdditionalInformation( \Monri\Payments\Gateway\Request\Components\OrderDetailsBuilder::ORDER_NUMBER_FIELD);
        $currencyCode = $order->getOrderCurrencyCode();
        $authenticityToken = $this->config->getClientAuthenticityToken($order->getStoreId());

        $digest = $this->digest->build(
            $orderNumber,
            $currencyCode,
            $gatewayAmount,
            $order->getStoreId(),
            Digest::DIGEST_ALGO_1
        );

        return [
            self::TRANSACTION_GROUP_FIELD => [
                self::AMOUNT_FIELD => $gatewayAmount,
                self::CURRENCY_FIELD => $currencyCode,
                self::DIGEST_FIELD => $digest,
                self::AUTHENTICITY_TOKEN_FIELD => $authenticityToken,
                self::ORDER_NUMBER_FIELD => $orderNumber,
            ],
            '__store' => $order->getStoreId(),
        ];
    }
}