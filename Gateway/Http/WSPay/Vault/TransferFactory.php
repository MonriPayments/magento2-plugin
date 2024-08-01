<?php

namespace Monri\Payments\Gateway\Http\WSPay\Vault;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Favicode\WSPay\Gateway\VaultConfig;

class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var VaultConfig
     */
    private $config;

    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * VaultTransferFactory constructor.
     *
     * @param TransferBuilder $transferBuilder
     * @param VaultConfig $config
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        VaultConfig $config
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
    }
    public function create(array $request)
    {
        $storeId = isset($request['__store']) ? (int)$request['__store'] : null;
        unset($request['__store']);

        return $this->transferBuilder
            ->setMethod('POST')
            ->setUri($this->config->getApiEndpoint('processpayment', $storeId))
            ->setBody($request)
            ->build();
    }
}
