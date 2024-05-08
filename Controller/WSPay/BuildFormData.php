<?php

namespace Monri\Payments\Controller\WSPay;

use Monri\Payments\Gateway\Command\WSPay\BuildFormDataCommand;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Controller\Result\Json;

class BuildFormData extends Action
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var BuildFormDataCommand
     */
    private $command;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * BuildFormData constructor.
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param BuildFormDataCommand $command
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        BuildFormDataCommand $command,
        PaymentDataObjectFactory $paymentDataObjectFactory
    ) {
        $this->session = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->command = $command;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;

        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): Json
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        //$orderId = $this->session->getData('last_order_id');
        $orderId = $this->session->getLastOrderId();
        //$orderId = 4;

        try {
            $order = $this->orderRepository->get((int)$orderId);
        } catch (NotFoundException $e) {
            $resultJson
                ->setHttpResponseCode(400)
                ->setData(['message' => __('No such order id.')]);

            return $resultJson;
        }

        $result = $this->command->execute(
            [
                'payment' => $this->paymentDataObjectFactory->create($order->getPayment())
            ]
        )->get();

        $resultJson->setData($result);
        return $resultJson;
    }
}
