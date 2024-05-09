<?php

namespace Monri\Payments\Gateway\Request\WSPay;

use Monri\Payments\Gateway\Config\WSPay;
use Monri\Payments\Gateway\Helper\TestModeHelper;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class FormDataBuilder extends AbstractDataBuilder
{
    public const FIELD_RETURN_URL = 'ReturnUrl';
    public const FIELD_CANCEL_URL = 'CancelUrl';
    public const FIELD_RETURN_ERROR_URL = 'ReturnErrorURL';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * FormDataBuilder constructor.
     *
     * @param WSPay $config
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        WSPay $config,
        UrlInterface $urlBuilder
    ) {
        parent::__construct($config);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Build form request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject): array
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        $billingAddress = $order->getBillingAddress();

        $orderId = $order->getOrderIncrementId();
        if ($this->config->getValue('test_mode')) {
            $orderId = TestModeHelper::generateTestOrderId($orderId);
        }

        $configStoreId = $this->config->getValue('shop_id');
        $formattedAmount = number_format($order->getGrandTotalAmount(), 2, ',', '');

        $data = [
            self::FIELD_VERSION => self::VERSION,

            self::FIELD_SHOP_ID => $configStoreId,
            self::FIELD_ORDER_ID => $orderId,
            self::FIELD_LANGUAGE => $this->config->getValue('language'),
            self::FIELD_AMOUNT => $formattedAmount,

            self::FIELD_SIGNATURE => $this->generateSignature($orderId, $formattedAmount),

            self::FIELD_RETURN_URL => $this->urlBuilder->getUrl('monripayments/wspay/success'),
            self::FIELD_CANCEL_URL => $this->urlBuilder->getUrl('monripayments/wspay/cancel'),
            self::FIELD_RETURN_ERROR_URL => $this->urlBuilder->getUrl('monripayments/wspay/cancel', ['error' => '1']),

            self::FIELD_CUSTOMER_NAME => $this->prepareString($billingAddress->getFirstname()),
            self::FIELD_CUSTOMER_SURNAME => $this->prepareString($billingAddress->getLastname()),
            self::FIELD_CUSTOMER_ADDRESS => $this->prepareString($billingAddress->getStreetLine1()),
            self::FIELD_CUSTOMER_CITY => $this->prepareString($billingAddress->getCity()),
            self::FIELD_CUSTOMER_ZIP_CODE => $this->prepareString($billingAddress->getPostcode()),
            self::FIELD_CUSTOMER_COUNTRY => $this->prepareString($billingAddress->getCountryId()),
            self::FIELD_CUSTOMER_PHONE => $this->prepareString($billingAddress->getTelephone()),
            self::FIELD_CUSTOMER_EMAIL => $this->prepareString($billingAddress->getEmail())
        ];

        return [
            'action' => $this->config->getFormEndpoint((int)$order->getStoreId()),
            'fields' => $data
        ];
    }
}
