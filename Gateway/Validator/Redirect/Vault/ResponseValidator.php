<?php

namespace Monri\Payments\Gateway\Validator\Redirect\Vault;

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

        if ($response['transaction']['response_code'] !== '0000' ||
            $response['transaction']['response_message'] !== 'transaction approved') {
            return $this->createResult(false, [__('Transaction has been declined.')]);
        }

        return $this->createResult(true);
    }
}
