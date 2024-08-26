<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Response\WSPay;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;

abstract class AbstractTransactionHandler implements HandlerInterface
{
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var TransactionResource
     */
    private $transactionResource;

    /**
     * AbstractTransactionHandler constructor.
     *
     * @param TransactionFactory $transactionFactory
     * @param TransactionResource $transactionResource
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        TransactionResource $transactionResource
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->transactionResource = $transactionResource;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @throws AlreadyExistsException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();

        $this->handleTransaction($payment, $order, $response);
    }

    /**
     * Processes a transaction.
     *
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     * @param array $response
     * @throws AlreadyExistsException
     */
    abstract protected function handleTransaction(
        OrderPaymentInterface $payment,
        OrderInterface $order,
        array $response
    );
}
