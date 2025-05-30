<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Request\Redirect;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Monri\Payments\Gateway\Config;
use Monri\Payments\Helper\Formatter;
use Monri\Payments\Model\Crypto\Digest;

class ProcessingDataBuilder implements BuilderInterface
{
    public const LANGUAGE_FIELD = 'language';

    public const TRANSACTION_TYPE_FIELD = 'transaction_type';

    public const AUTHENTICITY_TOKEN_FIELD = 'authenticity_token';

    public const DIGEST_FIELD = 'digest';

    public const NUMBER_OF_INSTALLMENTS_FIELD = 'number_of_installments';

    public const MOTO_FIELD = 'moto';

    public const SUCCESS_URL_FIELD = 'success_url_override';

    public const CANCEL_URL_FIELD = 'cancel_url_override';

    public const CALLBACK_URL_FIELD = 'callback_url_override';

    public const SUPPORTED_PAYMENT_METHODS = 'supported_payment_methods';

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

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * ProcessingDataBuilder constructor.
     *
     * @param Formatter $formatter
     * @param Digest $digest
     * @param Config $config
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Formatter $formatter,
        Digest $digest,
        Config $config,
        UrlInterface $urlBuilder
    ) {
        $this->formatter = $formatter;
        $this->digest = $digest;
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
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

        /*
            Added in 2.4.8, because \PayPal\Braintree\Gateway\Data\Order\OrderAdapter puts themselves as preference for
            \Magento\Payment\Gateway\Data\Order\OrderAdapter. It declares strict types, but getGrandTotalAmount returns
            string instead of float, causing it to break execution.
        */
        try {
            $amount = $this->formatter->formatPrice(
                $order->getGrandTotalAmount()
            );
        } catch (\TypeError $e) {
            $payment = $paymentDataObject->getPayment();
            $orderObject = $payment->getOrder();
            $amount = $this->formatter->formatPrice(
                $orderObject->getBaseGrandTotal()
            );
        }

        $authToken = $this->config->getClientAuthenticityToken($order->getStoreId());

        $digest = $this->digest->build(
            $orderNumber,
            $currencyCode,
            $amount,
            $order->getStoreId()
        );

        $languageCode = $this->config->getGatewayLanguage($order->getStoreId());

        $installments = $this->config->getInstallments($order->getStoreId());

        $supportedPaymentMethods = $this->config->getSupportedPaymentMethods($order->getStoreId()) ?? 'card';

        $isMoto = false;

        $payload =  [
            self::LANGUAGE_FIELD => $languageCode,
            self::TRANSACTION_TYPE_FIELD => $this->config->getTransactionType($order->getStoreId()),
            self::AUTHENTICITY_TOKEN_FIELD => $authToken,
            self::DIGEST_FIELD => $digest,
            self::MOTO_FIELD => $isMoto,
            self::SUCCESS_URL_FIELD => $this->urlBuilder->getUrl(
                'monripayments/redirect/success',
                ['_secure' => true]
            ),
            self::CANCEL_URL_FIELD => $this->urlBuilder->getUrl(
                'monripayments/redirect/cancel',
                ['_secure' => true]
            ),
            self::CALLBACK_URL_FIELD => $this->urlBuilder->getUrl(
                'monripayments/gateway/callback',
                ['_secure' => true]
            ),
            self::SUPPORTED_PAYMENT_METHODS => $supportedPaymentMethods
        ];

        if ($installments !== Config::INSTALLMENTS_DISABLED) {
            $payload[self::NUMBER_OF_INSTALLMENTS_FIELD] = $installments;
        }

        return $payload;
    }
}
