<?php

namespace Monri\Payments\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Monri\Payments\Gateway\Config;

class WSPay extends \Magento\Payment\Gateway\Config\Config implements WSPayConfigInterface
{
    public const CODE = 'monri_wspay';

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
                             $methodCode = self::CODE,
                             $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
    }

    /**
     * @inheritDoc
     */
    public function getFormEndpoint(?int $storeId = null): string
    {
        return $this->getValue('test_mode', $storeId) ? self::TEST_FORM_ENDPOINT : self::FORM_ENDPOINT;
    }

    /**
     * @inheritDoc
     */
    public function getApiEndpoint(string $api, ?int $storeId = null): string
    {
        $endpoint = $this->getValue('test_mode', $storeId) ? self::TEST_API_ENDPOINT : self::API_ENDPOINT;
        return sprintf($endpoint, $api);
    }
}
