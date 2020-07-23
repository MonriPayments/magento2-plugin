<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;

class InitializeCommand implements CommandInterface
{

    /**
     * Initializes the payment.
     *
     * @param array $commandSubject
     */
    public function execute(array $commandSubject)
    {
        $stateObject = SubjectReader::readStateObject($commandSubject);
        $paymentDataObject = SubjectReader::readPayment($commandSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setAmountAuthorized($payment->getOrder()->getTotalDue());
        $payment->setBaseAmountAuthorized($payment->getOrder()->getBaseTotalDue());
        $payment->getOrder()->setCanSendNewEmailFlag(false);

        $stateObject->setData(OrderInterface::STATE, Order::STATE_PENDING_PAYMENT);
        $stateObject->setData(OrderInterface::STATUS, Order::STATE_PENDING_PAYMENT);
    }
}
