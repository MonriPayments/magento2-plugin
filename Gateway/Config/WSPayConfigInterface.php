<?php

namespace Monri\Payments\Gateway\Config;

interface WSPayConfigInterface extends \Magento\Payment\Gateway\ConfigInterface
{
    public const FORM_ENDPOINT = 'https://form.wspay.biz/authorization.aspx';
    public const TEST_FORM_ENDPOINT = 'https://formtest.wspay.biz/authorization.aspx';

    public const API_ENDPOINT = 'https://secure.wspay.biz/api/services/%s';
    public const TEST_API_ENDPOINT = 'https://test.wspay.biz/api/services/%s';

    /**
     * Get form redirect url
     *
     * @param int|null $storeId
     * @return string
     */
    public function getFormEndpoint(?int $storeId): string;

    /**
     * Resolve API endpoint
     *
     * @param string $resource
     * @param int|null $storeId
     * @return string
     */
    public function getApiEndpoint(string $resource, ?int $storeId): string;
}
