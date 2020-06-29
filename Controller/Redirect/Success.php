<?php


namespace Monri\Payments\Controller\Redirect;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Controller\AbstractGatewayResponse;
use Monri\Payments\Model\GetOrderIdByIncrement;

class Success extends AbstractGatewayResponse
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
     * Updates the status of an order.
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

            /** @var InfoInterface $payment */
            $payment = $order->getPayment();

            $gatewayResponse = $this->getRequest()->getParams();
            $gatewayResponse['status'] = 'approved';

            $digestData = $this->getDigestData();

            $result = $this->processGatewayResponse($gatewayResponse, $payment, $digestData);

            if (isset($result['message'])) {
                $this->messageManager->addNoticeMessage(__('Payment processed: %1', $result['message']));
            } else {
                $this->messageManager->addNoticeMessage(__('Payment processed.'));
            }

        } catch (InputException | NoSuchEntityException $e) {
            $this->messageManager->addNoticeMessage(__('Problem finding your order.'));
        } catch (Exception $e) {
            $this->messageManager->addNoticeMessage(__('Unexpected problem with processing your order.'));
        }

        $resultRedirect->setPath('checkout/cart');
    }

    /**
     * @return array
     */
    protected function getDigestData()
    {
        $digest = $this->getRequest()->getParam('digest');
        $url = $this->_url->getCurrentUrl();

        $data = str_replace('&digest=' . $digest, '', $url);

        return [
            'digest' => $digest,
            'digest_data' => $data,
        ];
    }
}