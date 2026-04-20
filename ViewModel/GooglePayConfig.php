<?php

namespace Monri\Payments\ViewModel;

use Magento\Framework\Exception\InputException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Monri\Payments\Gateway\Config\GooglePay as Config;
use Monri\Payments\Gateway\Response\Components\PaymentCreateHandler;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Exception;
use Magento\Payment\Model\Method\Logger;

class GooglePayConfig implements ArgumentInterface
{
    /**
     * @param Session $checkoutSession
     * @param OrderRepository $orderRepository
     * @param GatewayCommand $googlePayCommand
     * @param Config $config
     * @param PaymentDataObjectFactory $paymentDataObjectFactory *
     * @param Logger $logger
     */
    public function __construct(
        private Session $checkoutSession,
        private OrderRepository $orderRepository,
        private GatewayCommand $googlePayCommand,
        private Config $config,
        private PaymentDataObjectFactory $paymentDataObjectFactory,
        private Logger $logger
    ) {
    }

    /**
     * Get Google Pay configuration for frontend component.
     *
     * @return array
     */
    public function getConfig()
    {
        try {
            $orderId = $this->checkoutSession->getData('last_order_id');
            if (!$orderId) {
                throw new InputException(__('Missing fields.'));
            }

            $order = $this->orderRepository->get($orderId);
            /** @var InfoInterface $payment */
            $payment = $order->getPayment();

            // direct execution, no pool needed
            $this->googlePayCommand->execute(['payment' => $this->paymentDataObjectFactory->create($payment)]);

            $payload = $payment->getAdditionalInformation(
                PaymentCreateHandler::INITIAL_DATA
            );

            $storeId = $order->getStoreId();

            return [
                'payload' => $payload,
                'gatewayUrl' => $this->config->getGatewayPaymentCreateURL($storeId),
                'componentsJsUrl' => $this->config->getComponentsJsURL($storeId),
                'authenticityToken' => $this->config->getClientAuthenticityToken($storeId),
                'isTest' => $this->config->getIsSandboxMode($storeId),
            ];
        } catch (Exception $e) {
            $this->logger->debug(['GooglePay initialization failed: ' . $e->getMessage()]);

            return [
                'error' => true,
                'message' => __('Google Pay is currently unavailable.')
            ];
        }
    }
}
