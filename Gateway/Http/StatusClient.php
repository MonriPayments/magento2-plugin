<?php

namespace Monri\Payments\Gateway\Http;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\HTTP\ClientInterfaceFactory;
use Magento\Payment\Model\Method\Logger;

class StatusClient implements ClientInterface
{

    /**
     * Client constructor.
     *
     * @param ClientInterfaceFactory $httpClientFactory
     * @param SerializerInterface $serializer
     * @param Logger $logger
     * @param int $timeout
     * @param string $requestType
     */
    public function __construct(
        private ClientInterfaceFactory $httpClientFactory,
        private SerializerInterface $serializer,
        private Logger $logger,
        private $timeout = 10,
        private $requestType = 'application/json'
    ) {}
    /**
     * @inheritDoc
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

        /** @var \Magento\Framework\HTTP\ClientInterface $client */
        $client = $this->httpClientFactory->create();

        $client->setTimeout($this->timeout);

        $client->setHeaders(array_merge(
            $transferObject->getHeaders(),
            [
                'Content-Type' => $this->requestType
            ]
        ));

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
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Order status fetch failed.')
            );
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
     * @param string $response
     * @return array
     */
    protected function parseResponseBody($response)
    {
        try {
            //the response is xml, so we need to convert it to json and then to array
            $xml = simplexml_load_string($response);
            $json = json_encode($xml);
            $data = json_decode($json,TRUE);
            if ($data === null) {
                return [];
            }

            return $data;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Validate server response
     *
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
        } elseif ($statusCode >= 500 && $statusCode <= 599) {
            throw new ClientException(__('Server error (%1)', $statusCode));
        }
    }
}

