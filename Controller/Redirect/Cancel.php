<?php

namespace Monri\Payments\Controller\Redirect;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Controller\AbstractGatewayResponse;
use Monri\Payments\Model\GetOrderIdByIncrement;

class Cancel extends AbstractGatewayResponse
{
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        CommandManagerInterface $commandManager,
        GetOrderIdByIncrement $getOrderIdByIncrement,
        Session $checkoutSession,
        Logger $logger
    ) {
        parent::__construct($context, $orderRepository, $commandManager, $getOrderIdByIncrement);

        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
    }

    /**
     * Cancels an order.
     *
     * @return Redirect|void
     */
    public function execute()
    {
        $log = [
            'location' => __METHOD__,
            'errors' => [],
            'success' => true,
        ];

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $order = $this->getOrderById(
                $this->checkoutSession->getData('last_order_id')
            );

            $gatewayResponse = $this->getRequest()->getParams();

            if ($gatewayResponse['order_number'] !== $order->getIncrementId()) {
                $log['errors'][] = 'Order number from session not matching the one in gateway response.';
                throw new NotFoundException(__('Order not found.'));
            }

            /** @var InfoInterface $payment */
            $payment = $order->getPayment();

            $gatewayResponse['status'] = 'declined';

            $result = $this->processGatewayResponse($gatewayResponse, $payment, ['disabled' => true]);

            if (isset($result['message'])) {
                $log['errors'][] = 'Error processing payment: ' . $result['message'];
                $this->messageManager->addNoticeMessage(__('The payment has been denied: %1', $result['message']));
            } else {
                $log['errors'][] = 'Error processing payment.';
                $this->messageManager->addNoticeMessage(__('The payment has been denied.'));
            }
        } catch (InputException | NoSuchEntityException | NotFoundException $e) {
            $log['errors'][] = 'Caught exception: ' . $e->getMessage();
            $log['success'] = false;
            $this->messageManager->addNoticeMessage(__('Order not found.'));
        } catch (Exception $e) {
            $log['errors'][] = 'Caught unexpected exception: ' . $e->getMessage();
            $log['success'] = false;
            $this->messageManager->addNoticeMessage(__('Error processing payment, please try again later.'));
        } finally {
            $this->checkoutSession->restoreQuote();
            $this->logger->debug($log);
        }

        return $resultRedirect->setPath('checkout/cart');
    }
}
