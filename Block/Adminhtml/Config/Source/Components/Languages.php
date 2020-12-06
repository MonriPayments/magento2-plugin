<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Block\Adminhtml\Config\Source\Components;

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
            ['value' => 'hr', 'label' => __('Croatian')],
            ['value' => 'en', 'label' => __('English')],
            ['value' => 'sr', 'label' => __('Serbian')],
        ];
    }
}
