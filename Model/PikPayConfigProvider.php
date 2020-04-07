<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Leftor\PikPay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class PikPayConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCodes = [
        PikPay::PAYMENT_METHOD_PIKPAY_CODE,
    ];

    /**
     * @var \Magento\Payment\Model\Method\AbstractMethod[]
     */
    protected $methods = [];

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @param PaymentHelper $paymentHelper
     * @param Escaper $escaper
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper
    ) {
        $this->escaper = $escaper;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                $config['payment']['installments'][$code] = $this->getInstallmentsNumber($code);
                $config['payment']['have_installments'][$code] = $this->haveInstallments($code);
                $config['payment']['installments_minimum'][$code] = $this->installmentsMinimum($code);
                $config['payment']['cc_bins'][$code] = $this->getBins($code);
                $config['payment']['payment_type'][$code] = $this->getPaymentType($code);
            }
        }
        return $config;
    }

    /**
     * Get instructions text from config
     *
     * @param string $code
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }

    /**
     * @param $code
     * @return mixed
     */
    protected function getInstallmentsNumber($code)
    {
        $limit = $this->methods[$code]->numberOfInstallments();
        return range(1, $limit);
    }

    /**
     * @param $code
     * @return mixed
     */
    protected function haveInstallments($code)
    {
        return $this->methods[$code]->canPayInInstallments();
    }

    /**
     * @param $code
     * @return mixed
     */
    protected function installmentsMinimum($code)
    {
        return $this->methods[$code]->getInstallmentsMinimum();
    }

    protected function getBins($code)
    {
        return $this->methods[$code]->getCcBins();
    }

    protected function getPaymentType($code)
    {
        return $this->methods[$code]->getPaymentType();
    }
}
