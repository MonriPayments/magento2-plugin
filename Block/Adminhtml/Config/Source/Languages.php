<?php

namespace Monri\Payments\Block\Adminhtml\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Languages implements OptionSourceInterface
{
    /**
     * Return array of languages for gateway.
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'en', 'label' => __('English')],
            ['value' => 'es', 'label' => __('Spanish')],
            ['value' => 'ba', 'label' => __('Bosnian')],
            ['value' => 'hr', 'label' => __('Croatian')],
        ];
    }
}
