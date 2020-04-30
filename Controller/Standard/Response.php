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
                $comment = "Narudzba je placena! PikPay approval code: ".$response["approval_code"];
               if ($this->updateOrder($response["order_number"],'success',$comment)){
                   $this->makeInvoice($response["order_number"],$response);
                   echo "Narudzba je uspjesno procesirana!";
                   $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
                   $this->getResponse()->setRedirect($redirectUrl);
               }
                else {
                    echo "Doslo je do greske, narudzba nije azurirana! Molimo Vas da kontaktirate administratora... Broj narudzbe je: ".$response["order_number"];
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
                echo "INVALID DIGEST!";
            }
        }
        else {
            $response_code = $this->getResponseCode($response["response_code"]);
            $comment = "Narudzba nije placena, PikPay response: ".$response_code;
            $this->updateOrder($response["order_number"],'fail',$comment);
            echo "Placanje nije uspjelo! Poruka: ".$response_code;
        }
    }
}