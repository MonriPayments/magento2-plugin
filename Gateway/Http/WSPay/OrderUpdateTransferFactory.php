<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Http\WSPay;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Monri\Payments\Gateway\Config\WSPay;
use Monri\Payments\Gateway\Request\WSPay\FormDataBuilder;

class OrderUpdateTransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var WSPay
     */
    private $config;

    /**
     * @var string
     */
    private $resource;

    /**
     * OrderUpdateTransferFactory constructor.
     * @param TransferBuilder $transferBuilder
     * @param WSPay $config
     * @param string $resource
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        WSPay $config,
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
        $storeId = null;
        if (isset($request['__store'])) {
            $storeId = $request['__store'];
            unset($request['__store']);
        }

        $uri = $this->config->getGatewayTransactionManagementURL($this->resource, $storeId);

        return $this->transferBuilder
                ->setUri($uri)
                ->setMethod('POST')
                ->setBody($request)
                ->build();
    }
}
