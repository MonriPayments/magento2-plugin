<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Validator\WSPay;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;

class TransferResponseValidator extends AbstractValidator
{

    /**
     * Validates a given transfer request, checking if the gateway response is valid.
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        /** @var array $response */
        $response = SubjectReader::readResponse($validationSubject);

        $actionSuccess = isset($response['ActionSuccess']) ? $response['ActionSuccess'] : null;
        if ($actionSuccess === '1') {
            return $this->createResult(true);
        }

        $responseMessage = isset($response['ErrorMessage']) ? __($response['ErrorMessage']) : '';

        $responseMessages = [$responseMessage];

        return $this->createResult(false, $responseMessages);
    }
}
