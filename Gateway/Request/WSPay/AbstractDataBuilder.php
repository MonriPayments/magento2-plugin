<?php

namespace Monri\Payments\Gateway\Request\WSPay;

use Monri\Payments\Gateway\Config\WSPay as WSPayConfig;
use Magento\Payment\Gateway\Request\BuilderInterface;

abstract class AbstractDataBuilder implements BuilderInterface
{
    public const VERSION = '2.0';

    /**
     * Required form field constants
     */
    public const FIELD_SHOP_ID = 'ShopID';
    public const FIELD_ORDER_ID = 'ShoppingCartID';
    public const FIELD_LANGUAGE = 'Lang';
    public const FIELD_AMOUNT = 'TotalAmount';
    public const FIELD_SIGNATURE = 'Signature';
    public const FIELD_VERSION = 'Version';

    /**
     * Optional form field constants
     */
    public const FIELD_CUSTOMER_NAME = 'CustomerFirstName';
    public const FIELD_CUSTOMER_SURNAME = 'CustomerLastName';
    public const FIELD_CUSTOMER_ADDRESS = 'CustomerAddress';
    public const FIELD_CUSTOMER_CITY = 'CustomerCity';
    public const FIELD_CUSTOMER_ZIP_CODE = 'CustomerZIP';
    public const FIELD_CUSTOMER_COUNTRY = 'CustomerCountry';
    public const FIELD_CUSTOMER_PHONE = 'CustomerPhone';
    public const FIELD_CUSTOMER_EMAIL = 'CustomerEmail';

    /**
     * Save card for later
     */
    public const FIELD_IS_TOKEN_REQUEST = 'IsTokenRequest';

    /**
     * @var WSPayConfig
     */
    protected $config;

    /**
     * FormDataBuilder constructor.
     *
     * @param WSPayConfig $config
     */
    public function __construct(
        WSPayConfig $config
    ) {
        $this->config = $config;
    }

    /**
     * Trim and respect length
     *
     * @param string $string
     * @param bool|int $length
     * @return string
     */
    protected function prepareString($string, $length = false): string
    {
        $string = trim($string);
        if ($length > 0) {
            $string = substr($string, 0, $length);
        }

        return $string;
    }

    /**
     * Generate signature algo
     *
     * @param string $shoppingCartId
     * @param string $formattedAmount
     * @return string
     */
    protected function generateSignature(string $shoppingCartId, string $formattedAmount): string
    {
        $shopId = $this->config->getValue('shop_id');
        $secretKey = $this->config->getValue('secret_key');

        $cleanTotalAmount = str_replace(',', '', $formattedAmount);
        $signature =
            $shopId . $secretKey .
            $shoppingCartId . $secretKey .
            $cleanTotalAmount . $secretKey;

        $signature = hash('sha512', $signature);
        return $signature;
    }
    /**
     * Generate refund signature algo
     *
     * @param string $STAN
     * @param string $approvalCode
     * @param string $WsPayOrderId
     * @param string $formattedAmount
     * @return string
     */
    protected function generateRefundSignature($STAN, $approvalCode, $WsPayOrderId, $formattedAmount): string
    {
        $shopId = $this->config->getValue('shop_id');
        $secretKey = $this->config->getValue('secret_key');
        $cleanTotalAmount = str_replace(',', '', $formattedAmount);
        $signature =
            $shopId . $WsPayOrderId .
            $secretKey . $STAN .
            $secretKey . $approvalCode .
            $secretKey . $cleanTotalAmount .
            $secretKey . $WsPayOrderId;

        $signature = hash('sha512', $signature);
        return $signature;
    }
}
