<?php

namespace Monri\Payments\Gateway\Request\WSPay;

use Monri\Payments\Gateway\Request\WSPay\AbstractDataBuilder;
use Monri\Payments\Gateway\Config\WSPayVaultConfig;
use Monri\Payments\Gateway\Helper\TestModeHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Helper\SubjectReader;

class VaultDataBuilder extends AbstractDataBuilder
{
    public const FIELD_TOKEN = 'Token';
    public const FIELD_TOKEN_NUMBER = 'TokenNumber';

    public const FIELD_DATETIME = 'DateTime';
    public const FIELD_LANGUAGE = 'Language';

    /**
     * @var TimezoneInterface
     */
    private $localeDate;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * VaultDataBuilder constructor.
     *
     * @param WSPayVaultConfig $config
     * @param TimezoneInterface $timezone
     * @param Json $jsonSerializer
     */
    public function __construct(
        WSPayVaultConfig $config,
        TimezoneInterface $timezone,
        Json $jsonSerializer,
    ) {
        parent::__construct($config);
        $this->localeDate = $timezone;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @inheritDoc
     * @throws CommandException
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        /** @var  \Magento\Payment\Gateway\Data\AddressAdapterInterface $billingAddress */
        $billingAddress = $order->getBillingAddress();

        $orderId = $order->getOrderIncrementId();
        if ($this->config->getValue('test_mode')) {
            $orderId = TestModeHelper::generateTestOrderId($orderId);
        }


        $formattedAmount = number_format($order->getGrandTotalAmount(), 2, '', '');

        $extensionAttributes = $payment->getExtensionAttributes();
        /** @var \Magento\Vault\Model\PaymentToken $paymentToken */
        $paymentToken = $extensionAttributes->getVaultPaymentToken();
        if ($paymentToken === null) {
            throw new CommandException(__('The Payment Token is not available to perform the request.'));
        }

        //@todo: replace with serializer
        $tokenDetails = $this->jsonSerializer->unserialize($paymentToken->getTokenDetails());
        $shopId = $this->config->getValue('shop_id', $order->getStoreId());

        return [
            self::FIELD_VERSION => self::VERSION,
            self::FIELD_SHOP_ID => $shopId,
            self::FIELD_ORDER_ID => $orderId,

            self::FIELD_TOKEN => $paymentToken->getGatewayToken(),
            self::FIELD_TOKEN_NUMBER => $tokenDetails['maskedCC'],

            'PaymentPlan' => '0000',

            self::FIELD_AMOUNT => $formattedAmount,
            // use order createdAt in website timezone? $this->localeDate->date(new \DateTime($order->getCreatedAt())),
            self::FIELD_DATETIME => $this->localeDate->date()->format('YmdHis'),
            self::FIELD_CUSTOMER_NAME => $this->prepareString($billingAddress->getFirstname()),
            self::FIELD_CUSTOMER_SURNAME => $this->prepareString($billingAddress->getLastname()),
            self::FIELD_CUSTOMER_ADDRESS => $this->prepareString($billingAddress->getStreetLine1()),
            self::FIELD_CUSTOMER_CITY => $this->prepareString($billingAddress->getCity()),
            self::FIELD_CUSTOMER_ZIP_CODE => $this->prepareString($billingAddress->getPostcode()),
            self::FIELD_CUSTOMER_COUNTRY => $this->prepareString($billingAddress->getCountryId()),
            self::FIELD_CUSTOMER_PHONE => $this->prepareString($billingAddress->getTelephone()),
            self::FIELD_CUSTOMER_EMAIL => $this->prepareString($billingAddress->getEmail()),

            self::FIELD_LANGUAGE => $this->config->getValue('language'),
            self::FIELD_SIGNATURE => $this->generateSignature($orderId, $formattedAmount),

        ];
    }
}
