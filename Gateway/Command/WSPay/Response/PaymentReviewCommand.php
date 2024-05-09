<?php

namespace Monri\Payments\Gateway\Command\WSPay\Response;

use Monri\Payments\Gateway\Helper\CcTypeMapper;
use Monri\Payments\Gateway\Helper\TestModeHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

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
     * SuccessCommand constructor.
     *
     * @param ConfigInterface $config
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Session $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        ConfigInterface $config,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Session $checkoutSession,
        Logger $logger
    ) {
        $this->config = $config;
        $this->orderRepository = $orderRepository;
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

        $ccType = $response['PaymentType'] ?? ($response['CreditCardName'] ?? '');
        if ($ccType) {
            $order->getPayment()->setCcType(
                CcTypeMapper::getCcTypeId($ccType, $response['Partner'] ?? null)
            );
        }

        // send new order email
        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
        }

        // update order
        $this->orderRepository->save($order);
    }

    /**
     * Get order by increment id
     *
     * @param string $orderIncrementId
     * @return bool|OrderInterface
     */
    private function getOrder(string $orderIncrementId): bool|OrderInterface
    {
        if ($this->config->getValue('test_mode')) {
            $orderIncrementId = TestModeHelper::resolveRealOrderId($orderIncrementId);
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('increment_id', $orderIncrementId)
            ->create();

        $result = $this->orderRepository->getList($searchCriteria);

        if ($result->getTotalCount() < 1) {
            return false;
        }

        $orders = $result->getItems();
        return array_shift($orders);
    }
}
