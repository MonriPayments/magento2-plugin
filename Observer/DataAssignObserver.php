<?php

namespace Monri\Payments\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote\Payment;


class DataAssignObserver extends AbstractDataAssignObserver
{
    /**
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

        $paymentModel = $this->readPaymentModelArgument($observer);
        if (!$paymentModel instanceof Payment) {
            return;
        }
        $paymentModel->setAdditionalInformation('transaction_data', $additionalData);
    }
}