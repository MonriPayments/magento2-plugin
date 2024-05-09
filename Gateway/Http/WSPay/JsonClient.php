<?php

namespace Monri\Payments\Gateway\Http\WSPay;

use HttpException;
use Magento\Framework\HTTP\ClientInterfaceFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Psr\Log\LoggerInterface;

class JsonClient implements ClientInterface
{
    /**
     * @var ClientInterfaceFactory
     */
    private $httpClientFactory;

    /**
     * @var PaymentLogger
     */
    private $paymentLogger;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Json
     */
    private $json;

    /**
     * Client constructor.
     *
     * @param ClientInterfaceFactory $httpClientFactory
     * @param PaymentLogger $paymentLogger
     * @param LoggerInterface $logger
     * @param Json $json
     */
    public function __construct(
        ClientInterfaceFactory $httpClientFactory,
        PaymentLogger $paymentLogger,
        LoggerInterface $logger,
        Json $json
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->paymentLogger = $paymentLogger;
        $this->logger = $logger;
        $this->json = $json;
    }

    /**
     * Place JSON request
     *
     * @param TransferInterface $transferObject
     * @return array|int|bool|string|null
     * @throws ClientException
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();
        $log = [
            'api_request' => [
                'uri' => $transferObject->getUri(),
                'request' => $request
            ],
        ];

        /** @var \Magento\Framework\HTTP\ClientInterface $client */
        $client = $this->httpClientFactory->create();
        $client->setTimeout(10);
        $client->setOptions($transferObject->getClientConfig());
        $client->setHeaders(array_merge(
            [
                'Content-Type' => 'application/json'
            ],
            $transferObject->getHeaders()
        ));

        try {
            if (strtoupper($transferObject->getMethod()) === 'GET') {
                $client->get($transferObject->getUri());
            } else {
                $client->post($transferObject->getUri(), $this->json->serialize($request));
            }

            if ($client->getStatus() < 200 || $client->getStatus() >= 300) {
                throw new HttpException('Invalid response status.');
            }

            $response = $client->getBody();
            $response = $this->json->unserialize($response);
            $log['api_response'] = $response;
            return $response;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $log['error'] = $e->getMessage();

            throw new ClientException(
                __('Something went wrong in the payment gateway.')
            );
        } finally {
            $log['log_origin'] = __METHOD__;
            $this->paymentLogger->debug($log);
        }
    }
}
