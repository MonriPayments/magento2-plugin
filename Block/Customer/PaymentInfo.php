<?php

namespace Monri\Payments\Block\Customer;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template;
use Monri\Payments\Gateway\Config\WSPay;

class PaymentInfo extends Template
{
    /**
     * @param Template\Context $context
     * @param Session $checkoutSession
     * @param WSPay $config
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private Session $checkoutSession,
        private WSPay $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Return additional payment info if enabled and payment was Monri WSPay
     *
     * @return array|string[]
     */
    public function getAdditionalPaymentInfo()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order) {
            return [];
        }

        $additionalTransactionInfo = $this->config->getTransactionInfoInOrder($order->getStoreId());
        $payment = $order->getPayment();
        if (!$payment || $payment->getMethod() !== 'monri_wspay' || !$additionalTransactionInfo) {
            return [];
        }

        return $payment->getAdditionalInformation();
    }
}
