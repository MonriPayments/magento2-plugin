<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Leftor\PikPay\Model\Config\Source;

class ConfigLanguage implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => "ba", 'label' => __('Bosnian')],
            ['value' => "hr", 'label' => __('Croatian')],
            ['value' => "en", 'label' => __('English')],
            ['value' => "es", 'label' => __('Espanol')]
        ];
    }
}