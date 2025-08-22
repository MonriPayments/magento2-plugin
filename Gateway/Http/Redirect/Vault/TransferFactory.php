<?php

namespace Monri\Payments\Gateway\Http\Redirect\Vault;

use Magento\Payment\Gateway\Http\Transfer;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Monri\Payments\Gateway\Config as VaultConfig;
use Magento\Payment\Gateway\Http\TransferInterface;

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

    /**
     * Builds vault transfer object
     *
     * @param array $request
     *
     * @return Transfer|TransferInterface
     */
    public function create(array $request)
    {
        $storeId = isset($request['__store']) ? (int)$request['__store'] : null;
        unset($request['__store']);

        return $this->transferBuilder
            ->setMethod('POST')
            ->setUri($this->config->getTransactionEndpoint($storeId))
            ->setBody($request)
            ->build();
    }
}
