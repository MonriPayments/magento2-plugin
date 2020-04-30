<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 */

namespace Leftor\PikPay\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use GuzzleHttp\ClientFactory;
use Leftor\PikPay\Model\RawDetailsFormatter;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class PikPay extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_PIKPAY_CODE = 'pikpay';

    const PAYMENT_ACTION_AUTHORIZE = 'authorize';
    const PAYMENT_ACTION_PURCHASE = 'purchase';

    protected $_code = self::PAYMENT_METHOD_PIKPAY_CODE;
    protected $_isGateway = false;
    protected $_isOffline = true;
    protected $_canCapture = true;
    protected $_canAuthorize = true;
    protected $_canRefund = true;
    protected $_canVoid = true;
    protected $_isInitializeNeeded = true;
    protected $_canCapturePartial = true;

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var \Leftor\PikPay\Model\RawDetailsFormatter
     */
    private $rawDetailsFormatter;

    /**
     * @var OrderSender
     */
    private $orderSender;

    protected $_remoteAddress;
    protected $_supportedCurrencyCodes = array('BAM', 'HRK', 'EUR', 'USD');

    private $orderRepository;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        OrderRepositoryInterface $orderRepository,
        ClientFactory $clientFactory,
        RawDetailsFormatter $rawDetailsFormatter,
        OrderSender $orderSender
    )
    {
        $this->_remoteAddress = $remoteAddress;
        $this->orderRepository = $orderRepository;
        $this->clientFactory = $clientFactory;
        $this->rawDetailsFormatter = $rawDetailsFormatter;
        $this->orderSender = $orderSender;
        //$this->_customLogger = $customLogger;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger
        );
    }

    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }
        return true;
    }

    public function digest($key, $orderNumber, $amount, $currency)
    {
        return sha1($key . $orderNumber . $amount . $currency);
    }

    public function digestV2($key, $orderNumber, $amount, $currency)
    {
        return hash('sha512', $key . $orderNumber . $amount . $currency);
    }

    protected function fullAddress($address)
    {
        if ($address != null && count($address) > 1) {
            return $address[0] . " " . $address[1];
        } else
            return $address[0];
    }

    public function checkoutRequest($quote, $order)
    {

        $params = [];
        $amount = round(($order->getGrandTotal()), 2) * 100;
        $orderNumber = $order->getRealOrderId();

        $address = $order->getBillingAddress()->getStreet();

        if ($this->getPaymentType() == 1) {
            $digest = $this->digestV2(
                $this->getConfigData("key"),
                $orderNumber,
                $amount,
                $this->getConfigData("currency"));
        }

        if ($this->getConfigData("language") == 'ba') {
            $language = 'hr';
        } else {
            $language = $this->getConfigData("language");
        }

        $params["ch_full_name"] = $order->getBillingAddress()->getName();
        $params["ch_address"] = $this->fullAddress($address);
        $params["ch_city"] = $order->getBillingAddress()->getCity();
        $params["ch_zip"] = $order->getBillingAddress()->getPostcode();
        $params["ch_country"] = $order->getBillingAddress()->getCountryId();
        $params["ch_phone"] = $order->getBillingAddress()->getTelephone();
        $params["ch_email"] = $order->getCustomerEmail();
        $params["order_info"] = "Order: " . $order->getRealOrderId();
        $params["order_number"] = $orderNumber;
        $params["amount"] = $amount;
        $params["currency"] = $this->getConfigData("currency");
        $params["language"] = $language;
        $params["transaction_type"] = $this->getTrasactionType($order);
        $params["authenticity_token"] = $this->getConfigData("auth_token");
        $params["digest"] = $digest;
        $params["ip"] = $this->getIp();

        return $params;
    }

    protected function getTrasactionType($order)
    {
        $action = $this->getConfigData("payment_action", $order->getStoreId());
        if ($action == \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE) {
            return self::PAYMENT_ACTION_AUTHORIZE;
        } elseif ($action == \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
            return self::PAYMENT_ACTION_PURCHASE;
        }
    }

    /**
     * @param $params
     * @return mixed
     */
    public function directCheckoutRequest($params)
    {
        $xmlData = '<?xml version="1.0" encoding="UTF-8"?>
		<transaction>';
        foreach ($params as $attribute => $value) {
            $attribute = str_replace('_', '-', $attribute);
            if (trim($value) != '') $xmlData .= '<' . $attribute . '>' . trim($value) . '</' . $attribute . '>';
        }
        $xmlData .= '</transaction>';

        $testing = $this->getConfigData('test_mode');
        $procesor = $this->getConfigData('procesor');

        switch ($testing) {
            case 1:
                $url = 'https://ipgtest.' . $procesor . '/api';
                break;
            case 0:
                $url = 'https://ipg.' . $procesor . '/api';
                break;
        }
        $ch = curl_init();
        $headers = array();
        $headers[] = 'Accept: application/xml';
        $headers[] = 'Content-Type: application/xml';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        @curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, 'TLSv1');
        @curl_setopt($ch, CURLOPT_SSLVERSION, defined('CURL_SSLVERSION_TLSv1') ? CURL_SSLVERSION_TLSv1 : 1);
        @curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->_logger->info('cURL Error: ' . curl_error($ch));
            die(curl_error($ch));
        }
        curl_close($ch);

        $resultXml = false;
        if ($this->isValidXML($result)) {
            $resultXml = new \SimpleXmlElement($result);
        }

        $resultXml = (array)$resultXml;

        foreach ($resultXml as $resKey => $resValue) {
            $resultRequest[str_replace('-', '_', $resKey)] = $resValue;
        }
        $this->_logger->info(print_r($resultRequest, true));

        return $resultRequest;

    }

    public function isValidXML($xml)
    {

        $prev = libxml_use_internal_errors(true);
        $ret = true;
        try {
            new \SimpleXMLElement($xml);
        } catch (Exception $e) {
            $ret = false;
        }
        if (count(libxml_get_errors()) > 0) {
            // There has been XML errors
            $ret = false;
        }

        // Tidy up.
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $ret;
    }

    protected function getIp()
    {
        return $this->_remoteAddress->getRemoteAddress();
    }

    public function getKey()
    {
        return $this->getConfigData("key");
    }

    public function getOrderStatusSuccess()
    {
        return $this->getConfigData("order_status_on_success");
    }

    public function getOrderStatusFail()
    {
        return $this->getConfigData("order_status_on_fail");
    }

    public function getPaymentType()
    {
        return $this->getConfigData('payment_type');
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

    /**
     * @return mixed
     */
    public function canPayInInstallments()
    {
        if ($this->getConfigData('installments') == 1) {
            return true;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function numberOfInstallments()
    {
        return $this->getConfigData('installments_number');
    }

    /**
     * @return mixed
     */
    public function getInstallmentsMinimum()
    {
        return $this->getConfigData('installments_minimum');
    }

    /**
     * @return mixed
     */
    public function getCcBins()
    {
        return $this->getConfigData('bin');
    }

    /**
     * @return float|int
     */
    public function getOrderPercentage()
    {
        $configValue = $this->getConfigData('percentage');
        if ($configValue) {
            $realValue = ((float)$configValue / 100) + 1;
            return $realValue;
        } else return 1;
    }

    public function getOrderPercentageForInstallments()
    {
        $configValue = $this->getConfigData('installments_percentage');
        if ($configValue) {
            $realValue = ((float)$configValue / 100) + 1;
            return $realValue;
        } else return 1;
    }

    /** List of response codes */
    public function responseCodes()
    {

        $codes = [
            0000 => "Transaction Approved",
            1001 => "Card Expired",
            1002 => "Card Suspicious",
            1003 => "Card Suspended",
            1004 => "Card Stolen",
            1005 => "Card Lost",
            1011 => "Card Not Found",
            1012 => "Cardholder Not Found",
            1014 => "Account Not Found",
            1015 => "Invalid Request",
            1016 => "Not Sufficient Funds",
            1017 => "Previously Reversed",
            1018 => "Previously Reversed",
            1019 => "Further activity prevents reversal",
            1020 => "Further activity prevents void",
            1021 => "Original transaction has been voided",
            1022 => "Preauthorization is not allowed for this card",
            1023 => "Only full 3D authentication is allowed for this card",
            1024 => "Installments are not allowed for this card",
            1025 => "Transaction with installments can not be send as preauthorization",
            1026 => "Installments are not allowed for non ZABA cards",
            1050 => "Transaction declined",
            1802 => "Missing fields",
            1803 => "Extra fields exist",
            1804 => "Invalid card number",
            1806 => "Card not active",
            1808 => "Card not configured",
            1810 => "Invalid amount",
            1811 => "System Error, Database",
            1812 => "System Error, Transaction",
            1813 => "Cardholder not active",
            1814 => "Cardholder not configured",
            1815 => "Cardholder expired",
            1816 => "Original not found",
            1817 => "Usage Limit Reached",
            1818 => "Configuration error",
            1819 => "Invalid terminal",
            1820 => "Inactive terminal",
            1821 => "Invalid merchant",
            1822 => "Duplicate entity",
            1823 => "Invalid Acquirer",
            2000 => "Internal error - host down",
            2001 => "Internal error - host timeout",
            2002 => "Internal error - invalid message",
            2003 => "Internal error - message format error",
            2013 => "3D Secure error - invalid request",
            3000 => "Time expired",
            3100 => "Function not supported",
            3200 => "Timeout",
            3201 => "Authorization host not active",
            3202 => "System not ready",
            4001 => "3D Secure error - ECI 7",
            4002 => "3D Secure error - not 3D Secure, store policy",
            4003 => "3D secure error - not authenticated",
            5018 => "RISK: Minimum amount per transaction",
            5019 => "RISK: Maximum amount per transaction",
            5001 => "RISK: Number of repeats per PAN",
            5020 => "RISK: Number of approved transactions per PAN",
            5003 => "RISK: Number of repeats per BIN",
            5016 => "RISK: Total sum on amount",
            5021 => "RISK: Sum on amount of approved transactions per PAN",
            5022 => "RISK: Sum on amount of approved transactions per BIN",
            5005 => "RISK: Percentage of declined transactions",
            5009 => "RISK: Number of chargebacks",
            5010 => "RISK: Sum on amount of chargebacks",
            5006 => "RISK: Number of refunded transactions",
            5007 => "RISK: Percentage increment of sum on amount of refunded transactions",
            5023 => "RISK: Number of approved transactions per PAN and MCC on amount",
            5011 => "RISK: Number of retrieval requests",
            5012 => "RISK: Sum on amount of retrieval requests",
            5013 => "RISK: Average amount per transaction",
            5014 => "RISK: Percentage increment of average amount per transaction",
            5015 => "RISK: Percentage increment of number of transactions",
            5017 => "RISK: Percentage increment of total sum on amount",
            5050 => "RISK: Number of repeats per IP",
            5051 => "RISK: Number of repeats per cardholder name",
            5052 => "RISK: Number of repeats per cardholder e-mail",
            6000 => "Internal error - systan mismatch"
        ];

        return $codes;
    }

    public function initialize($paymentAction, $stateObject)
    {

        $payment = $this->getInfoInstance();
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        $stateObject->setState(Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus($this->getConfigData('order_status'));
        $stateObject->setIsNotified(false);

        return $this;
    }


    public function handleResponse($response, $order)
    {

        $action = $this->getConfigData("payment_action", $order->getStoreId());
        $payment = $order->getPayment();

        $payment->setTransactionId($response["order_number"]);
        $payment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $response
        );

        if ($action == \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE) {

            $payment->setIsTransactionClosed(false);
            $payment->registerAuthorizationNotification($order->getBaseGrandTotal());

        } elseif ($action == \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
            $order->setState(Order::STATE_PROCESSING);
            $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);
        }

        $this->orderRepository->save($order);

        if (!$order->getEmailSent()) {
            $this->orderSender->send($order);
        }

    }

    protected function getApiUrl()
    {
        $testing = $this->getConfigData('test_mode');
        $procesor = $this->getConfigData('procesor');

        switch ($testing) {
            case 1:
                $url = 'https://ipgtest.' . $procesor . '/';
                break;
            case 0:
                $url = 'https://ipg.' . $procesor . '/';
                break;
        }

        return $url;

    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $order = $payment->getOrder();
        $baseUrl = $this->getApiUrl();
        $requestUrl = 'transactions/' . $order->getIncrementId() . '/capture.xml';
        $merchantReference = uniqid($order->getIncrementId() . '-capture-');

        $digestAmount = $amount * 100;
        $digestString = $this->getConfigData("key", $order->getStoreId()) . $order->getIncrementId() . $digestAmount . $this->getConfigData("currency", $order->getStoreId());

        $digest = sha1($digestString);

        $xmlData = (string)$this->_generateXmlBody(
            $digestAmount,
            $this->getConfigData("currency", $order->getStoreId()),
            $digest, $this->getConfigData("auth_token"),
            $order->getIncrementId()
        );

        $client = $this->clientFactory->create(
            [
                'config' => [
                    'base_uri' => $baseUrl,
                    'timeout' => 5.0
                ]
            ]
        );

        $response = $client->request('POST', $requestUrl, [
            'body' => $xmlData,
            'headers' => [
                'Accept' => 'application/xml',
                'Content-Type' => 'application/xml'
            ]
        ]);

        if (!in_array($response->getStatusCode(), [200, 201])) {
            throw new LocalizedException(__('Error with Capture Action.'));
            return $this;
        }

        $result = (array)$this->_convertXmlToArray($response->getBody()->getContents());

        if (!isset($result['status']) || $result['status'] != 'approved') {
            throw new LocalizedException(__('Trasaction is not Approved!'));
            return $this;
        }

        $trasactionData = $this->rawDetailsFormatter->format($result);
        $payment->setTransactionId($merchantReference);
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $trasactionData);
        $payment->setShouldCloseParentTransaction(true);
        return $this;
    }


    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {

        $order = $payment->getOrder();
        $digestAmount = $amount * 100;
        $merchantReference = uniqid($order->getIncrementId() . '-refund-');

        $baseUrl = $this->getApiUrl();
        $requestUrl = 'transactions/' . $order->getIncrementId() . '/refund.xml';

        $digestString = $this->getConfigData("key", $order->getStoreId()) . $order->getIncrementId() . $digestAmount . $this->getConfigData("currency", $order->getStoreId());

        $digest = sha1($digestString);

        $xmlData = (string)$this->_generateXmlBody(
            $digestAmount,
            $this->getConfigData("currency", $order->getStoreId()),
            $digest, $this->getConfigData("auth_token"),
            $order->getIncrementId()
        );

        $client = $this->clientFactory->create(
            [
                'config' => [
                    'base_uri' => $baseUrl,
                    'timeout' => 5.0
                ]
            ]
        );

        $response = $client->request('POST', $requestUrl, [
            'body' => $xmlData,
            'headers' => [
                'Accept' => 'application/xml',
                'Content-Type' => 'application/xml'
            ]
        ]);


        if (!in_array($response->getStatusCode(), [200, 201])) {
            throw new LocalizedException(__('Error with Capture Action.'));
            return $this;
        }

        $result = (array)$this->_convertXmlToArray($response->getBody()->getContents());

        if (!isset($result['status']) || $result['status'] != 'approved') {
            throw new LocalizedException(__('Trasaction is not Approved!'));
            return $this;
        }

        $payment->setTransactionId($merchantReference);
        $trasactionData = $this->rawDetailsFormatter->format($result);
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $trasactionData);

        $payment->setShouldCloseParentTransaction(!$payment->getCreditmemo()->getInvoice()->canRefund());

        return $this;
    }


    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        $order = $payment->getOrder();
        $digestAmount = ($order->getBaseGrandTotal() - $order->getBaseTotalPaid()) * 100;
        $merchantReference = uniqid($order->getIncrementId() . '-void-');

        $baseUrl = $this->getApiUrl();
        $requestUrl = 'transactions/' . $order->getIncrementId() . '/void.xml';

        $digestString = $this->getConfigData("key", $order->getStoreId()) . $order->getIncrementId() . $digestAmount . $this->getConfigData("currency", $order->getStoreId());

        $digest = sha1($digestString);

        $xmlData = (string)$this->_generateXmlBody(
            $digestAmount,
            $this->getConfigData("currency", $order->getStoreId()),
            $digest, $this->getConfigData("auth_token"),
            $order->getIncrementId()
        );

        $client = $this->clientFactory->create(
            [
                'config' => [
                    'base_uri' => $baseUrl,
                    'timeout' => 5.0
                ]
            ]
        );

        $response = $client->request('POST', $requestUrl, [
            'body' => $xmlData,
            'headers' => [
                'Accept' => 'application/xml',
                'Content-Type' => 'application/xml'
            ]
        ]);


        if (!in_array($response->getStatusCode(), [200, 201])) {
            throw new LocalizedException(__('Error with Capture Action.'));
            return $this;
        }

        $result = (array)$this->_convertXmlToArray($response->getBody()->getContents());

        if (!isset($result['status']) || $result['status'] != 'approved') {
            throw new LocalizedException(__('Trasaction is not Approved!'));
            return $this;
        }

        $payment->setTransactionId($merchantReference);
        $trasactionData = $this->rawDetailsFormatter->format($result);
        $payment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS, $trasactionData);

        return $this;
    }

    protected function _generateXmlBody($amount, $currency, $digest, $authenticityToken, $orderNumber): string
    {
        $xml = new \SimpleXMLElement('<transaction/>');
        $xml->addChild('amount', $amount);
        $xml->addChild('currency', $currency);
        $xml->addChild('digest', $digest);
        $xml->addChild('authenticity-token', $authenticityToken);
        $xml->addChild('order-number', $orderNumber);

        return $xml->asXML();
    }

    protected function _convertXmlToArray($xmlString): array
    {
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $result = json_decode($json, TRUE);

        return $result;
    }
}
