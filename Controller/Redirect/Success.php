<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Controller\Redirect;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Controller\AbstractGatewayResponse;
use Monri\Payments\Gateway\Exception\TransactionAlreadyProcessedException;
use Monri\Payments\Model\GetOrderIdByIncrement;

/**
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class Success extends AbstractGatewayResponse
{

    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Success constructor.
     *
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param CommandManagerInterface $commandManager
     * @param GetOrderIdByIncrement $getOrderIdByIncrement
     * @param Session $checkoutSession
     * @param Logger $logger
     */
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
     * Updates the status of an order.
     *
     * @return Redirect
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

            /** @var InfoInterface $payment */
            $payment = $order->getPayment();

            $gatewayResponse = $this->getRequest()->getParams();
            $log['payload'] = $gatewayResponse;
            $gatewayResponse['status'] = 'approved';

            $digestData = $this->getDigestData();

            $result = $this->processGatewayResponse($gatewayResponse, $payment, $digestData);

            if (isset($result['response_code_message'])) {
                $this->messageManager->addNoticeMessage(
                    __('The payment has been accepted: %1', $result['response_code_message'])
                );
            } else {
                $this->messageManager->addNoticeMessage(__('The payment has been accepted.'));
            }
        } catch (TransactionAlreadyProcessedException | AlreadyExistsException $e) {
            $log['errors'][] = 'Already processed: ' . $e->getMessage();
            $log['success'] = true;
        } catch (InputException | NoSuchEntityException $e) {
            $log['errors'][] = 'Exception caught: ' . $e->getMessage();
            $log['success'] = false;
            $this->messageManager->addNoticeMessage(__('Order not found.'));

            return $resultRedirect->setPath('checkout/cart');
        } catch (Exception $e) {
            $log['errors'][] = 'Unexpected exception caught: ' . $e->getMessage();
            $log['success'] = false;
            $this->messageManager->addNoticeMessage(__('Error processing payment, please try again later.'));

            return $resultRedirect->setPath('checkout/cart');
        } finally {
            $this->logger->debug($log);
        }

        return $resultRedirect->setPath('checkout/onepage/success');
    }

    /**
     * Resolve digest data from url
     *
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
