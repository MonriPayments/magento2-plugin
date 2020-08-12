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
    const ORDER_INFO_FIELD = 'order_info';

    const ORDER_NUMBER_FIELD = 'order_number';

    const AMOUNT_FIELD = 'amount';

    const CURRENCY_FIELD = 'currency';

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

        $orderInfo = __('Order: #%1', $orderNumber);

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

        $orderAmount = $this->formatter->formatPrice(
            $order->getGrandTotalAmount()
        );

        $currencyCode = $order->getCurrencyCode();

        return [
            self::ORDER_INFO_FIELD => $this->formatter->formatText($orderInfo, 300),
            self::ORDER_NUMBER_FIELD => $this->formatter->formatText($orderNumber),
            self::AMOUNT_FIELD => $orderAmount,
            self::CURRENCY_FIELD => $currencyCode,
        ];
    }
}
