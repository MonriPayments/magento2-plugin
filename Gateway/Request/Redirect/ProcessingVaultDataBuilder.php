<?php

namespace Monri\Payments\Gateway\Request\Redirect;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Model\PaymentToken;
use Monri\Payments\Gateway\Config;
use Monri\Payments\Helper\Formatter;
use Monri\Payments\Model\Crypto\Digest;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObjectFactory;

class ProcessingVaultDataBuilder implements BuilderInterface
{

    public const LANGUAGE_FIELD = 'language';

    public const TRANSACTION_TYPE_FIELD = 'transaction_type';

    public const AUTHENTICITY_TOKEN_FIELD = 'authenticity_token';

    public const DIGEST_FIELD = 'digest';

    public const NUMBER_OF_INSTALLMENTS_FIELD = 'number_of_installments';

    public const MOTO_FIELD = 'moto';

    public const FULL_NAME_FIELD = 'ch_full_name';

    public const ADDRESS_FIELD = 'ch_address';

    public const CITY_FIELD = 'ch_city';

    public const ZIP_FIELD = 'ch_zip';

    public const COUNTRY_FIELD = 'ch_country';

    public const PHONE_FIELD = 'ch_phone';

    public const EMAIL_FIELD = 'ch_email';

    public const IP_FIELD = 'ip';

    public const CURRENCY_FIELD = 'currency';

    public const PAN_FIELD = 'pan_token';

    public const AMOUNT_FIELD = 'amount';

    public const ORDER_INFO_FIELD = 'order_info';

    public const ORDER_NUMBER_FIELD = 'order_number';

    public const TRANSACTION = 'transaction';

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
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * ProcessingDataBuilder constructor.
     *
     * @param Formatter $formatter
     * @param Digest $digest
     * @param Config $config
     * @param UrlInterface $urlBuilder
     * @param ManagerInterface $eventManager
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        Formatter $formatter,
        Digest $digest,
        Config $config,
        UrlInterface $urlBuilder,
        ManagerInterface $eventManager,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->formatter = $formatter;
        $this->digest = $digest;
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
        $this->eventManager = $eventManager;
        $this->dataObjectFactory = $dataObjectFactory;
    }

    /**
     * Builds the processing data
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws CommandException
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();
        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $orderNumber = $order->getOrderIncrementId();
        $currencyCode = $order->getCurrencyCode();
        $ipAddress = $order->getRemoteIp();
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

        $isMoto = true;

        $billingAddress = $order->getBillingAddress();

        $extensionAttributes = $payment->getExtensionAttributes();
        /** @var PaymentToken $paymentToken */
        $paymentToken = $extensionAttributes->getVaultPaymentToken();
        if ($paymentToken === null) {
            throw new CommandException(__('The Payment Token is not available to perform the request.'));
        }

        $orderInfo = __('Order %1', $orderNumber)->render();

        $transportObject = $this->dataObjectFactory->create([
            'data' => [
                'description' => $orderInfo
            ]
        ]);

        // For custom order descriptions
        $this->eventManager->dispatch('monri_payments_order_description_after', [
            'order' => $order,
            'payment' => $paymentDataObject->getPayment(),
            'transportObject' => $transportObject
        ]);

        $orderInfo = $transportObject->getData('description');

        $payload =  [
            self::TRANSACTION => [
                self::TRANSACTION_TYPE_FIELD => $this->config->getTransactionType($order->getStoreId()),
                self::AMOUNT_FIELD => $amount,
                self::IP_FIELD => $ipAddress,
                self::ORDER_INFO_FIELD => $orderInfo,
                self::ADDRESS_FIELD => $billingAddress->getStreetLine1(),
                self::CITY_FIELD => $billingAddress->getCity(),
                self::COUNTRY_FIELD => $billingAddress->getCountryId(),
                self::EMAIL_FIELD => $billingAddress->getEmail(),
                self::FULL_NAME_FIELD => $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname(),
                self::PHONE_FIELD => $billingAddress->getTelephone(),
                self::ZIP_FIELD => $billingAddress->getPostcode(),
                self::CURRENCY_FIELD => $currencyCode,
                self::DIGEST_FIELD => $digest,
                self::ORDER_NUMBER_FIELD => $orderNumber,
                self::AUTHENTICITY_TOKEN_FIELD => $authToken,
                self::LANGUAGE_FIELD => $languageCode,
                self::PAN_FIELD => $paymentToken->getGatewayToken(),
                self::MOTO_FIELD => $isMoto,
            ]
        ];

        return $payload;
    }
}
