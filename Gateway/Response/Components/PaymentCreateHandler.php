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
    const INITIAL_DATA = 'initial_payment_data';
    const TIME_LIMIT_TTL = 'ttl';
    /**
     * @param array $handlingSubject
     * @param array $response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $stateObject = SubjectReader::readStateObject($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $response['base_grand_total'] = $payment->getQuote()->getBaseGrandTotal();
        $payment->setAdditionalInformation(self::INITIAL_DATA, $response);
        $payment->setAdditionalInformation(self::TIME_LIMIT_TTL, $stateObject->getData('ttl'));
    }
}
