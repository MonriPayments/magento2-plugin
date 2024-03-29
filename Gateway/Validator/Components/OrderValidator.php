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
use Magento\Payment\Gateway\Helper\SubjectReader;
use Monri\Payments\Gateway\Request\Components\OrderDetailsBuilder;

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
        $isValid = true;
        $errorMessages = [];

        $paymentDataObject = SubjectReader::readPayment($validationSubject);
        $payment = $paymentDataObject->getPayment();

        $transactionData = (array)$payment->getAdditionalInformation('transaction_data');
        $orderNumber = $payment->getAdditionalInformation(OrderDetailsBuilder::ORDER_NUMBER_FIELD);

        if ($orderNumber != $transactionData['order_number']) {
            $isValid = false;
            $errorMessages[] = __('Order ID is not valid.');
        }

        if ($payment->getOrder()->getBaseGrandTotal() != ($transactionData['amount'] / 100)) {
            $isValid = false;
            $errorMessages[] = __('Order amount is not valid.');
        }

        if (!isset($transactionData['status']) || $transactionData['status'] != 'approved') {
            $isValid = false;
            $errorMessages[] = __('Transaction has status declined.');
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
