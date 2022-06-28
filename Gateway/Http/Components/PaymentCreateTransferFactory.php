<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Http\Components;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Monri\Payments\Model\Crypto\Components\Digest;
use Monri\Payments\Gateway\Config\Components as Config;

class PaymentCreateTransferFactory implements TransferFactoryInterface
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
     * @var Digest
     */
    private $digest;

    /**
     * @var Json
     */
    private $json;

    /**
     * PaymentInitializeTransferFactory constructor.
     *
     * @param TransferBuilder $transferBuilder
     * @param Config $config
     * @param Digest $digest
     * @param Json $json
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        Config $config,
        Digest $digest,
        Json $json
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
        $this->digest = $digest;
        $this->json = $json;
    }

    /**
     * Builds order update transfer object.
     *
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        $storeId = null;
        if (isset($request['__store'])) {
            $storeId = $request['__store'];
            unset($request['__store']);
        }

        $uri = $this->config->getGatewayPaymentCreateURL($storeId);

        //@todo: move this to Client?
        $clientAuthenticityToken = $this->config->getClientAuthenticityToken($storeId);
        $timestamp = time();
        $digest = $this->digest->build($timestamp, $this->json->serialize($request), $storeId);

        return $this->transferBuilder
                ->setUri($uri)
                ->setMethod('POST')
                ->setHeaders(['Authorization' => "WP3-v2 $clientAuthenticityToken $timestamp $digest"])
                ->setBody($request)
                ->build();
    }
}
