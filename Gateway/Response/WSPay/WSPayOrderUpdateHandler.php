<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Response\WSPay;

use Exception;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManager\TMapFactory;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Gateway\Config;
use Monri\Payments\Gateway\Exception\TransactionAlreadyProcessedException;
use Monri\Payments\Lock\Order\LockInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class WSPayOrderUpdateHandler implements HandlerInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderSender
     */
    private $orderSender;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var LockInterface
     */
    private $locker;

    /**
     * @var HandlerInterface
     */
    private $unsuccessfulTransactionHandler;

    /**
     * OrderUpdateHandler constructor.
     *
     * @param OrderRepository $orderRepository
     * @param OrderSender $orderSender
     * @param Logger $logger
     * @param TMapFactory $TMapFactory
     * @param HandlerInterface $unsuccessfulTransactionHandler
     * @param Config $config
     * @param LockInterface $locker
     */
    public function __construct(
        OrderRepository $orderRepository,
        OrderSender $orderSender,
        Logger $logger,
        TMapFactory $TMapFactory,
        HandlerInterface $unsuccessfulTransactionHandler,
        Config $config,
        LockInterface $locker
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->config = $config;
        $this->logger = $logger;
        $this->locker = $locker;
        $this->unsuccessfulTransactionHandler = $unsuccessfulTransactionHandler;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws CommandException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $log = [
            'location' => __METHOD__,
            'errors' => [],
            'action' => null,
            'email_sent' => false,
        ];

        if (!isset($response['ActionSuccess'])) {
            $log['errors'][] = 'Status not set in response, invalid response.';
            $this->logger->debug($log);
            return;
        }

        $paymentDataObject = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();

        $this->ensureNotLocked($order);
        $this->locker->lock($order->getId());

        if (!isset($response['transaction_type'])) {
            $response['transaction_type'] = $this->getTransactionTypeFromPayment($payment, $order);
        }

        try {
            $this->processResponse($handlingSubject, $response, $order);

            if (!$order->getEmailSent() && $this->isSuccessfulResponse($response)) {
                $log['email_sent'] = true;
                $this->orderSender->send($order);
            }
        } finally {
            $this->locker->unlock($order->getId());
            $this->logger->debug($log);
        }
    }

    /**
     * Processes the response
     *
     * @param array $handlingSubject
     * @param array $response
     * @param OrderInterface $order
     * @return void
     * @throws AlreadyExistsException
     * @throws CommandException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws TransactionAlreadyProcessedException
     */
    protected function processResponse(
        array $handlingSubject,
        array $response,
        OrderInterface $order
    ): void {
        try {
            if ($this->isSuccessfulResponse($response)) {
                $this->processSuccessfulGatewayResponse($handlingSubject, $response);
            } else {
                $this->processUnsuccessfulGatewayResponse($handlingSubject, $response);
            }
        } catch (TransactionAlreadyProcessedException $e) {
            // pass through TransactionAlreadyProcessedException
            throw $e;
        } catch (Exception $e) {
            throw new CommandException(__('Failed to process transaction: %1', $e->getMessage()));
        }

        $this->orderRepository->save($order);
    }

    /**
     * Performs any necessary actions.
     *
     * @param array $handlingSubject
     */
    protected function processSuccessfulGatewayResponse(
        array $handlingSubject,
    ) {
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        // Allow only a single command of a payment.
        $payment->setShouldCloseParentTransaction(true);
    }

    /**
     * Processes an unsuccessful transaction.
     *
     * @param array $handlingSubject
     * @param array $response
     */
    protected function processUnsuccessfulGatewayResponse(
        array $handlingSubject,
        array $response
    ) {
        $this->unsuccessfulTransactionHandler->handle($handlingSubject, $response);
    }

    /**
     * Determines if a given response is successful.
     *
     * @param array $response
     * @return bool
     */
    protected function isSuccessfulResponse(array $response)
    {
        return isset($response['ActionSuccess']) && $response['ActionSuccess'] === '1';
    }

    /**
     * Returns the transaction type.
     *
     * @param InfoInterface $payment
     * @param Order $order
     * @return string
     */
    protected function getTransactionTypeFromPayment(InfoInterface $payment, Order $order)
    {
        $transactionType = $payment->getAdditionalInformation('transaction_type');

        if (!$transactionType) {
            $transactionType = $this->config->getTransactionType($order->getStoreId());
        }

        return $transactionType;
    }

    /**
     * Throws TransactionAlreadyProcessedException on locked order
     *
     * @param OrderInterface $order
     * @throws TransactionAlreadyProcessedException
     * @return void
     */
    protected function ensureNotLocked(OrderInterface $order): void
    {
        $orderId = $order->getId();
        if ($this->locker->isLocked($orderId)) {
            $this->logger->debug([
                'message' => __('Order is currently being processed (lock). Order ID: %1', $orderId),
                'log_origin' => __METHOD__
            ]);

            throw new TransactionAlreadyProcessedException(__('Order lock. Order ID: %1', $orderId));
        }
    }
}
