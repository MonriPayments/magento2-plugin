<?php

namespace Monri\Payments\Gateway\Validator;

use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Monri\Payments\Gateway\Config;

class CurrencyValidator extends \Magento\Payment\Gateway\Validator\AbstractValidator
{

    /**
     * @var Config
     */
    private $config;

    /**
     * CurrencyValidator constructor.
     * @param Config $config
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        Config $config,
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
        $storeId = $validationSubject['storeId'];

        $availableCurrencies = $this->config->getAvailableCurrencyCodes($storeId);

        if (!in_array($currency, $availableCurrencies)) {
            return $this->createResult(false, [__('The currency selected is not supported by Monri Payments.')]);
        }

        return $this->createResult(true);
    }
}
