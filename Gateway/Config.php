<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    public const CODE = 'monri_payments';

    public const GATEWAY_PRODUCTION_URL = 'https://ipg.monri.com/%s';
    public const GATEWAY_SANDBOX_URL = 'https://ipgtest.monri.com/%s';

    public const SANDBOX = 'sandbox';

    public const CLIENT_KEY = 'client_key';

    public const CLIENT_AUTHENTICITY_TOKEN = 'client_authenticity_token';

    public const TRANSACTION_TYPE = 'transaction_type';

    public const INSTALLMENTS = 'installments';

    public const INSTALLMENTS_DISABLED = 'disabled';

    public const LANGUAGE = 'language';

    public const TRANSACTION_TYPE_CAPTURE = 'capture';

    public const TRANSACTION_TYPE_AUTHORIZE = 'authorize';

    public const TRANSACTION_TYPE_PURCHASE = 'purchase';

    public const FORM_ENDPOINT = 'v2/form';

    public const ORDER_STATUS_ENDPOINT = 'orders/show';

    public const ALLOW_INSTALLMENTS = 'allow_installments';

    public const SUPPORTED_PAYMENT_METHODS = 'supported_payment_methods';

    /**
     * @var string[]
     */
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
     * Returns order status info url for a given store ID.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getOrderStatusResourceURL($storeId = null)
    {
        return $this->getGatewayResourceURL(self::ORDER_STATUS_ENDPOINT, $storeId);
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
     * Returns the available currency codes
     *
     * @return array
     */
    public function getAvailableCurrencyCodes()
    {
        return $this->_availableCurrencies;
    }

    /**
     * Returns the allow installments variable
     *
     * @param null|int $storeId
     * @return bool
     */
    public function getAllowInstallments($storeId = null)
    {
        return $this->getValue(self::ALLOW_INSTALLMENTS, $storeId);
    }

    /**
     * Returns additional payment methods
     *
     * @param null|int $storeId
     * @return array
     */
    public function getSupportedPaymentMethods($storeId = null)
    {
        return $this->getValue(self::SUPPORTED_PAYMENT_METHODS, $storeId);
    }
}
