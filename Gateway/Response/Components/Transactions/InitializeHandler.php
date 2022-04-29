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
use Monri\Payments\Block\Adminhtml\Config\Source\TransactionTypes;
use Monri\Payments\Gateway\Config\Components as ComponentsConfig;

class InitializeHandler implements HandlerInterface
{
    /**
     * @var ComponentsConfig
     */
    private $config;

    /**
     * InitializeHandler constructor.
     *
     * @param ComponentsConfig $config
     */
    public function __construct(
        ComponentsConfig $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);

        $stateObject = SubjectReader::readStateObject($handlingSubject);
        $stateObject->setData('state', Order::STATE_PROCESSING);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $transactionData = $payment->getAdditionalInformation('transaction_data');

        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $transactionData);

        $payment
            ->setTransactionId($this->getTransactionId($transactionData))
            ->setIsTransactionClosed(0);

        switch ($this->config->getPaymentAction()) {
            case TransactionTypes::ACTION_AUTORIZE:
                $payment->registerAuthorizationNotification($payment->getOrder()->getBaseGrandTotal());
                break;
            case TransactionTypes::ACTION_PURCHASE:
                $payment->registerCaptureNotification($payment->getOrder()->getBaseGrandTotal());
                break;
        }
    }

    /**
     * Resolve transaction id in data
     *
     * @param array $transactionData
     * @return string
     */
    protected function getTransactionId(array $transactionData)
    {
        $orderNumber = $transactionData['order_number'];
        $secretCode = $transactionData['data_secret'];

        return "{$orderNumber}-{$secretCode}";
    }
}
