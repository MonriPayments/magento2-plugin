<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Validator\WSPay;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Monri\Payments\Gateway\Config\WSPay;

class CurrencyValidator extends AbstractValidator
{
    /**
     * @var WSPay
     */
    private $config;

    /**
     * CurrencyValidator constructor.
     * @param WSPay $config
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        WSPay $config,
        ResultInterfaceFactory $resultFactory
    ) {
        parent::__construct($resultFactory);
        $this->config = $config;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $currency = $validationSubject['currency'];

        $availableCurrencies = $this->config->getAvailableCurrencyCodes();

        if (!in_array($currency, $availableCurrencies)) {
            return $this->createResult(false, [__('The currency selected is not supported by WSPay')]);
        }

        return $this->createResult(true);
    }
}
