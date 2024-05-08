<?php

namespace Monri\Payments\Gateway\Response\WSPay\Form;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderManagementInterface;

class CancelHandler implements HandlerInterface
{
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * CancelHandler constructor.
     *
     * @param OrderManagementInterface $orderManagement
     */
    public function __construct(
        OrderManagementInterface $orderManagement
    ) {
        $this->orderManagement = $orderManagement;
    }

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);
        $order = $paymentDO->getOrder();

        $this->orderManagement->cancel($order->getId());
    }
}
