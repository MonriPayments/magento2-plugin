<?php

namespace Monri\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Payment;

class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * DataAssignObserver constructor.
     *
     * @param Json $jsonSerializer
     */
    public function __construct(Json $jsonSerializer)
    {
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Assign needed data to Components payment
     *
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (!is_array($additionalData)) {
            return;
        }

        $transactionData = $additionalData['transaction_data'] ?? [];
        if (is_string($transactionData)) {
            try {
                $transactionData = $this->jsonSerializer->unserialize($transactionData);
            } catch (\Exception $e) {
                return;
            }
        }

        $paymentModel = $this->readPaymentModelArgument($observer);
        if (!$paymentModel instanceof Payment) {
            return;
        }
        $paymentModel->setAdditionalInformation('transaction_data', $transactionData);
    }
}
