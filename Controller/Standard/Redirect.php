<?php

namespace Leftor\PikPay\Controller\Standard;


class Redirect extends \Leftor\PikPay\Controller\PikPay
{
    public function execute()
    {
        //print_r($this->getPaymentMethod()->getPaymentType()); die;

        if($this->getPaymentMethod()->getPaymentType() == 3)
        {
            $response = $this->getRequest()->getParams();
            $quote = $this->getQuote();
            $order = $this->getOrder();
            $params = $this->getPaymentMethod()->checkoutRequest($quote,$order);

            if(!empty($response))
            {
                $ccNumber = $response['pan'];
                $creditCard = $this->getCcHelper()->validCreditCard($ccNumber);
                if(isset($response['year']) && isset($response['month']))
                {
                    $validDate = $this->getCcHelper()->validDate('20'.$response['year'],$response['month']);
                }
                $validCvc = $this->getCcHelper()->validCvc($response['cvv'],$creditCard['type']);
                if(!$creditCard['valid'] || !$validDate || !$validCvc)
                {
                    if(!$creditCard['valid'])
                    {
                        $params['valid_cc'] = 0;
                    }
                    elseif(!$validDate)
                    {
                        $params['valid_date'] = 0;
                    } 
                    elseif (!$validCvc)
                    {
                        $params['valid_cvc'] = 0;
                    }

                    $this->_coreRegistry->register('param',$params);
                    return $this->resultPageFactory->create();
                }
                $response['expiration_date'] = $response['year'].$response['month'];
                unset($response['year']);
                unset($response['month']);

                if(array_key_exists('number_of_installments',$response))
                {
                    if ($response['number_of_installments'] != '')
                    {
                        $response['amount'] = intval($response['amount'] * $this->getOrderPercentageForInstallments());
                        $response['digest'] = $this->_paymentMethod->digest(
                            $this->_paymentMethod->getKey(),
                            $response['order_number'],
                            $response['amount'],
                            $response['currency']
                        );
                    }
                    else
                    {
                        $response['amount'] = intval($response['amount'] * $this->getOrderPercentage());
                        $response['digest'] = $this->_paymentMethod->digest(
                            $this->_paymentMethod->getKey(),
                            $response['order_number'],
                            $response['amount'],
                            $response['currency']
                        );
                    }
                }
                else
                {
                    $response['amount'] = intval($response['amount'] * $this->getOrderPercentage());
                    $response['digest'] = $this->_paymentMethod->digest(
                        $this->_paymentMethod->getKey(),
                        $response['order_number'],
                        $response['amount'],
                        $response['currency']
                    );
                }

                $result = $this->getPaymentMethod()->directCheckoutRequest($response);

                $responseValues = [];
                foreach ($result as $key => $value){
                    $responseValues[$key] = $value;
                }
                if(isset($responseValues['acs_url']) && $responseValues['acs_url']!=null)
                {
                    $url = $this->_url->getBaseUrl() . 'pikpay/standard/verification';
                    $redirect = $this->secureVerification($responseValues['acs_url'],$responseValues['pareq'],$url,$responseValues['authenticity_token']);
                    echo $redirect;
                    die();
                }
                elseif(array_key_exists('status', $responseValues) && $responseValues['status'] !== '')
                {
                    $comment = "Narudzba je placena! PikPay approval code: " . $responseValues["approval_code"];
                    $this->updateOrder($order->getRealOrderId(), 'success', $comment);
                    $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/success');
                    $this->getResponse()->setRedirect($redirectUrl);
                     
                }
                else
                {
                    if(array_key_exists('error', $responseValues)) {
                        $error = $responseValues["error"];
                    } else {
                        $error = "none";
                    }

                    $this->updateOrder($order->getRealOrderId(),'canceled','Narudžba je otkazana! '.'Error: '.$error);
                    $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/failure');
                    $this->messageManager->addErrorMessage(__('Narudžba otkazana. Plaćanje nije uspjelo, molimo pokušajte ponovo.'));
                    $this->getResponse()->setRedirect($redirectUrl);
                }
            }
            else
            {
                $this->_coreRegistry->register('param',$params);
                $comment = "Unos podataka kreditne kartice... obrada u toku!";
                $order->addStatusHistoryComment($comment)->save();

                return $this->resultPageFactory->create();
            }
        }
        else 
        {
            $quote = $this->getQuote();
            $order = $this->getOrder();
            $params = $this->getPaymentMethod()->checkoutRequest($quote,$order);
            $this->_coreRegistry->register('param',$params);

            $comment = "Narudzba preusmjerena na PikPay... obrada u toku!";
            $order->addStatusHistoryComment($comment)->save();

            return $this->resultPageFactory->create();
        }
    }
}