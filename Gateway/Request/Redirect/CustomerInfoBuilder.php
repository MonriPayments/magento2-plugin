<?php

/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Monri\Payments\Gateway\Request\Redirect;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Monri\Payments\Helper\Formatter;

class CustomerInfoBuilder implements BuilderInterface
{
    const FULL_NAME_FIELD = 'ch_full_name';

    const ADDRESS_FIELD = 'ch_address';

    const CITY_FIELD = 'ch_city';

    const ZIP_FIELD = 'ch_zip';

    const COUNTRY_FIELD = 'ch_country';

    const PHONE_FIELD = 'ch_phone';

    const EMAIL_FIELD = 'ch_email';

    /**
     * @var Formatter
     */
    private $formatter;

    public function __construct(
        Formatter $formatter
    ) {
        $this->formatter = $formatter;
    }

    /**
     * Builds the customer information object.
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();

        $billingAddress = $order->getBillingAddress();

        $firstName = $billingAddress->getFirstname();
        $lastName = $billingAddress->getLastname();
        $customerName = "{$firstName} {$lastName}";

        $streetAddress = $billingAddress->getStreetLine1();

        $city = $billingAddress->getCity();

        $zipCode = $billingAddress->getRegionCode();

        $countryCode = $billingAddress->getCountryId();

        $phoneNumber = $billingAddress->getTelephone();

        $email = $billingAddress->getEmail();

        return [
            self::FULL_NAME_FIELD => $this->formatter->formatText($customerName, 30),
            self::ADDRESS_FIELD => $this->formatter->formatText($streetAddress, 100),
            self::CITY_FIELD => $this->formatter->formatText($city, 30),
            self::ZIP_FIELD => $this->formatter->formatText($zipCode, 9),
            self::COUNTRY_FIELD => $countryCode,
            self::PHONE_FIELD => $this->formatter->formatText($phoneNumber, 30),
            self::EMAIL_FIELD => $this->formatter->formatText($email, 100, false),
        ];
    }
}
