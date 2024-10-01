<?php

namespace Monri\Payments\Gateway\Response\WSPay\Form;

use Monri\Payments\Model\ResourceModel\CheckIfTransactionExists;
use Monri\Payments\Gateway\Config\WSPay;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction;

class CaptureHandler implements HandlerInterface
{
    /**
     * @var WSPay
     */
    private $config;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CheckIfTransactionExists
     */
    private $checkIfTransactionExists;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * CaptureHandler constructor.
     *
     * @param WSPay $config
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param CheckIfTransactionExists $checkIfTransactionExists
     */
    public function __construct(
        WSPay $config,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        CheckIfTransactionExists $checkIfTransactionExists
    ) {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->checkIfTransactionExists = $checkIfTransactionExists;
    }

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        $payment->setAdditionalInformation('STAN', $response['STAN']);
        $payment->setAdditionalInformation('ApprovalCode', $response['ApprovalCode']);
        $payment->setAdditionalInformation('originalTransactionId', $response['WsPayOrderId']);
        $payment->setTransactionId($response['WsPayOrderId']);
        /** @noinspection PhpParamsInspection */
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $response
        );
        if (!$order->canInvoice() || $this->checkIfTransactionExists->execute($payment)) {
            // No action to take, order can't be invoiced or authorized or transaction is already processed
            return;
        }
        //@todo: find what is closing the transaction instead of manually reverting the value
        $payment->setIsTransactionClosed(0);

        switch ($this->config->getValue('payment_action', $order->getStoreId())) {
            case MethodInterface::ACTION_AUTHORIZE:
                $payment->registerAuthorizationNotification(
                    $order->getBaseGrandTotal()
                );
                break;
            case MethodInterface::ACTION_AUTHORIZE_CAPTURE:
                $order->setState(Order::STATE_PROCESSING);
                $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);
                break;
        }

        // send new order email
        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
        }

        // update order
        $this->orderRepository->save($order);
    }
}
