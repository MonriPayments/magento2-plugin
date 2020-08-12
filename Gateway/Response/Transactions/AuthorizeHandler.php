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
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Monri\Payments\Gateway\Exception\TransactionAlreadyProcessedException;

class AuthorizeHandler extends AbstractTransactionHandler
{

    /**
     * Processes a successful authorize transaction.
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
            $response
        );

        if (!$order->canInvoice() || $this->checkIfTransactionProcessed($payment)) {
            // Already processed this transaction.
            throw new TransactionAlreadyProcessedException(
                __('Transaction %1 already processed.', $payment->getTransactionId())
            );
        }

        $payment->setIsTransactionClosed(false);
        $payment->registerAuthorizationNotification($payment->getOrder()->getBaseGrandTotal());
    }
}
