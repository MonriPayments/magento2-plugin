<?php


namespace Monri\Payments\Gateway\Request\Redirect;


use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Monri\Payments\Helper\Formatter;

class OrderDetailsBuilder implements BuilderInterface
{
    const ORDER_INFO_FIELD = 'order_info';

    const ORDER_NUMBER_FIELD = 'order_number';

    const AMOUNT_FIELD = 'amount';

    const CURRENCY_FIELD = 'currency';

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
     * Builds the order details object
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $order = $paymentDataObject->getOrder();

        $orderNumber = $order->getOrderIncrementId();

        $orderInfo = __('Order: #%1', $orderNumber);

        $orderAmount = $this->formatter->formatPrice(
            $order->getGrandTotalAmount()
        );

        $currencyCode = $order->getCurrencyCode();

        return [
            self::ORDER_INFO_FIELD => $this->formatter->formatText($orderInfo, 300, false),
            self::ORDER_NUMBER_FIELD => $this->formatter->formatText($orderNumber),
            self::AMOUNT_FIELD => $orderAmount,
            self::CURRENCY_FIELD => $currencyCode,
        ];
    }
}
