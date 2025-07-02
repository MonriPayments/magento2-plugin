<?php

namespace Monri\Payments\Gateway\Response\WSPay\Vault;

use Monri\Payments\Gateway\Helper\CcTypeMapper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Monri\Payments\Gateway\Config\WSPay;

class CaptureHandler implements HandlerInterface
{
    /**
     * @var OrderSender $orderSender
     */
    private OrderSender $orderSender;

    /**
     * @var WSPay
     */
    private WSPay $config;

    /**
     * @param OrderSender $orderSender
     * @param WSPay $config
     */
    public function __construct(
        OrderSender $orderSender,
        WSPay $config
    ) {
        $this->orderSender = $orderSender;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     *
     * @todo: Use this as separate handler for both form and vault (PaymentCaptureHandler vs OrderCaptureHandler)
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        /** @var Payment $orderPayment */
        $payment = $paymentDO->getPayment();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $payment->setAdditionalInformation('STAN', $response['STAN']);
        $payment->setAdditionalInformation('ApprovalCode', $response['ApprovalCode']);
        $payment->setAdditionalInformation('originalTransactionId', $response['WsPayOrderId']);
        $payment->setAdditionalInformation('paidUsingToken', true);
        $payment->setTransactionId($response['WsPayOrderId']);

        $additionalTransactionInfo = $this->config->getTransactionInfoInOrder($order->getStoreId());

        if ($additionalTransactionInfo) {
            $payment->setAdditionalInformation('WsPayOrderId', $response['WsPayOrderId']);
            $payment->setAdditionalInformation('PaymentType', $response['CreditCardName']);
            $payment->setAdditionalInformation('CreditCardNumber', $response['MaskedPan']);
            $payment->setAdditionalInformation('PaymentPlan', $response['PaymentPlan']);
            $payment->setAdditionalInformation('DateTime', $response['TransactionDateTime']);
        }

        /** @noinspection PhpParamsInspection */
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $response
        );
        $payment->setTransactionAdditionalInfo('paidUsingToken', true);

        if (!$payment->getOrder()->getEmailSent()) {
            $this->orderSender->send($payment->getOrder());
        }
    }
}
