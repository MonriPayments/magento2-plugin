<?php

namespace Monri\Payments\Gateway\Response\WSPay\Form;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class PaymentReviewHandler implements HandlerInterface
{
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var OrderSender
     */
    private OrderSender $orderSender;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * CancelHandler constructor.
     *
     * @param OrderManagementInterface $orderManagement
     * @param OrderSender $orderSender
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderManagementInterface $orderManagement,
        OrderSender $orderSender,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderManagement = $orderManagement;
        $this->checkoutSession = $checkoutSession;
        $this->orderSender = $orderSender;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        if ($order->getId() != $this->checkoutSession->getLastOrderId()) {
            return;
        }

        if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
            return;
        }

        $order->setStatus(Order::STATE_PAYMENT_REVIEW);

        // send new order email
        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
        }

        // update order
        $this->orderRepository->save($order);
    }
}
