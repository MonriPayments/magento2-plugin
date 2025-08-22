<?php

namespace Monri\Payments\Model\Method\Redirect;

use Magento\Payment\Model\MethodInterface;
use Monri\Payments\Block\Adminhtml\Config\Source\TransactionTypes;

class Vault extends \Magento\Vault\Model\Method\Vault
{
    /**
     * Vault doesn't need to inherit initialize from "parent" method
     *
     * @return true
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

    /**
     * @inheritDoc
     */
    public function getConfigPaymentAction()
    {
        $transactionType = $this->getConfigData('transaction_type');

        if ($transactionType === TransactionTypes::ACTION_PURCHASE) {
            return MethodInterface::ACTION_AUTHORIZE_CAPTURE;
        }
        return MethodInterface::ACTION_AUTHORIZE;
    }
}
