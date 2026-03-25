<?php

namespace Monri\Payments\Gateway\Response\GooglePay;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;

class StatusHandler implements HandlerInterface
{
    /**
     * Updates payment additional information with gateway response details.
     *
     * @param array $handlingSubject The handling subject containing contextual information for processing.
     * @param array $response The response data received from the payment gateway.
     *
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        // Save gateway status info for use is payment handler
        $payment->setAdditionalInformation('gateway_status', $response['status'] ?? null);
        $payment->setAdditionalInformation('gateway_response_code', $response['response-code'] ?? null);
        $payment->setAdditionalInformation('gateway_transaction_type', $response['transaction-type'] ?? null);
        $payment->setAdditionalInformation('gateway_approval_code', $response['approval-code'] ?? null);
    }
}
