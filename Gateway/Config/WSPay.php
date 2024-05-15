<?php

namespace Monri\Payments\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class WSPay extends \Magento\Payment\Gateway\Config\Config
{
    public const FORM_ENDPOINT = 'https://form.wspay.biz/authorization.aspx';
    public const TEST_FORM_ENDPOINT = 'https://formtest.wspay.biz/authorization.aspx';
    public const API_ENDPOINT = 'https://secure.wspay.biz/api/services/%s';
    public const TEST_API_ENDPOINT = 'https://test.wspay.biz/api/services/%s';

    public const CODE = 'monri_wspay';

    public const TEST_MODE = 'test_mode';

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
     * Returns the transaction management URL for a given resource object and action.
     *
     * @param string $resource
     * @param null|int $storeId
     * @return string
     */
    public function getGatewayTransactionManagementURL($resource, $storeId): string
    {
        $endpoint = $this->getIsTestMode($storeId) ? self::TEST_API_ENDPOINT : self::API_ENDPOINT;
        return sprintf($endpoint, $resource) ;
    }

    /**
     * @inheritDoc
     */
    public function getIsTestMode($storeId = null)
    {
        return (bool) $this->getValue(self::TEST_MODE, $storeId);
    }
}
