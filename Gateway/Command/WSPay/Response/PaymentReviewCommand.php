<?php

namespace Monri\Payments\Gateway\Command\WSPay\Response;

use Magento\Framework\Exception\LocalizedException;
use Monri\Payments\Gateway\Helper\TestModeHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Monri\Payments\Model\GetOrderIdByIncrement;

class PaymentReviewCommand implements CommandInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var GetOrderIdByIncrement
     */
    private GetOrderIdByIncrement $getOrderIdByIncrement;

    /**
     * SuccessCommand constructor.
     *
     * @param ConfigInterface $config
     * @param GetOrderIdByIncrement $getOrderIdByIncrement
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Session $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        ConfigInterface $config,
        GetOrderIdByIncrement $getOrderIdByIncrement,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Session $checkoutSession,
        Logger $logger
    ) {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
        $this->getOrderIdByIncrement = $getOrderIdByIncrement;
        $this->orderSender = $orderSender;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Check if response is valid. If there are no problems we send an email and update the order
     *
     * @param array $commandSubject
     * @return void
     * @throws CommandException
     */
    public function execute(array $commandSubject)
    {
        $response = SubjectReader::readResponse($commandSubject);

        $this->logger->debug(['payment_payment_review_response' => $response]);

        // load order
        $orderIncrementId = $response['ShoppingCartID'];

        $order = $this->getOrder($orderIncrementId);
        if (!$order) {
            throw new CommandException(__('Payment order not found.'));
        }

        if ($order->getEntityId() != $this->checkoutSession->getLastOrderId()) {
            throw new CommandException(__('Order id is not valid.'));
        }

        if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
            return;
        }

        $order->setStatus(Order::STATE_PAYMENT_REVIEW);

        // send new order email
        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
        }

        // update order
        $this->orderRepository->save($order);
    }

    /**
     * Resolve and load order by increment_id
     *
     * @param string $orderIncrementId
     * @return OrderInterface|null
     */
    protected function getOrder(string $orderIncrementId): ?OrderInterface
    {
        if ($this->config->getValue('test_mode')) {
            $orderIncrementId = TestModeHelper::resolveRealOrderId($orderIncrementId);
        }

        $orderId = $this->getOrderIdByIncrement->execute($orderIncrementId);

        if (!$orderId) {
            return null;
        }

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (LocalizedException $e) {
            return null;
        }

        return $order;
    }
}
