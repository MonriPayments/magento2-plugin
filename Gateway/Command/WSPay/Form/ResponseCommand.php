<?php

namespace Monri\Payments\Gateway\Command\WSPay\Form;

use Monri\Payments\Gateway\Helper\TestModeHelper;
use Monri\Payments\Model\GetOrderIdByIncrement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class ResponseCommand implements CommandInterface
{
    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var HandlerInterface
     */
    protected $handler;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ArrayResultFactory
     */
    protected $arrayResultFactory;

    /**
     * @var PaymentDataObjectFactory
     */
    protected $paymentDataObjectFactory;

    /**
     * @var GetOrderIdByIncrement
     */
    private $getOrderIdByIncrement;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $commandName;

    /**
     * ResponseCommand constructor.
     *
     * @param ValidatorInterface $validator
     * @param HandlerInterface $handler
     * @param OrderRepositoryInterface $orderRepository
     * @param ArrayResultFactory $arrayResultFactory
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param GetOrderIdByIncrement $getOrderIdByIncrement
     * @param ConfigInterface $config
     * @param Logger $logger
     * @param string $commandName
     */
    public function __construct(
        ValidatorInterface $validator,
        HandlerInterface $handler,
        OrderRepositoryInterface $orderRepository,
        ArrayResultFactory $arrayResultFactory,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        GetOrderIdByIncrement $getOrderIdByIncrement,
        ConfigInterface $config,
        Logger $logger,
        string $commandName = 'payment_response'
    ) {
        $this->validator = $validator;
        $this->handler = $handler;
        $this->orderRepository = $orderRepository;
        $this->arrayResultFactory = $arrayResultFactory;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->getOrderIdByIncrement = $getOrderIdByIncrement;
        $this->config = $config;
        $this->logger = $logger;
        $this->commandName = $commandName;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject): ?ResultInterface
    {
        $response = SubjectReader::readResponse($commandSubject);

        // log here?
        $this->logger->debug([$this->commandName => $response]);

        // validate wspay response
        $result = $this->validator->validate($commandSubject);
        if (!$result->isValid()) {
            throw new CommandException(
                $result->getFailsDescription()
                    ? __(implode(', ', $result->getFailsDescription()))
                    : __('Gateway response is not valid.')
            );
        }

        // load order here (or in controller?) and set payment to $commandSubject
        $orderIncrementId = $response['ShoppingCartID'];
        $order = $this->getOrder($orderIncrementId);
        if (!$order) {
            throw new CommandException(__('Payment order not found.'));
        }

        $actionCommandSubject = [
            'response' => $response,
            'payment' => $this->paymentDataObjectFactory->create(
                $order->getPayment()
            )
        ];

        $this->handler->handle($actionCommandSubject, $response);

        return $this->arrayResultFactory->create(['array' => $actionCommandSubject]);
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
