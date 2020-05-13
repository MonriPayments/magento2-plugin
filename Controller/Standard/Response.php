<?php

namespace Leftor\PikPay\Controller\Standard;

class Response extends \Leftor\PikPay\Controller\PikPay
{

    public function execute()
    {
        $response = $this->getRequest()->getParams();

        //$actualLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $actualLink = $this->_url->getCurrentUrl();

        $urlString = strstr($actualLink, '&digest', true);
        $digest = $this->getResponseDigestV2($urlString);

        if($response["response_code"] == "0000") {
            if ($this->getResponseDigest($response["order_number"]) == $response["digest"]) {
                $comment = __('Order is paid! Monri approval code: %1', $response["approval_code"]);
               if ($this->updateOrder($response["order_number"],'success',$comment)){
                   $this->makeInvoice($response["order_number"],$response);
                   echo __('Order is successfully processed.');
                   $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
                   $this->getResponse()->setRedirect($redirectUrl);
               }
                else {
                    echo __('Error occurred, order not updated! Please contact your administrator... Order number: %1', $response["order_number"]);
                }

            } elseif ($digest == $response["digest"]) {

                $order = $this->getOrderById($response["order_number"]);
                $methodInstance = $order->getPayment()->getMethodInstance();
                $methodInstance->handleResponse($response, $order);
                $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
                $this->getResponse()->setRedirect($redirectUrl);


                /*if ($this->updateOrder($response["order_number"],'success',$comment)){
                   $this->makeInvoice($response["order_number"],$response);
                   echo "Narudzba je uspjesno procesirana!";
                   $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
                   $this->getResponse()->setRedirect($redirectUrl);
               }
                else {
                    echo "Doslo je do greske, narudzba nije azurirana! Molimo Vas da kontaktirate administratora... Broj narudzbe je: ".$response["order_number"];
                }*/

            }
             else {
                echo __("INVALID DIGEST!");
            }
        }
        else {
            $response_code = $this->getResponseCode($response["response_code"]);
            $comment = __('Order not paid, Monri response: %1', $response_code);
            $this->updateOrder($response["order_number"],'fail',$comment);
            echo __('Payment unsuccessful! Message: %1', $response_code);
        }
    }
}
