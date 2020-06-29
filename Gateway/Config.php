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

    public function getGatewayBaseURL($storeId = null) {
        return $this->getIsSandboxMode($storeId) ? self::GATEWAY_SANDBOX_URL : self::GATEWAY_PRODUCTION_URL;
    }

    public function getClientKey($storeId = null) {
        return $this->getValue(self::CLIENT_KEY, $storeId);
    }

    public function getClientAuthenticityToken($storeId = null) {
        return $this->getValue(self::CLIENT_AUTHENTICITY_TOKEN, $storeId);
    }

    public function getGatewayResourceURL($resource, $storeId = null) {
        return sprintf($this->getGatewayBaseURL($storeId), $resource);
    }

    public function getGatewayTransactionManagementURL($resource, $object, $storeId = null)
    {
        return $this->getGatewayResourceURL(sprintf('transactions/%s/%s.xml', $object, $resource), $storeId);
    }

    public function getTransactionType($storeId = null) {
        return $this->getValue(self::TRANSACTION_TYPE, $storeId);
    }

    public function getFormRedirectURL($storeId = null) {
        return $this->getGatewayResourceURL(self::FORM_ENDPOINT, $storeId);
    }

    public function getGatewayLanguage($storeId = null) {
        return $this->getValue(self::LANGUAGE, $storeId);
    }

    public function getIsSandboxMode($storeId = null) {
        return $this->getValue(self::SANDBOX, $storeId);
    }

    public function getInstallments($storeId = null) {
        return $this->getValue(self::INSTALLMENTS, $storeId);
    }
}
