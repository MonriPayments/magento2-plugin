<?php

namespace Monri\Payments\Model\Ui\WSPay;

use Monri\Payments\Gateway\Config\WSPayVaultConfig;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class ConfigProvider implements ConfigProviderInterface
{
    /**
     * Payment identifier
     */
    private const CODE = 'monri_wspay';

    private const DESCRIPTION_PATH = 'payment/monri_wspay/description';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * ConfigProvider constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        UrlInterface $urlBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Retrieve assoc array of payment method configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => [
                    'buildFormDataUrl' => $this->urlBuilder->getUrl('wspay/order/buildFormData'),
                    'description' => $this->getDescription(),
                    'vaultCode' => WSPayVaultConfig::CODE
                ]
            ]
        ];
    }

    /**
     * Retrieve description
     *
     * @return mixed
     */
    public function getDescription(): mixed
    {
        return $this->scopeConfig->getValue(self::DESCRIPTION_PATH, ScopeInterface::SCOPE_STORE);
    }
}
