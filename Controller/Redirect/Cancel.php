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
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Controller\AbstractGatewayResponse;
use Monri\Payments\Model\GetOrderIdByIncrement;

class Cancel extends AbstractGatewayResponse
{
    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        CommandManagerInterface $commandManager,
        GetOrderIdByIncrement $getOrderIdByIncrement,
        Session $checkoutSession
    ) {
        parent::__construct($context, $orderRepository, $commandManager, $getOrderIdByIncrement);

        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Cancels an order.
     *
     * @return void
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $order = $this->getOrderById(
                $this->checkoutSession->getData('last_order_id')
            );

            $gatewayResponse = $this->getRequest()->getParams();

            if ($gatewayResponse['order_number'] !== $order->getIncrementId()) {
                throw new NotFoundException(__('Order not found.'));
            }

            /** @var InfoInterface $payment */
            $payment = $order->getPayment();

            $gatewayResponse['status'] = 'declined';

            $result = $this->processGatewayResponse($gatewayResponse, $payment, ['disabled' => true]);

            if (isset($result['message'])) {
                $this->messageManager->addNoticeMessage(__('Error processing your payment: %1', $result['message']));
            } else {
                $this->messageManager->addNoticeMessage(__('Error processing your payment.'));
            }

        } catch (InputException | NoSuchEntityException $e) {
            $this->messageManager->addNoticeMessage(__('Problem finding your order.'));
        } catch (Exception $e) {
            $this->messageManager->addNoticeMessage(__('Unexpected problem with processing your order.'));
        } finally {
            $this->checkoutSession->restoreQuote();
        }

        $resultRedirect->setPath('checkout/cart');
    }
}
