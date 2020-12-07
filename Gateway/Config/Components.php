<?php

declare(strict_types=1);

namespace Monri\Payments\Gateway\Config;

/**
 * Class Components
 */
class Components extends \Magento\Payment\Gateway\Config\Config
{
    const CODE = 'monri_components';

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

    const TRANSACTION_TYPE_PURCHASE = 'purchase';

    const FORM_ENDPOINT = 'v2/form';

    protected $_availableCurrencies = [
        'USD',
        'EUR',
        'BAM',
        'HRK',
    ];

    /**
     * Returns the base gateway URL for a given store ID.
     *
     * @param null|int $storeId
     * @return string
     */
    public function getGatewayBaseURL($storeId = null)
    {
        return $this->getIsSandboxMode($storeId) ? self::GATEWAY_SANDBOX_URL : self::GATEWAY_PRODUCTION_URL;
    }

    /**
     * Returns the configured client key.
     *
     * @param null|int $storeId
     * @return string
     */
    public function getClientKey($storeId = null)
    {
        return $this->getValue(self::CLIENT_KEY, $storeId);
    }

    /**
     * Returns the configured authenticity token.
     *
     * @param null|int $storeId
     * @return string
     */
    public function getClientAuthenticityToken($storeId = null)
    {
        return $this->getValue(self::CLIENT_AUTHENTICITY_TOKEN, $storeId);
    }

    /**
     * Returns the gateway URL for a given resource and store ID.
     *
     * @param string $resource
     * @param null|int $storeId
     * @return string
     */
    public function getGatewayResourceURL($resource, $storeId = null)
    {
        return sprintf($this->getGatewayBaseURL($storeId), $resource);
    }

    /**
     * Returns the transaction management URL for a given resource object and action.
     *
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
     * @param $resource
     * @param $object
     * @param null $storeId
     * @return string
     */
    public function getGatewayPaymentCreateURL($storeId = null)
    {
        return $this->getGatewayResourceURL('v2/payment/new', $storeId);
    }

    /**
     * Returns the configured transaction type ('purchase' or 'authorize').
     *
     * @param null|int $storeId
     * @return string
     */
    public function getTransactionType($storeId = null)
    {
        return $this->getValue(self::TRANSACTION_TYPE, $storeId);
    }

    /**
     * Returns the base redirect URL for a given store ID.
     *
     * @param null|int $storeId
     * @return string
     */
    public function getFormRedirectURL($storeId = null)
    {
        return $this->getGatewayResourceURL(self::FORM_ENDPOINT, $storeId);
    }

    /**
     * Returns the configured language for gateway.
     *
     * @param null|int $storeId
     * @return string
     */
    public function getGatewayLanguage($storeId = null)
    {
        return $this->getValue(self::LANGUAGE, $storeId);
    }

    /**
     * Returns true if sandbox mode is enabled, false if otherwise.
     *
     * @param null|int $storeId
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsSandboxMode($storeId = null)
    {
        return (bool) $this->getValue(self::SANDBOX, $storeId);
    }

    /**
     * Returns the configured number of installments.
     *
     * @param null|int $storeId
     * @return int|null
     */
    public function getInstallments($storeId = null)
    {
        return $this->getValue(self::INSTALLMENTS, $storeId);
    }

    /**
     * Returns the available currency codes for a given store ID.
     *
     * @param null $storeId
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getAvailableCurrencyCodes($storeId = null)
    {
        return $this->_availableCurrencies;
    }
}