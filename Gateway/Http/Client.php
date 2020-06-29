<?php

namespace Monri\Payments\Gateway\Http;

use InvalidArgumentException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Framework\Serialize\Serializer\Json;

class Client implements ClientInterface
{
    /**
     * @var CurlFactory
     */
    private $curlClientFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    public function __construct(
        CurlFactory $curlClientFactory,
        Json $jsonSerializer
    ) {
        $this->curlClientFactory = $curlClientFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $uri = $transferObject->getUri();
        $method = strtoupper($transferObject->getMethod());
        $payload = $transferObject->getBody();

        /** @var Curl $client */
        $client = $this->curlClientFactory->create();

        if ($method === 'POST') {
            $client->post($uri, $payload);
        } else {
            $client->get($uri);
        }

        $status = $client->getStatus();

        if ($status >= 400 && $status <= 499) {
            throw new ClientException(__('Client error: %1', $status));
        } else if ($status >= 500 && $status <= 599) {
            throw new ClientException(__('Server error: %1', $status));
        }

        $headers = $client->getHeaders();

        if ($this->shouldBeJSONResponse($headers)) {
            try {
                $decoded = $this->jsonSerializer->unserialize($client->getBody());
            } catch (InvalidArgumentException $e) {
                throw new ConverterException(__('Could not parse JSON response.'), $e);
            }

            return $decoded;
        }

        return [$client->getBody()];
    }

    /**
     * Is the response expected to be a JSON?
     *
     * @param array $headers
     * @return bool
     */
    protected function shouldBeJSONResponse(array $headers)
    {
        foreach ($headers as $header => $value) {
            if (strtolower($header) === 'content-type' && strtolower($value) == 'application/json') {
                return true;
            }
        }

        return false;
    }

}
