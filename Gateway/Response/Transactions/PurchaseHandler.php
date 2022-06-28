<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Response\Transactions;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;
use Monri\Payments\Gateway\Exception\TransactionAlreadyProcessedException;
use Monri\Payments\Gateway\Helper\RawDetailsFormatter;

class PurchaseHandler extends AbstractTransactionHandler
{
    /**
     * @var RawDetailsFormatter
     */
    private $rawDetailsFormatter;

    /**
     * PurchaseHandler constructor.
     *
     * @param TransactionFactory $transactionFactory
     * @param TransactionResource $transactionResource
     * @param RawDetailsFormatter $rawDetailsFormatter
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        TransactionResource $transactionResource,
        RawDetailsFormatter $rawDetailsFormatter
    ) {
        parent::__construct($transactionFactory, $transactionResource);
        $this->rawDetailsFormatter = $rawDetailsFormatter;
    }

    /**
     * Processes a successful purchase transaction.
     *
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     * @param array $response
     * @throws TransactionAlreadyProcessedException
     */
    protected function handleTransaction(OrderPaymentInterface $payment, OrderInterface $order, array $response)
    {
        /** @var Payment $payment */
        $payment->setTransactionId($this->getTransactionId($response));
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $this->rawDetailsFormatter->format($response)
        );

        if (!$order->canInvoice() || $this->checkIfTransactionProcessed($payment)) {
            // Already processed this transaction.
            throw new TransactionAlreadyProcessedException(
                __('Transaction %1 already processed.', $payment->getTransactionId())
            );
        }

        $payment->getOrder()->setState(Order::STATE_PROCESSING);
        $payment->registerCaptureNotification($payment->getOrder()->getBaseGrandTotal());
    }
}
