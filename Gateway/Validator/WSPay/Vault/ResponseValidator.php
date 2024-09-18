<?php

namespace Monri\Payments\Gateway\Validator\WSPay\Vault;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class ResponseValidator extends AbstractValidator
{
    /**
     * @inheritDoc
     */
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);

        if ($response['ActionSuccess'] !== '1' || $response['Approved'] !== '1') {
            return $this->createResult(false, [__('Transaction has been declined.')]);
        }

        return $this->createResult(true);
    }
}
