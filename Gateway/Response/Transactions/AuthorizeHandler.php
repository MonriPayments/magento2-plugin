<?php


namespace Monri\Payments\Gateway\Response\Transactions;


use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

class AuthorizeHandler extends AbstractTransactionHandler
{

    /**
     * Processes a successful authorize transaction.
     *
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     * @param array $response
     * @throws AlreadyExistsException
     */
    protected function handleTransaction(OrderPaymentInterface $payment, OrderInterface $order, array $response)
    {
        /** @var Payment $payment */
        $payment->setTransactionId($this->getTransactionId($response));
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $response
        );

        if (!$order->canInvoice() || $this->checkIfTransactionProcessed($payment)) {
            // Already processed this transaction.
            throw new AlreadyExistsException(__('Transaction already processed.'));
        }

        $payment->setIsTransactionClosed(false);
        $payment->registerAuthorizationNotification($payment->getOrder()->getBaseGrandTotal());
    }
}
