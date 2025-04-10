<?php

namespace Monri\Payments\Block\Adminhtml\Config\Source\Redirect;

use Magento\Framework\Data\OptionSourceInterface;

class SupportedPaymentMethodsAction implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'keks-pay-hr', 'label' => __('KEKS pay')],
            ['value' => 'pay-cek', 'label' => __('PayCek')],
        ];
    }
}
