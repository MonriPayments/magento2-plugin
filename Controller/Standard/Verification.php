<?php

namespace Leftor\PikPay\Controller\Standard;

class Verification extends \Leftor\PikPay\Controller\PikPay
{

    public function execute()
    {
        $response = $this->getRequest()->getParams();

        $xmlData = '<?xml version="1.0" encoding="UTF-8"?>
			<secure-message>';
        foreach ($response as $xmlvar => $value)
        {
            $xmlvar = str_replace('_', '-', $xmlvar);
            if(trim($value)!='')
                $xmlData .= '<'.$xmlvar.'>'.trim($value).'</'.$xmlvar.'>';
        }
        $xmlData .= '</secure-message>';

        $testing = $this->_paymentMethod->getConfigData('test_mode');
        $procesor = $this->_paymentMethod->getConfigData('procesor');

        switch ($testing)
        {
            case 1:
                $url = 'https://ipgtest.'.$procesor.'/pares';
                break;
            case 0:
                $url = 'https://ipg.'.$procesor.'/pares';
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

        if(curl_errno($ch))
        {
            $this->_logger->info('cURL Error: '.curl_error($ch));
            die(curl_error($ch));
        }
        curl_close($ch);

        $resultXml = false;
        if ($this->_paymentMethod->isValidXML($result))
        {
            $resultXml = new \SimpleXmlElement($result);
        }
        $resultXml = (array) $resultXml;

        foreach ($resultXml as $resKey => $resValue)
        {
            $resultRequest[str_replace('-', '_', $resKey)] = $resValue;
        }

        if($resultRequest['status']=='' || !isset($resultRequest['status']))
        {
            $this->_logger->info('Status ERROR: '.$resultRequest);
            $this->_checkoutHelper->cancelCurrentOrder(__('Order is cancelled... Response code: %1', $resultRequest['response_code']));
            $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/failure');
            $this->getResponse()->setRedirect($redirectUrl);
        }
        $comment = __('Order is paid! Monri approval code: %1', $resultRequest["approval_code"]);
        $this->updateOrder($this->getOrder()->getRealOrderId(),'success',$comment);

        $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
        $this->getResponse()->setRedirect($redirectUrl);
    }
}
