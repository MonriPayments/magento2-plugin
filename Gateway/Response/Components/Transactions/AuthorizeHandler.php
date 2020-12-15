<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Response\Components\Transactions;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;
use Monri\Payments\Gateway\Exception\TransactionAlreadyProcessedException;

class AuthorizeHandler implements HandlerInterface
{
    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var TransactionResource
     */
    private $transactionResource;

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
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {

        $paymentDataObject = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $transactionData = $payment->getAdditionalInformation('transaction_data');

        /** @var Order $order */
        $order = $payment->getOrder();

        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $transactionData);
        
        $payment
            ->setTransactionId($this->getTransactionId($transactionData))
            ->setIsTransactionClosed(0)
            ->registerAuthorizationNotification($payment->getOrder()->getBaseGrandTotal());

        //$payment->setTransactionId($this->getTransactionId($transactionData));


        /*$payment->setTransactionId($this->getTransactionId($transactionData));
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $transactionData
        );

        if (!$order->canInvoice() || $this->checkIfTransactionProcessed($payment)) {
            // Already processed this transaction.
            throw new TransactionAlreadyProcessedException(
                __('Transaction %1 already processed.', $payment->getTransactionId())
            );
        }*/

        //$payment->setIsTransactionClosed(false);
        //$payment->registerAuthorizationNotification($payment->getOrder()->getBaseGrandTotal());
    }

    protected function checkIfTransactionProcessed(Payment $payment)
    {
        $transactionTxnId = $payment->getTransactionId();
        $paymentId = $payment->getId();
        $orderId = $payment->getOrder()->getId();

        /** @var Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $this->transactionResource->loadObjectByTxnId(
            $transaction,
            $orderId,
            $paymentId,
            $transactionTxnId
        );

        return (bool) $transaction->getId();
    }

    protected function getTransactionId(array $transactionData)
    {
        $orderNumber = $transactionData['order_number'];
        $secretCode = $transactionData['data_secret'];

        return "{$orderNumber}-{$secretCode}";
    }
}
