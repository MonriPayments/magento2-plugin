<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Response\WSPay;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;
use Magento\Sales\Model\Order\Payment;

class UnsuccessfulHandler extends AbstractTransactionHandler
{
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * UnsuccessfulHandler constructor.
     *
     * @param TransactionFactory $transactionFactory
     * @param TransactionResource $transactionResource
     * @param Logger $logger
     * @param OrderManagementInterface $orderManagement
     */
    public function __construct(
        TransactionFactory $transactionFactory,
        TransactionResource $transactionResource,
        Logger $logger,
        OrderManagementInterface $orderManagement
    ) {
        parent::__construct($transactionFactory, $transactionResource);
        $this->orderManagement = $orderManagement;
        $this->logger = $logger;
    }

    /**
     * Processes a transaction.
     *
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     * @param array $response
     */
    protected function handleTransaction(OrderPaymentInterface $payment, OrderInterface $order, array $response)
    {
        try {
            if (isset($response['ActionSuccess'])) {
                /** @var Payment $payment */
                $payment->setAdditionalInformation(
                    'gateway_response_code',
                    $response['ActionSuccess']
                );
            }
        } catch (LocalizedException $e) {
            $this->logger->debug(['Failed to set gateway response code for payment: ' . $e->getMessage()]);
        }

        $this->orderManagement->cancel($order->getEntityId());
    }
}
