<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Monri\Payments\Gateway\Config;

class OrderStatusTransferFactory implements TransferFactoryInterface
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
     * OrderUpdateTransferFactory constructor.
     * @param TransferBuilder $transferBuilder
     * @param Config $config
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        Config $config
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
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

        $uri = $this->config->getOrderStatusResourceURL($storeId);

        return $this->transferBuilder
                ->setUri($uri)
                ->setMethod('POST')
                ->setBody($request)
                ->build();
    }
}
