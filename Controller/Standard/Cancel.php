<?php

namespace Leftor\PikPay\Controller\Standard;

class Cancel extends \Leftor\PikPay\Controller\PikPay
{

    public function execute()
    {
        $response = $this->getRequest()->getParams();

        $message = __('Order is cancelled!');

        if ($this->updateOrder($response["order_number"],'canceled',$message)){
            echo $message;
            $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/failure');
            $this->getResponse()->setRedirect($redirectUrl);
        }
        else {
            echo __('Error occurred, order was not updated! Please contact your administrator...');
        }
    }
}
