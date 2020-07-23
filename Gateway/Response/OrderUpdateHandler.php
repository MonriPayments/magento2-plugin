<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Response;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Gateway\Config;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderUpdateHandler implements HandlerInterface
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
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var TransactionResource
     */
    private $transactionResource;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        OrderRepository $orderRepository,
        OrderManagementInterface $orderManagement,
        OrderSender $orderSender,
        TransactionFactory $transactionFactory,
        TransactionResource $transactionResource,
        Logger $logger,
        Config $config
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->orderManagement = $orderManagement;
        $this->config = $config;
        $this->transactionFactory = $transactionFactory;
        $this->transactionResource = $transactionResource;
        $this->logger = $logger;
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
     */
    public function handle(array $handlingSubject, array $response)
    {
        $log = [
            'location' => __METHOD__,
            'errors' => [],
            'action' => null,
            'email_sent' => false,
        ];

        if (!isset($response['status'])) {
            $log['errors'][] = 'Status not set in response, invalid response.';
            $this->logger->debug($log);
            return;
        }

        $paymentDataObject = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();

        $status = $response['status'];
        $transactionType = isset($response['transaction_type']) ? $response['transaction_type'] : null;

        $log['action'] = $this->config->getTransactionType($order->getStoreId()) === Config::TRANSACTION_TYPE_AUTHORIZE
            ? 'authorize'
            : 'capture';

        if ($status === 'approved' && !in_array($transactionType, ['refund', 'void'])) {
            $log['status'] = 'approved';

            try {
                $this->processSuccessfulPayment($payment, $order, $response);
            } catch (AlreadyExistsException $e) {
                $log['errors'][] = 'Transaction already processed or order cannot be invoiced: %e' . $e->getMessage();
                $this->logger->debug($log);
                return;
            }
        } else {
            $log['status'] = 'denied';

            try {
                $this->processUnsuccessfulPayment($payment, $order, $response);
            } catch (LocalizedException $e) {
                $log['errors'][] = 'Issue when processing unsuccessful payment: ' . $e->getMessage();
            }
        }

        $this->orderRepository->save($order);

        if (!$order->getEmailSent()) {
            $log['email_sent'] = true;
            $this->orderSender->send($order);
        }

        $this->logger->debug($log);
    }

    /**
     * Processes a successful payment.
     *
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     * @param array $response
     * @throws AlreadyExistsException
     */
    protected function processSuccessfulPayment(
        OrderPaymentInterface $payment,
        OrderInterface $order,
        array $response
    ) {
        /** @var Payment $payment */
        $payment->setTransactionId($this->getTransactionId($response));
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $response
        );

        if (!$order->canInvoice() || $this->checkIfTransactionProcessed($payment)) {
            // Already processed this transaction.
            throw new AlreadyExistsException(__('Transaction already processed.'));
        }

        if ($this->config->getTransactionType($order->getStoreId()) === Config::TRANSACTION_TYPE_AUTHORIZE) {
            $this->setAuthorizedPayment($payment);
        } else {
            $this->setCapturedPayment($payment);
        }
    }

    /**
     * Processes an unsuccessful payment.
     *
     * @param OrderPaymentInterface $payment
     * @param OrderInterface $order
     * @param array $response
     * @throws LocalizedException
     */
    protected function processUnsuccessfulPayment(
        OrderPaymentInterface $payment,
        OrderInterface $order,
        array $response
    ) {
        /** @var Payment $payment */
        try {
            if (isset($response['response_code'])) {
                $payment->setAdditionalInformation(
                    'gateway_response_code',
                    $response['response_code']
                );
            }
        } catch (LocalizedException $e) {
            throw new LocalizedException(__('Could not set response code on payment: %1', $e->getMessage()));
        } finally {
            $this->orderManagement->cancel($order->getEntityId());
        }
    }

    /**
     * Registers an authorized payment.
     *
     * @param OrderPaymentInterface $payment
     */
    protected function setAuthorizedPayment(OrderPaymentInterface $payment)
    {
        /** @var Payment $payment */
        $payment->setIsTransactionClosed(false);
        $payment->registerAuthorizationNotification($payment->getOrder()->getBaseGrandTotal());
    }

    /**
     * Registers a paid payment.
     *
     * @param OrderPaymentInterface $payment
     */
    protected function setCapturedPayment(OrderPaymentInterface $payment)
    {
        /** @var Payment $payment */
        $payment->getOrder()->setState(Order::STATE_PROCESSING);
        $payment->registerCaptureNotification($payment->getOrder()->getBaseGrandTotal());
    }

    /**
     * Crafts the ID for the transaction.
     *
     * @param array $response
     * @return string
     */
    protected function getTransactionId(array $response)
    {
        $orderNumber = $response['order_number'];
        $approvalCode = $response['approval_code'];

        return "{$orderNumber}-{$approvalCode}";
    }

    protected function checkIfTransactionProcessed(Payment $payment)
    {
        $transactionTxnId = $payment->getTransactionId();
        $paymentId = $payment->getId();
        $orderId = $payment->getOrder()->getId();

        /** @var Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $this->transactionResource->loadObjectByTxnId(
            $transaction,
            $orderId,
            $paymentId,
            $transactionTxnId
        );

        return (bool) $transaction->getId();
    }
}
