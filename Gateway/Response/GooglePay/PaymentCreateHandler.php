<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Response\GooglePay;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Quote\Model\Quote\Payment;

class PaymentCreateHandler implements HandlerInterface
{
    public const INITIAL_DATA = 'initial_payment_data';

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();
        $billing = $order->getBillingAddress();

        $response['ch_full_name'] = trim($billing->getFirstname() . ' ' . $billing->getLastname());
        $response['ch_address'] = $billing->getStreetLine(1) ?? '';
        $response['ch_city'] = $billing->getCity() ?? '';
        $response['ch_zip'] = $billing->getPostcode() ?? '';
        $response['ch_country'] = $billing->getCountryId() ?? '';
        $response['ch_phone'] = $billing->getTelephone() ?? '';
        $response['ch_email'] = $order->getCustomerEmail() ?? '';
        $response['orderInfo'] = 'Magento Order';
        $payment->setAdditionalInformation(self::INITIAL_DATA, $response);
    }
}
