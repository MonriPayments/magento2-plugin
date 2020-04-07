<?php

namespace Leftor\PikPay\Controller\Standard;

class Cancel extends \Leftor\PikPay\Controller\PikPay
{

    public function execute()
    {
        $response = $this->getRequest()->getParams();

        if ($this->updateOrder($response["order_number"],'canceled','Narudžba je otkazana!')){
            echo "Narudzba je otkazana!";
            $redirectUrl = $this->getCheckoutHelper()->getUrl('checkout/onepage/failure');
            $this->getResponse()->setRedirect($redirectUrl);
        }
        else {
            echo "Doslo je do greske, narudzba nije azurirana! Molimo Vas da kontaktirate administratora...";
        }
    }
}