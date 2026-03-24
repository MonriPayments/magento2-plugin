<?php

namespace Monri\Payments\Gateway\Response\GooglePay;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;

class StatusHandler implements HandlerInterface
{
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
