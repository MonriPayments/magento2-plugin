<?php

namespace Monri\Payments\Gateway\Command\WSPay;

use Monri\Payments\Gateway\Command\WSPay\Form\ResponseCommand;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Command\ResultInterface;

class CallbackCommand extends ResponseCommand
{
    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject): ?ResultInterface
    {
        $response = SubjectReader::readResponse($commandSubject);

        $this->logger->debug(['callback' => $response]);

        // validate callback response
        $result = $this->validator->validate($commandSubject);

        if (!$result->isValid()) {
            throw new CommandException(
                $result->getFailsDescription()
                    ? __(implode(', ', $result->getFailsDescription()))
                    : __('Gateway response is not valid.')
            );
        }

        if ($response['ActionSuccess'] !== '1') {
            return null;
        }

        // @todo: execute success or cancel handler based on this; we're currently only handling authorize/capture
        if (!in_array($this->getCallbackAction($response), ['Authorized', 'Completed'])) {
            return null;
        }

        // load order
        $orderIncrementId = $response['ShoppingCartID'];
        $order = $this->getOrder($orderIncrementId);
        if (!$order) {
            throw new CommandException(__('Payment order not found.'));
        }

        $actionCommandSubject = [
            'response' => $response,
            'payment' => $this->paymentDataObjectFactory->create(
                $order->getPayment()
            )
        ];

        $this->handler->handle($actionCommandSubject, $response);
        return null;
    }

    /**
     * @param array $response
     * @return string
     */
    private function getCallbackAction(array $response): string
    {
        $orderedActions = ['Refunded', 'Voided', 'Completed', 'Authorized'];
        $result = 'Unknown';
        foreach ($orderedActions as $action) {
            if ($response[$action] === '1') {
                $result = $action;
                break;
            }
        }

        return $result;
    }
}
