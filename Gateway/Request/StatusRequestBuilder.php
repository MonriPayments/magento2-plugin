<?php

namespace Monri\Payments\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Monri\Payments\Gateway\Config;
use Monri\Payments\Helper\Formatter;
use Monri\Payments\Model\Crypto\Components\OrderStatusDigest as Digest;

class StatusRequestBuilder implements BuilderInterface
{
    public function __construct(
        private Digest $digest,
        private Formatter $formatter,
        private Config $config,
    ){}
    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();

        $payment = $paymentDataObject->getPayment();
        $orderNumber = $payment->getAdditionalInformation('monri_order_number');
        $currencyCode = $order->getCurrencyCode();


        /*
            Added in 2.4.8, because \PayPal\Braintree\Gateway\Data\Order\OrderAdapter puts themselves as preference for
            \Magento\Payment\Gateway\Data\Order\OrderAdapter. It declares strict types, but getGrandTotalAmount returns
            string instead of float, causing it to break execution.
        */
        try {
            $amount = $this->formatter->formatPrice(
                $order->getGrandTotalAmount()
            );
        } catch (\TypeError $e) {
            $orderObject = $payment->getOrder();
            $amount = $this->formatter->formatPrice(
                $orderObject->getBaseGrandTotal()
            );
        }
        $storeId = $order->getStoreId();
        $authToken = $this->config->getClientAuthenticityToken($storeId);

        $digest = $this->digest->build(
            $orderNumber,
            $order->getStoreId()
        );

        return [
            'order' => [
                'order_number' => $orderNumber,
                'authenticity_token' => $authToken,
                'digest' => $digest,
                ],
            '__store' => $order->getStoreId(),
        ];
    }
}
