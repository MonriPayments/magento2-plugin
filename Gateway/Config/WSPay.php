<?php

namespace Monri\Payments\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class WSPay extends \Magento\Payment\Gateway\Config\Config implements WSPayConfigInterface
{

    public const CODE = 'monri_wspay';

    public const TEST_MODE = 'test_mode';

    /**
     * @var string[]
     */
    public const AVAILABLE_CURRENCIES = [
        'USD',
        'EUR',
        'BAM',
        'CAD',
        'RSD',
        'GBP',
        'AUD',
        'HUF',
        'CZK',
        'ZAR',
        'BGN',
        'RON',
        'CHF',
        'SEK',
        'PLN',
        'NOK',
        'DKK',
        'MKD'
    ];

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct( // @codingStandardsIgnoreLine
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
     * Returns the API endpoint for a given resource object and action.
     *
     * @param string $resource
     * @param int|null $storeId
     *
     * @return string
     */
    public function getApiEndpoint(string $resource, ?int $storeId): string
    {
        $endpoint = $this->getIsTestMode($storeId) ? self::TEST_API_ENDPOINT : self::API_ENDPOINT;
        return sprintf($endpoint, $resource) ;
    }

    /**
     * Check if test mode
     *
     * @param int $storeId
     *
     * @return bool
     */
    public function getIsTestMode($storeId = null): bool
    {
        return (bool) $this->getValue(self::TEST_MODE, $storeId);
    }

    /**
     * Returns the available currency codes
     *
     * @return array
     */
    public function getAvailableCurrencyCodes()
    {
        return self::AVAILABLE_CURRENCIES;
    }
}
