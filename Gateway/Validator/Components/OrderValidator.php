<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Validator\Components;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Monri\Payments\Gateway\Config;

class OrderValidator extends AbstractValidator
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

        return $this->createResult(true);
    }
}
