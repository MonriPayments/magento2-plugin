<?php

namespace Monri\Payments\Block\Adminhtml\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TransactionTypes implements OptionSourceInterface
{

    /**
     * Return array of transaction types for redirect gateway.
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'purchase', 'label' => __('Purchase')],
            ['value' => 'authorize', 'label' => __('Authorize Only')],
        ];
    }
}
