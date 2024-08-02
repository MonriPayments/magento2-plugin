<?php

namespace Monri\Payments\Gateway\Request\WSPay;

use Favicode\WSPay\Gateway\VaultConfig;
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
     * @var VaultConfig
     */
    private $vaultConfig;

    /**
     * FormDataBuilder constructor.
     *
     * @param WSPay $config
     * @param UrlInterface $urlBuilder
     * @param VaultConfig $vaultConfig
     */
    public function __construct(
        WSPay $config,
        UrlInterface $urlBuilder,
        VaultConfig $vaultConfig
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
        $storeId = $order->getStoreId();
        if ($payment->getAdditionalInformation('paidUsingToken') === true) {
            $shopId = $this->vaultConfig->getValue('shop_id', $storeId);
            $secretKey = $this->vaultConfig->getValue('secret_key', $storeId);
        } else {
            $shopId = $this->config->getValue('shop_id', $storeId);
            $secretKey = $this->config->getValue('secret_key', $storeId);
        }

        $formattedAmount = number_format($order->getGrandTotalAmount(), 2, ',', '');
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
