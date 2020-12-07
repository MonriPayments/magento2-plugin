<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Response\Components;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Quote\Model\Quote\Payment;

class PaymentCreateHandler implements HandlerInterface
{
    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $payment->setAdditionalData(json_encode($response));
        // save quote payment here?

        // what is the difference between additionalData and AdditionalInformation ?!?

        /*
        $payment
            ->setAdditionalInformation('order_id', $response['orderId'])
            ->setAdditionalInformation('redirect_url', $response['redirectUrl']);
        */
    }
}
