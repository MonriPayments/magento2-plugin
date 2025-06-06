<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway;

use Magento\Framework\App\Config\ScopeConfigInterface;

class VaultConfig extends \Magento\Payment\Gateway\Config\Config
{
    public const CODE = 'monri_payments_vault';
    private const VAULT_SETTINGS = ['shop_id', 'secret_key', 'active'];

    public const LANGUAGE = 'language';

    /**
     * @var Config
     */
    private $vaultProviderConfig;

    /**
     * VaultConfig constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $vaultProviderConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $vaultProviderConfig
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
}
