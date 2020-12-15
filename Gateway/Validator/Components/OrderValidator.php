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

        $trasactionData = (array) $paymentDataObject->getPayment()->getAdditionalInformation('transaction_data');
        //$initialPaymentData = (array) $paymentData->getPayment()->getAdditionalInformation('initial_payment_data');

        /*if(!isset($trasactionData['order_number'])
            || !isset($initialPaymentData['order_number'])
            || $trasactionData['order_number'] !== $initialPaymentData['order_number']){
            $isValid = false;
            $errorMessages[] = __('Order Number is wrong.');
        }*/

        if($payment->getOrder()->getBaseGrandTotal() != ($trasactionData['amount']/100)) {
            $isValid = false;
            $errorMessages[] = __('Order is not valid.');
        }

        if(!isset($trasactionData['status']) || $trasactionData['status'] != 'approved'){
            $isValid = false;
            $errorMessages[] = __('Transaction was declined.');
        }

        return $this->createResult($isValid, $errorMessages);
    }
}
