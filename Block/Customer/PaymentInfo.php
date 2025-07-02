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
        private Template\Context $context,
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
        $payment = $order->getPayment();
        $additionalTransactionInfo = $this->config->getTransactionInfoInOrder($order->getStoreId());
        $methodCode = $payment->getMethod();

        if ($methodCode === 'monri_wspay' && $additionalTransactionInfo) {
            return $order->getPayment()->getAdditionalInformation();
        }

        return [];
    }
}
