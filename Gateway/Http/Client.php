<?php

namespace Monri\Payments\Gateway\Http;

use Exception;
use Magento\Framework\HTTP\ClientInterface;
use Magento\Framework\HTTP\ClientInterfaceFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Gateway\Http\ClientInterface as PaymentClientInterface;
use Magento\Payment\Model\Method\Logger;

class Client implements PaymentClientInterface
{
    /**
     * @var ClientInterfaceFactory
     */
    private $httpClientFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $requestType;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        ClientInterfaceFactory $httpClientFactory,
        SerializerInterface $serializer,
        Logger $logger,
        $requestType = 'application/xml'
    ) {
        $this->httpClientFactory = $httpClientFactory;
        $this->serializer = $serializer;
        $this->requestType = $requestType;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'location' => __METHOD__,
            'request_data' => [],
            'response_data' => [],
            'errors' => [],
            'success' => true,
        ];

        $requestUri = $transferObject->getUri();
        $requestMethod = strtoupper($transferObject->getMethod());
        $requestPayload = $this->prepareRequestPayload($transferObject->getBody());

        $log['request_data'] = $requestPayload;

        /** @var ClientInterface $client */
        $client = $this->httpClientFactory->create();

        $client->setHeaders([
            'Content-Type' => $this->requestType
        ]);

        if ($requestMethod === 'POST') {
            $client->post($requestUri, $requestPayload);
        } else {
            $client->get($requestUri);
        }

        $responseStatus = $client->getStatus();

        $response = $this->parseResponseBody($client->getBody());

        $log['response_data'] = $response;

        try {
            $this->assertServerResponse($response, $responseStatus);
        } catch (ClientException $e) {
            $log['errors'][] = 'Exception caught: ' . $e->getMessage();
            $log['success'] = false;
            $this->logger->debug($log);
            throw $e;
        }

        $this->logger->debug($log);
        return $response;
    }

    /**
     * Prepares request payload
     *
     * @param array $payload
     * @return bool|string
     */
    protected function prepareRequestPayload(array $payload)
    {
        try {
            $serialized = $this->serializer->serialize($payload);
            if ($serialized === false) {
                return '';
            }

            return $serialized;
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Parses response and returns array
     *
     * @param $response
     * @return array
     */
    protected function parseResponseBody($response)
    {
        try {
            $data = $this->serializer->unserialize($response);
            if ($data === null) {
                return [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * @param array $responseBody
     * @param int $statusCode
     * @throws ClientException
     */
    protected function assertServerResponse(array $responseBody, $statusCode)
    {
        if ($statusCode >= 400 && $statusCode <= 499) {
            if (isset($responseBody['error'])) {
                $errors = $responseBody['error'];
                if (!is_array($errors)) {
                    $errors = [$errors];
                }

                throw new ClientException(__('Client error (%1): %2', $statusCode, implode(', ', $errors)));
            } else {
                throw new ClientException(__('Client error (%1)', $statusCode));
            }
        } else if ($statusCode >= 500 && $statusCode <= 599) {
            throw new ClientException(__('Server error (%1)', $statusCode));
        }
    }
}
