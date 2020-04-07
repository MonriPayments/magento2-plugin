<?php
namespace Leftor\PikPay\Model\Config\Source;

class Cctype extends \Magento\Payment\Model\Source\Cctype{

    public function getAllowedTypes()
    {
        return array('VI', 'MC', 'AE', 'MI', 'OT');
    }
}