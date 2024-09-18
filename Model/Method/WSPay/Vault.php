<?php

namespace Monri\Payments\Model\Method\WSPay;

class Vault extends \Magento\Vault\Model\Method\Vault
{
    /**
     * Vault doesn't need to inherit initialize from "parent" method
     *
     * @return false
     */
    public function isInitializeNeeded()
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canAuthorize()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function canCapture()
    {
        return true;
    }
}
