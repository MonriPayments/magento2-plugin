<?php

namespace Monri\Payments\Gateway\Request\WSPay;

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
        $storeId = $order->getStoreId();
        $shopId = $this->config->getValue('shop_id', $storeId);
        $formattedAmount = number_format($order->getGrandTotalAmount(), 2, ',', '');
        $STAN = $payment->getAdditionalInformation('STAN');
        $approvalCode = $payment->getAdditionalInformation('ApprovalCode');
        $WsPayOrderId = $payment->getAdditionalInformation('originalTransactionId');
        $signature = $this->generateAPISignature($STAN, $approvalCode, $WsPayOrderId, $formattedAmount, $storeId);

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
