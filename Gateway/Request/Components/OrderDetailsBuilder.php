<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Request\Components;

use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Monri\Payments\Helper\Formatter;
use Monri\Payments\Gateway\Config\Components as ComponentsConfig;

class OrderDetailsBuilder implements BuilderInterface
{
    public const ORDER_INFO_FIELD = 'order_info';

    public const ORDER_NUMBER_FIELD = 'order_number';

    public const AMOUNT_FIELD = 'amount';

    public const CURRENCY_FIELD = 'currency';

    public const TRANSACTION_TYPE_FIELD = 'transaction_type';

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
     * @var ComponentsConfig
     */
    private $config;

    /**
     * OrderDetailsBuilder constructor.
     *
     * @param Formatter $formatter
     * @param ManagerInterface $eventManager
     * @param DataObjectFactory $dataObjectFactory
     * @param ComponentsConfig $config
     */
    public function __construct(
        Formatter $formatter,
        ManagerInterface $eventManager,
        DataObjectFactory $dataObjectFactory,
        ComponentsConfig $config
    ) {
        $this->formatter = $formatter;
        $this->eventManager = $eventManager;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->config = $config;
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

        /** @var \Magento\Payment\Gateway\Data\Quote\QuoteAdapter $order */
        $order = $paymentDataObject->getOrder();

        $orderNumber = $this->formatter->formatText($order->getOrderIncrementId() . uniqid('-'), 40);

        $paymentDataObject->getPayment()->setAdditionalInformation(self::ORDER_NUMBER_FIELD, $orderNumber);

        $orderInfo = __('Order: %1', $order->getOrderIncrementId());

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

        $orderAmount = $this->formatter->formatPrice(
            $paymentDataObject->getPayment()->getQuote()->getBaseGrandTotal() //can we do this better?
        );

        $currencyCode = $order->getCurrencyCode();

        $paymentAction = $this->config->getPaymentAction(
            $paymentDataObject->getPayment()->getQuote()->getStoreId()
        );

        return [
            self::ORDER_INFO_FIELD => $orderInfo,
            self::ORDER_NUMBER_FIELD => $orderNumber,
            self::AMOUNT_FIELD => $orderAmount,
            self::CURRENCY_FIELD => $currencyCode,
            self::TRANSACTION_TYPE_FIELD => $paymentAction,
        ];
    }
}
