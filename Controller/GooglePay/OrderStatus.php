<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Controller\GooglePay;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Model\GetOrderIdByIncrement;

/**
 * AJAX endpoint for polling Google Pay order payment status.
 *
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class OrderStatus extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var CommandManagerInterface
     */
    private $commandManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var GetOrderIdByIncrement
     */
    private $getOrderIdByIncrement;

    /**
     * OrderStatus constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderRepository $orderRepository
     * @param CommandManagerInterface $commandManager
     * @param GetOrderIdByIncrement $getOrderIdByIncrement
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderRepository $orderRepository,
        CommandManagerInterface $commandManager,
        GetOrderIdByIncrement $getOrderIdByIncrement,
        Logger $logger
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->commandManager = $commandManager;
        $this->getOrderIdByIncrement = $getOrderIdByIncrement;
        $this->logger = $logger;
    }

    /**
     * Poll order payment status and return JSON.
     *
     * Returns:
     *   {"status": "approved"}  – payment was approved
     *   {"status": "pending"}   – payment not yet finalised
     *   {"status": "error", "message": "..."}  – unrecoverable error
     *
     * @return Json
     */
    public function execute()
    {
        $log = [
            'location' => __METHOD__,
            'errors' => [],
        ];

        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $orderId = $this->checkoutSession->getData('last_order_id');

            if (!$orderId) {
                $result->setData(['status' => 'error', 'message' => 'Order not found.']);
                return $result;
            }

            $order = $this->orderRepository->get($orderId);
            $payment = $order->getPayment();

            // Run check_status which fetches the latest status from Monri API
            // and stores it in payment additional information.
            $this->commandManager->executeByCode('check_status', $payment);

            $gatewayStatus = $payment->getAdditionalInformation('gateway_status');
            $log['gateway_status'] = $gatewayStatus;

            if ($gatewayStatus === 'approved') {
                $result->setData(['status' => 'approved']);
            } else {
                $result->setData(['status' => 'pending']);
            }
        } catch (InputException | NoSuchEntityException $e) {
            $log['errors'][] = 'Order not found: ' . $e->getMessage();
            $result->setData(['status' => 'error', 'message' => 'Order not found.']);
        } catch (Exception $e) {
            $log['errors'][] = 'Exception: ' . $e->getMessage();
            $result->setData(['status' => 'error', 'message' => 'Error checking order status.']);
        } finally {
            $this->logger->debug($log);
        }

        return $result;
    }
}
