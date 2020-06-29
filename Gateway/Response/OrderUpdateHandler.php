<?php


namespace Monri\Payments\Gateway\Response;


use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction as TransactionResource;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Gateway\Config;

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

    public function __construct(
        OrderRepository $orderRepository,
        OrderManagementInterface $orderManagement,
        OrderSender $orderSender,
        TransactionFactory $transactionFactory,
        TransactionResource $transactionResource,
        Config $config
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->orderManagement = $orderManagement;
        $this->config = $config;
        $this->transactionFactory = $transactionFactory;
        $this->transactionResource = $transactionResource;
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
        if (!isset($response['status'])) {
            return;
        }

        $paymentDataObject = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        /** @var Order $order */
        $order = $payment->getOrder();

        $status = $response['status'];

        if ($status === 'approved') {
            $payment->setTransactionId($this->getTransactionId($response));
            $payment->setTransactionAdditionalInfo(
                Transaction::RAW_DETAILS,
                $response
            );

            if (!$order->canInvoice() || $this->checkIfTransactionProcessed($payment)) {
                // Already processed this transaction.
                return;
            }

            // Consider storing transaction type with payment.
            if ($this->config->getTransactionType($order->getStoreId()) === Config::TRANSACTION_TYPE_AUTHORIZE) {
                $this->setAuthorizedPayment($payment);
            } else {
                $this->setCapturedPayment($payment);
            }
        } else {
            if (isset($response['response_code'])) {
                try {
                    $payment->setAdditionalInformation(
                        'gateway_response_code',
                        $response['response_code']
                    );
                } catch (LocalizedException $e) {
                    // TODO: Log
                }
            }

            $this->orderManagement->cancel($order->getEntityId());
        }


        $this->orderRepository->save($order);

        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
        }
    }

    /**
     * Registers an authorized payment.
     *
     * @param Payment $payment
     */
    protected function setAuthorizedPayment(Payment $payment)
    {
        $payment->setIsTransactionClosed(false);
        $payment->registerAuthorizationNotification($payment->getOrder()->getBaseGrandTotal());
    }

    /**
     * Registers a paid payment.
     *
     * @param Payment $payment
     */
    protected function setCapturedPayment(Payment $payment)
    {
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
