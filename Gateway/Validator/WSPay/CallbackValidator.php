<?php

namespace Monri\Payments\Gateway\Validator\WSPay;

use Monri\Payments\Gateway\Config\WSPay;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Payment\Gateway\Validator\ResultInterface;

class CallbackValidator extends AbstractValidator
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $requiredParams = [
        'ShoppingCartID',
        'ActionSuccess',
        'ApprovalCode',
        'WsPayOrderId',
        'Signature'
    ];

    /**
     * CallbackValidator constructor.
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param Config $config
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Config $config
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
            $response['ActionSuccess'] .
            $response['ApprovalCode'] .
            $secretKey . $shopID .
            $response['ApprovalCode'] .
            $response['WsPayOrderId'];

        $signature = hash('sha512', $signature);

        if ($signature !== $response['Signature']) {
            return $this->createResult(false, [__('Gateway response is not valid.')]);
        }

        return $this->createResult(true);
    }
}
