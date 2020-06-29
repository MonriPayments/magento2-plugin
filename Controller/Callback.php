<?php


namespace Monri\Payments\Controller;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Model\GetOrderIdByIncrement;

class Callback extends AbstractGatewayResponse
{
    const CALLBACK_DIGEST_PREFIX = 'WP3-callback ';

    /**
     * @var Json
     */
    private $jsonSerializer;
    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        CommandManagerInterface $commandManager,
        GetOrderIdByIncrement $getOrderIdByIncrement,
        Json $jsonSerializer,
        Logger $logger
    ) {
        parent::__construct($context, $orderRepository, $commandManager, $getOrderIdByIncrement);

        $this->jsonSerializer = $jsonSerializer;
        $this->logger = $logger;
    }

    /**
     * Callback action triggered by the gateway
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $log = [
            'location' => __METHOD__
        ];

        /** @var Redirect $resultRedirect */
        $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);

        try {
            $gatewayPayload = $this->getRequest()->getContent();
            if (empty($gatewayPayload)) {
                return $resultRaw->setHttpResponseCode(400);
            }

            $gatewayResponse = $this->jsonSerializer->unserialize($gatewayPayload);

            $orderNumber = $gatewayResponse['order_number'];

            $order = $this->getOrderByIncrementId($orderNumber);

            /** @var InfoInterface $payment */
            $payment = $order->getPayment();

            $digest = $this->getRequestDigest();

            $this->processGatewayResponse($gatewayResponse, $payment, [
                'digest' => $digest,
                'digest_data' => $gatewayPayload
            ]);
        } catch (Exception $e) {
            return $resultRaw->setHttpResponseCode(500);
        } finally {
            $this->logger->debug($log);
        }

        return $resultRaw->setHttpResponseCode(200);
    }

    protected function getRequestDigest()
    {
        $digestHeader = $this->getRequest()->getHeader('Authorization');
        if (!$digestHeader) {
            $digestHeader = $this->getRequest()->getHeader('Http_authorization');
        }

        $digestHeader = str_replace(self::CALLBACK_DIGEST_PREFIX, '', $digestHeader);

        return $digestHeader;
    }
}
