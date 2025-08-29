<?php

namespace Monri\Payments\Gateway\Response\Redirect\Vault;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Monri\Payments\Gateway\Config;

class CaptureHandler implements HandlerInterface
{
    /**
     * @var OrderSender $orderSender
     */
    private OrderSender $orderSender;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param OrderSender $orderSender
     * @param Config $config
     */
    public function __construct(
        OrderSender $orderSender,
        Config $config
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

        /** @noinspection PhpParamsInspection */
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $response
        );
        $payment->setTransactionAdditionalInfo('paidUsingToken', true);

        $payment->setIsTransactionClosed(0);
        $payment->setTransactionId($this->getTransactionId($response));

    }

    /**
     * Resolve transaction id from response
     *
     * @param array $response
     * @return string
     */
    protected function getTransactionId(array $response)
    {
        $orderNumber = $response['transaction']['order_number'];
        $approvalCode = $response['transaction']['approval_code'];

        return "{$orderNumber}-{$approvalCode}";
    }
}
