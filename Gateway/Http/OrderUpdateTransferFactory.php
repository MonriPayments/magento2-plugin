<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Http;

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
     * OrderUpdateTransferFactory constructor.
     * @param TransferBuilder $transferBuilder
     * @param Config $config
     * @param string $resource
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        Config $config,
        $resource = ''
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
        $this->resource = $resource;
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

        return $this->transferBuilder
                ->setUri($uri)
                ->setMethod('POST')
                ->setBody($request)
                ->build();
    }
}
