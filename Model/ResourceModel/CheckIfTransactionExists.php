<?php

namespace Monri\Payments\Model\ResourceModel;

use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;

class CheckIfTransactionExists
{
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var TransactionResource
     */
    private $transactionLoader;
    /**
     * CheckIfTransactionExists constructor.
     *
     * @param TransactionFactory $transactionFactory ,
     * @param TransactionResource $transactionLoader
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        TransactionResource $transactionLoader
    ) {
        $this->transactionFactory = $transactionFactory;
        $this->transactionLoader = $transactionLoader;
    }

    /**
     * Checks if transaction exists
     *
     * @param Payment $payment
     * @return bool
     */
    public function execute(Payment $payment): bool
    {
        $transactionTxnId = $payment->getTransactionId();
        $paymentId = $payment->getId();
        $orderId = $payment->getOrder()->getId();

        /** @var \Magento\Sales\Model\Order\Payment\Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $this->transactionLoader->loadObjectByTxnId(
            $transaction,
            $orderId,
            $paymentId,
            $transactionTxnId
        );

        return (bool) $transaction->getId();
    }
}
