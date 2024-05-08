<?php

namespace Monri\Payments\Gateway\Validator\WSPay\Form;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Monri\Payments\Gateway\Config\WSPay;
use Magento\Payment\Gateway\Validator\ResultInterface;

class ResponseValidator extends AbstractValidator
{
    /**
     * @var WSPay
     */
    private $config;

    /**
     * @var array
     */
    private $requiredParams = [
        'ShoppingCartID',
        'Success',
        'ApprovalCode',
        'Signature'
    ];

    /**
     * SuccessValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param WSPay $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        WSPay $config
    ) {
        parent::__construct($resultFactory);
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);

        foreach ($this->requiredParams as $paramName) {
            if (!isset($response[$paramName])) {
                return $this->createResult(false, [__('Gateway response is missing required parameters.')]);
            }
        }


        $shopID = $this->config->getValue('shop_id');
        $secretKey = $this->config->getValue('secret_key');
        $signature =
            $shopID . $secretKey .
            $response['ShoppingCartID'] . $secretKey .
            $response['Success'] . $secretKey .
            $response['ApprovalCode'] . $secretKey;

        $signature = hash('sha512', $signature);

        if ($signature !== $response['Signature']) {
            return $this->createResult(false, [__('Gateway response is not valid.')]);
        }

        return $this->createResult(true);
    }

}
