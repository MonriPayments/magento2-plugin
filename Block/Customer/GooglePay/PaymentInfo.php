<?php

namespace Monri\Payments\Block\Customer\GooglePay;
use Magento\Framework\Exception\InputException;
use Magento\Framework\View\Element\Template;

class PaymentInfo extends Template
{
    public function getPaymentData()
    {
        $orderId = $this->checkoutSession->getData('last_order_id');

        if (!$orderId) {
            $log['errors'][] = 'Missing order ID field.';
            throw new InputException(__('Missing fields.'));
        }

        $order = $this->orderRepository->get($orderId);

        $payment = $order->getPayment();

        $this->commandManager->executeByCode('create_request', $payment);

        return $payment->getAdditionalInformation(
            PaymentCreateHandler::INITIAL_DATA
        );
    }
}
