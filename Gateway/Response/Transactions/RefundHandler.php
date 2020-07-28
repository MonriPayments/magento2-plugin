<?php


namespace Monri\Payments\Gateway\Response\Transactions;


use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

class RefundHandler extends AbstractTransactionHandler
{

    /**
     * Processes a refund transaction.
     *
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     * @param array $response
     */
    protected function handleTransaction(OrderPaymentInterface $payment, OrderInterface $order, array $response)
    {
        $payment->setShouldCloseParentTransaction(true);
    }
}
