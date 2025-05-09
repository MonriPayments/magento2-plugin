<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Request\Redirect;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Monri\Payments\Helper\Formatter;

class OrderDetailsBuilder implements BuilderInterface
{
    public const ORDER_INFO_FIELD = 'order_info';

    public const ORDER_NUMBER_FIELD = 'order_number';

    public const AMOUNT_FIELD = 'amount';

    public const CURRENCY_FIELD = 'currency';

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * OrderDetailsBuilder constructor.
     *
     * @param Formatter $formatter
     * @param ManagerInterface $eventManager
     * @param DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        Formatter $formatter,
        ManagerInterface $eventManager,
        DataObjectFactory $dataObjectFactory
    ) {
        $this->formatter = $formatter;
        $this->eventManager = $eventManager;
        $this->dataObjectFactory = $dataObjectFactory;
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
            $payment = $paymentDataObject->getPayment();
            $orderObject = $payment->getOrder();
            $orderAmount = $this->formatter->formatPrice(
                $orderObject->getBaseGrandTotal()
            );
        }

        $currencyCode = $order->getCurrencyCode();

        return [
            self::ORDER_INFO_FIELD => $this->formatter->formatText($orderInfo, 300, true),
            self::ORDER_NUMBER_FIELD => $this->formatter->formatText($orderNumber),
            self::AMOUNT_FIELD => $orderAmount,
            self::CURRENCY_FIELD => $currencyCode,
        ];
    }
}
