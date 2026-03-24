<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Command\GooglePay;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Monri\Payments\Gateway\Config\GooglePay as Config;
use Monri\Payments\Helper\Formatter;

class InitializeCommand implements CommandInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * InitializeCommand constructor.
     *
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(
        Config $config,
        Logger $logger,
        Formatter $formatter
    ) {
        $this->config = $config;
        $this->logger = $logger;
        $this->formatter = $formatter;
    }

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

        $payment->setAmountOrdered($payment->getOrder()->getTotalDue());
        $payment->setBaseAmountOrdered($payment->getOrder()->getBaseTotalDue());
        $payment->getOrder()->setCanSendNewEmailFlag(false);

        try {
            $payment->setAdditionalInformation(
                'monri_order_number',
                $this->formatter->formatText(
                    $payment->getOrder()->getIncrementId() . uniqid('-'),
                    40
                )
            );
            $payment->setAdditionalInformation(
                'transaction_type',
                'purchase'
            );
        } catch (LocalizedException $e) {
            $this->logger->debug(['Failed to set transaction type for payment: ' . $e->getMessage()]);
        }

        $stateObject->setData(OrderInterface::STATE, Order::STATE_PENDING_PAYMENT);
        $stateObject->setData(OrderInterface::STATUS, Order::STATE_PENDING_PAYMENT);
    }
}
