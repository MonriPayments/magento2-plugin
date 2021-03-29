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

class CaptureHandler extends AbstractTransactionHandler
{
    /**
     * Processes a capture transaction.
     *
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     * @param array $response
     */
    protected function handleTransaction(OrderPaymentInterface $payment, OrderInterface $order, array $response)
    {
        // Allow only a single capture of a payment.
        $payment->setShouldCloseParentTransaction(true);
    }
}
