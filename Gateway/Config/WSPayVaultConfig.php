<?php

namespace Monri\Payments\Gateway\Config;

use Monri\Payments\Gateway\Config\WSPay;
use Magento\Framework\App\Config\ScopeConfigInterface;

class WSPayVaultConfig extends \Magento\Payment\Gateway\Config\Config implements WSPayConfigInterface
{

    public const CODE = 'monri_wspay_vault';


    private const VAULT_SETTINGS = ['shop_id', 'secret_key', 'active', 'instant_purchase'];

    /**
     * @var WSPay
     */
    private $vaultProviderConfig;

    /**
     * VaultConfig constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param WSPay $vaultProviderConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WSPay $vaultProviderConfig
    ) {
        parent::__construct($scopeConfig, self::CODE, self::DEFAULT_PATH_PATTERN);
        $this->vaultProviderConfig = $vaultProviderConfig;
    }

    /**
     * Inherit part of config from monri_wspay vault provider
     *
     * @param string $field
     * @param int|null $storeId
     * @return mixed|null
     */
    public function getValue($field, $storeId = null)
    {
        //@todo: is there a better/dynamic way to distinguish VAULT_SETTINGS?
        return in_array($field, self::VAULT_SETTINGS) ?
            parent::getValue($field, $storeId) :
            $this->vaultProviderConfig->getValue($field, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getFormEndpoint(?int $storeId = null): string
    {
        return $this->vaultProviderConfig->getFormEndpoint($storeId);
    }

    /**
     * @inheritDoc
     */
    public function getApiEndpoint(string $api, ?int $storeId = null): string
    {
        return $this->vaultProviderConfig->getApiEndpoint($api, $storeId);
    }
}
