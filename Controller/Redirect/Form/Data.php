<?php


namespace Monri\Payments\Controller\Redirect\Form;


use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Gateway\Config;

class Data extends Action
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
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderRepository $orderRepository,
        Config $config,
        CommandManagerInterface $commandManager,
        Logger $logger
    ) {
        $this->commandManager = $commandManager;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Generate form data that will be used for the redirect to the gateway
     *
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $log = [
            'location' => __METHOD__
        ];

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $orderId = $this->checkoutSession->getData('last_order_id');

            if (!$orderId) {
                throw new InputException(__('Missing fields.'));
            }

            $order = $this->orderRepository->get($orderId);

            /** @var InfoInterface $payment */
            $payment = $order->getPayment();

            $result = $this->commandManager->executeByCode('create_request', $payment);

            $resultJson->setData([
                'payload' => $result->get(),
                'url' => $this->config->getFormRedirectURL($order->getStoreId()),
                'error' => null
            ]);
        } catch (InputException | NoSuchEntityException | CommandException $e) {
            $resultJson->setData([
                'payload' => [],
                'url' => '',
                'error' => __('There has been an issue with processing the data for your payment.')
            ]);

            $resultJson->setHttpResponseCode(400);
            return $resultJson;
        } catch (Exception $e) {
            $resultJson->setData([
                'payload' => [],
                'url' => '',
                'error' => __('Unexpected error processing your payment.')
            ]);

            $resultJson->setHttpResponseCode(500);
            return $resultJson;
        } finally {
            $this->logger->debug($log);
        }

        return $resultJson;
    }
}
