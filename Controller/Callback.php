<?php

/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

namespace Monri\Payments\Controller;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Model\GetOrderIdByIncrement;

/**
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
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
            'location' => __METHOD__,
            'errors' => [],
            'success' => true,
            'payload' => []
        ];

        /** @var Redirect $resultRedirect */
        $resultRaw = $this->resultFactory->create(ResultFactory::TYPE_RAW);

        try {
            $gatewayPayload = $this->getRequest()->getContent();
            if (empty($gatewayPayload)) {
                throw new InputException(__('Gateway payload empty.'));
            }

            $gatewayResponse = $this->jsonSerializer->unserialize($gatewayPayload);

            $log['payload'] = $gatewayResponse;

            $orderNumber = $gatewayResponse['order_number'];

            $order = $this->getOrderByIncrementId($orderNumber);

            /** @var InfoInterface $payment */
            $payment = $order->getPayment();

            $digest = $this->getRequestDigest();

            $this->processGatewayResponse($gatewayResponse, $payment, [
                'digest' => $digest,
                'digest_data' => $gatewayPayload
            ]);
        } catch (InputException $e) {
            $log['errors'][] = 'Exception caught: ' . $e->getMessage();
            $log['success'] = false;
            return $resultRaw->setHttpResponseCode(400);
        } catch (Exception $e) {
            $log['errors'][] = 'Unexpected exception caught: ' . $e->getMessage();
            $log['success'] = false;
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
