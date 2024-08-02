<?php

namespace Monri\Payments\Gateway\Response\WSPay\Vault;

use Monri\Payments\Gateway\Helper\CcTypeMapper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

class CaptureHandler implements HandlerInterface
{
    /**
     * @var OrderSender $orderSender
     */
    private OrderSender $orderSender;

    /**
     * @param OrderSender $orderSender
     */
    public function __construct(
        OrderSender $orderSender
    ) {
        $this->orderSender = $orderSender;
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

        $payment->setTransactionId($response['WsPayOrderId']);
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
