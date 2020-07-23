<?php

namespace Monri\Payments\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapperInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;

class GatewayResponseCommand implements CommandInterface
{
    const STATUS_FIELD = 'status';

    const STATUS_APPROVED = 'approved';

    /**
     * @var Command\Result\ArrayResultFactory
     */
    protected $arrayResultFactory;

    /**
     * @var HandlerInterface
     */
    protected $orderUpdateHandler;

    /**
     * @var ValidatorInterface
     */
    protected $validator;
    /**
     * @var ErrorMessageMapperInterface
     */
    private $errorMessageMapper;

    public function __construct(
        Command\Result\ArrayResultFactory $arrayResultFactory,
        HandlerInterface $orderUpdateHandler,
        ErrorMessageMapperInterface $errorMessageMapper,
        ValidatorInterface $validator = null
    ) {
        $this->arrayResultFactory = $arrayResultFactory;
        $this->orderUpdateHandler = $orderUpdateHandler;
        $this->validator = $validator;
        $this->errorMessageMapper = $errorMessageMapper;
    }

    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return null|Command\ResultInterface
     * @throws CommandException
     */
    public function execute(array $commandSubject)
    {
        if ($this->validator) {
            $result = $this->validator->validate($commandSubject);

            if (!$result->isValid()) {
                $this->processErrors($result);
            }
        }

        $response = SubjectReader::readResponse($commandSubject);

        $this->assertGatewayResponse($response);

        $this->orderUpdateHandler->handle($commandSubject, $response);

        $successfulPayment = $this->isPaymentApproved($response);

        $paymentDO = SubjectReader::readPayment($commandSubject);
        $responseCode = $paymentDO->getPayment()->getAdditionalInformation('gateway_response_code');

        $responseCodeMessage = null;
        if ($responseCode) {
            $responseCodeMessage = $this->errorMessageMapper->getMessage($responseCode);
        }

        return $this->arrayResultFactory->create(['array' => [
            'message' => $successfulPayment ? __('Payment successful.') : __('Payment unsuccessful.'),
            'successful' => $successfulPayment,
            'response_code' => $responseCode,
            'response_code_message' => $responseCodeMessage,
        ]]);
    }

    /**
     * Asserts the gateway response and throws an exception in case a crucial field is missing in the response.
     *
     * @param array $response
     * @throws CommandException
     */
    protected function assertGatewayResponse(array $response)
    {
        if (!array_key_exists(self::STATUS_FIELD, $response)) {
            throw new CommandException(__('Status field missing.'));
        }
    }

    /**
     * Checks to see if the gateway approved the transaction or not.
     *
     * @param array $response
     * @return bool
     */
    protected function isPaymentApproved(array $response)
    {
        return $response[self::STATUS_FIELD] === self::STATUS_APPROVED;
    }

    /**
     * @param ResultInterface $validationResult
     * @throws CommandException
     */
    protected function processErrors(ResultInterface $validationResult)
    {
        $errors = array_merge($validationResult->getErrorCodes(), $validationResult->getFailsDescription());

        $message = !empty($errors)
            ? __(implode(PHP_EOL, $errors))
            : __('There has been an issue processing your payment.');

        throw new CommandException($message);
    }
}
