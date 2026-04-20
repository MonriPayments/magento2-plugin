<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Request\GooglePay;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Monri\Payments\Helper\Formatter;
use Magento\Framework\UrlInterface;

class OrderDetailsBuilder implements BuilderInterface
{
    public const ORDER_INFO_FIELD = 'order_info';

    public const ORDER_NUMBER_FIELD = 'order_number';

    public const AMOUNT_FIELD = 'amount';

    public const CURRENCY_FIELD = 'currency';

    public const TRANSACTION_TYPE_FIELD = 'transaction_type';

    public const IP_ADDRESS_FIELD = 'ip';

    /**
     * OrderDetailsBuilder constructor.
     *
     * @param Formatter $formatter
     * @param ManagerInterface $eventManager
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        private Formatter $formatter,
        private ManagerInterface $eventManager,
        private DataObjectFactory $dataObjectFactory,
    ) {
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
        $payment = $paymentDataObject->getPayment();

        /** @var \Magento\Payment\Gateway\Data\Quote\QuoteAdapter $order */
        $order = $paymentDataObject->getOrder();

        $orderNumber = $payment->getAdditionalInformation('monri_order_number');

        $orderIpAddress = $paymentDataObject->getPayment()->getOrder()->getRemoteIp();

        $orderInfo = __('Order: %1', $order->getOrderIncrementId())->render();

        //Google Pay only supports purchase transactions
        $transactionType = 'purchase';

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

        $orderInfo = $this->formatter->formatText($transportObject->getData('description'), 100);

        /*
            Added in 2.4.8, because \PayPal\Braintree\Gateway\Data\Order\OrderAdapter puts themselves as preference for
            \Magento\Payment\Gateway\Data\Order\OrderAdapter. It declares strict types, but getGrandTotalAmount returns
            string instead of float, causing it to break execution.
        */
        try {
            $orderAmount = $this->formatter->formatPrice(
                $order->getGrandTotalAmount()
            );
        } catch (\TypeError $e) {
            $orderObject = $payment->getOrder();
            $orderAmount = $this->formatter->formatPrice(
                $orderObject->getBaseGrandTotal()
            );
        }

        $currencyCode = $order->getCurrencyCode();

        return [
            self::ORDER_INFO_FIELD => $orderInfo,
            self::ORDER_NUMBER_FIELD => $orderNumber,
            self::AMOUNT_FIELD => $orderAmount,
            self::CURRENCY_FIELD => $currencyCode,
            self::IP_ADDRESS_FIELD => $orderIpAddress,
            self::TRANSACTION_TYPE_FIELD => $transactionType,
        ];
    }
}
