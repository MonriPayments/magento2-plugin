<?php

namespace Monri\Payments\Gateway\Request\Redirect;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Monri\Payments\Gateway\Config;
use Monri\Payments\Helper\Formatter;
use Monri\Payments\Model\Crypto\Digest;

class ProcessingDataBuilder implements BuilderInterface
{
    const LANGUAGE_FIELD = 'language';

    const TRANSACTION_TYPE_FIELD = 'transaction_type';

    const AUTHENTICITY_TOKEN_FIELD = 'authenticity_token';

    const DIGEST_FIELD = 'digest';

    const NUMBER_OF_INSTALLMENTS_FIELD = 'number_of_installments';

    const MOTO_FIELD = 'moto';

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Digest
     */
    private $digest;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Formatter $formatter,
        Digest $digest,
        Config $config
    ) {
        $this->formatter = $formatter;
        $this->digest = $digest;
        $this->config = $config;
    }

    /**
     * Builds the processing data
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();

        $orderNumber = $order->getOrderIncrementId();
        $currencyCode = $order->getCurrencyCode();
        $amount = $this->formatter->formatPrice(
            $order->getGrandTotalAmount()
        );

        $authToken = $this->config->getClientAuthenticityToken($order->getStoreId());

        $clientKey = $this->config->getClientKey($order->getStoreId());
        $digest = $this->digest->build($clientKey, $orderNumber, $currencyCode, $amount);

        $languageCode = $this->config->getGatewayLanguage($order->getStoreId());

        // @TODO: Discuss with Weiler how to determine a MOTO order.
        // @TODO: Discuss number of installments as well.
        $isMoto = false;

        return [
            self::LANGUAGE_FIELD => $languageCode,
            self::TRANSACTION_TYPE_FIELD => $this->config->getTransactionType($order->getStoreId()),
            self::AUTHENTICITY_TOKEN_FIELD => $authToken,
            self::DIGEST_FIELD => $digest,
            self::MOTO_FIELD => $isMoto,
        ];
    }
}
