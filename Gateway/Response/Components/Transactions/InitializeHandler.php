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
use Monri\Payments\Gateway\Helper\RawDetailsFormatter;

class InitializeHandler implements HandlerInterface
{
    /**
     * @var ComponentsConfig
     */
    private $config;

    /**
     * @var RawDetailsFormatter
     */
    private $rawDetailsFormatter;

    /**
     * InitializeHandler constructor.
     *
     * @param ComponentsConfig $config
     */
    public function __construct(
        ComponentsConfig $config,
        RawDetailsFormatter $rawDetailsFormatter
    ) {
        $this->config = $config;
        $this->rawDetailsFormatter = $rawDetailsFormatter;
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

        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $this->rawDetailsFormatter->format($transactionData)
        );

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
        $transactionId = $transactionData['order_number'];

        if (isset($transactionData['transaction_response']['id'])) {
            $transactionId .= $transactionData['transaction_response']['id'];
        }

        return $transactionId;
    }
}
