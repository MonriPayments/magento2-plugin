<?php

namespace Monri\Payments\Gateway;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    const CODE = 'monri_payments';

    const GATEWAY_PRODUCTION_URL = 'https://ipg.monri.com/%s';
    const GATEWAY_SANDBOX_URL = 'https://ipgtest.monri.com/%s';

    const SANDBOX = 'sandbox';

    const CLIENT_KEY = 'client_key';

    const CLIENT_AUTHENTICITY_TOKEN = 'client_authenticity_token';

    const TRANSACTION_TYPE = 'transaction_type';

    const INSTALLMENTS = 'installments';

    const INSTALLMENTS_DISABLED = 'disabled';

    const LANGUAGE = 'language';

    const TRANSACTION_TYPE_CAPTURE = 'capture';

    const TRANSACTION_TYPE_AUTHORIZE = 'authorize';

    const FORM_ENDPOINT = 'v2/form';

    protected $_availableCurrencies = [
        'USD',
        'EUR',
        'BAM',
        'HRK',
    ];

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getGatewayBaseURL($storeId = null)
    {
        return $this->getIsSandboxMode($storeId) ? self::GATEWAY_SANDBOX_URL : self::GATEWAY_PRODUCTION_URL;
    }

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getClientKey($storeId = null)
    {
        return $this->getValue(self::CLIENT_KEY, $storeId);
    }

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getClientAuthenticityToken($storeId = null)
    {
        return $this->getValue(self::CLIENT_AUTHENTICITY_TOKEN, $storeId);
    }

    /**
     * @param string $resource
     * @param null|int $storeId
     * @return string
     */
    public function getGatewayResourceURL($resource, $storeId = null)
    {
        return sprintf($this->getGatewayBaseURL($storeId), $resource);
    }

    /**
     * @param string $resource
     * @param string $object
     * @param null|int $storeId
     * @return string
     */
    public function getGatewayTransactionManagementURL($resource, $object, $storeId = null)
    {
        return $this->getGatewayResourceURL(sprintf('transactions/%s/%s.xml', $object, $resource), $storeId);
    }

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getTransactionType($storeId = null)
    {
        return $this->getValue(self::TRANSACTION_TYPE, $storeId);
    }

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getFormRedirectURL($storeId = null)
    {
        return $this->getGatewayResourceURL(self::FORM_ENDPOINT, $storeId);
    }

    /**
     * @param null|int $storeId
     * @return string
     */
    public function getGatewayLanguage($storeId = null)
    {
        return $this->getValue(self::LANGUAGE, $storeId);
    }

    /**
     * @param null|int $storeId
     * @return bool
     */
    public function getIsSandboxMode($storeId = null)
    {
        return (bool) $this->getValue(self::SANDBOX, $storeId);
    }

    /**
     * @param null|int $storeId
     * @return int|null
     */
    public function getInstallments($storeId = null)
    {
        return $this->getValue(self::INSTALLMENTS, $storeId);
    }

    /**
     * @param null $storeId
     * @return array
     */
    public function getAvailableCurrencyCodes($storeId = null)
    {
        return $this->_availableCurrencies;
    }
}
