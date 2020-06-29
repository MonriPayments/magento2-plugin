<?php


namespace Monri\Payments\Gateway\Http;


use Magento\Framework\Convert\ConvertArray;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Monri\Payments\Gateway\Config;
use Monri\Payments\Gateway\Request\OrderUpdateBuilder;

class OrderUpdateTransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $resource;

    /**
     * @var ConvertArray
     */
    private $convertArray;

    /**
     * OrderUpdateTransferFactory constructor.
     * @param TransferBuilder $transferBuilder
     * @param ConvertArray $convertArray
     * @param Config $config
     * @param string $resource
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        ConvertArray $convertArray,
        Config $config,
        $resource = ''
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
        $this->resource = $resource;
        $this->convertArray = $convertArray;
    }

    /**
     * Builds order update transfer object.
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $orderNumber = $request[OrderUpdateBuilder::TRANSACTION_GROUP_FIELD][OrderUpdateBuilder::ORDER_NUMBER_FIELD];

        $storeId = null;
        if (isset($request['__store'])) {
            $storeId = $request['__store'];
            unset($request['__store']);
        }

        $uri = $this->config->getGatewayTransactionManagementURL($this->resource, $orderNumber, $storeId);

        try {
            $payload = $this->constructXmlPayload($request);
        } catch (LocalizedException $e) {
            //TODO: LOG
            $payload = $request;
        }

        return $this->transferBuilder
                ->setUri($uri)
                ->setMethod('POST')
                ->setBody($payload)
                ->build();
    }

    /**
     * @param array $payload
     * @return string
     * @throws LocalizedException
     */
    protected function constructXmlPayload(array $payload)
    {
        $rootNodeName = array_key_first($payload);

        $xml = $this->convertArray->assocToXml($payload[$rootNodeName], $rootNodeName);

        return $xml->asXML();
    }
}
