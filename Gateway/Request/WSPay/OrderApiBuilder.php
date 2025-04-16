<?php

namespace Monri\Payments\Gateway\Request\WSPay;

use Monri\Payments\Gateway\Config\WSPayVaultConfig;
use Monri\Payments\Gateway\Config\WSPay;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class OrderApiBuilder extends AbstractDataBuilder
{
    public const FIELD_ORDER_ID = 'WsPayOrderId';
    public const FIELD_APPROVAL_CODE = 'ApprovalCode';
    public const FIELD_STAN = 'STAN';
    public const FIELD_AMOUNT = 'Amount';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var WSPayVaultConfig
     */
    private $vaultConfig;

    /**
     * FormDataBuilder constructor.
     *
     * @param WSPay $config
     * @param UrlInterface $urlBuilder
     * @param WSPayVaultConfig $vaultConfig
     */
    public function __construct(
        WSPay $config,
        UrlInterface $urlBuilder,
        WSPayVaultConfig $vaultConfig
    ) {
        parent::__construct($config);
        $this->urlBuilder = $urlBuilder;
        $this->vaultConfig = $vaultConfig;
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

        /*
            Added in 2.4.8, because \PayPal\Braintree\Gateway\Data\Order\OrderAdapter puts themselves as preference for
            \Magento\Payment\Gateway\Data\Order\OrderAdapter. It declares strict types, but getGrandTotalAmount returns
            string instead of float, causing it to break execution.
        */
        try {
            $orderAmount = $order->getGrandTotalAmount();
        } catch (\TypeError $e) {
            $orderObject = $payment->getOrder();
            $orderAmount = $orderObject->getBaseGrandTotal();
        }

        $storeId = $order->getStoreId();
        $shopId = $payment->getAdditionalInformation('paidUsingToken') ?
            $this->vaultConfig->getValue('shop_id', $storeId) : $this->config->getValue('shop_id', $storeId);
        $secretKey = $payment->getAdditionalInformation('paidUsingToken') ?
            $this->vaultConfig->getValue('secret_key', $storeId) : $this->config->getValue('secret_key', $storeId);
        $formattedAmount = number_format($orderAmount, 2, ',', '');
        $STAN = $payment->getAdditionalInformation('STAN');
        $approvalCode = $payment->getAdditionalInformation('ApprovalCode');
        $WsPayOrderId = $payment->getAdditionalInformation('originalTransactionId');
        $signature = $this->generateAPISignature(
            $STAN,
            $approvalCode,
            $WsPayOrderId,
            $formattedAmount,
            $shopId,
            $secretKey
        );

        $data = [
            self::FIELD_VERSION => self::VERSION,
            self::FIELD_ORDER_ID => $WsPayOrderId,
            self::FIELD_SHOP_ID => $shopId,
            self::FIELD_APPROVAL_CODE => $approvalCode,
            self::FIELD_STAN => $STAN,
            self::FIELD_AMOUNT => str_replace(',', '', $formattedAmount),

            self::FIELD_SIGNATURE => $signature,
            '__store' => $storeId,
        ];

        return $data;
    }
}
