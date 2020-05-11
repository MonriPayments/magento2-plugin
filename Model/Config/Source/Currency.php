<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Leftor\PikPay\Model\Config\Source;

class Currency implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => "BAM", 'label' => __('Bosnia-Herzegovina Convertible Mark')],
            ['value' => "HRK", 'label' => __('Croatian Kuna')],
            ['value' => "EUR", 'label' => __('Euro')],
            ['value' => "USD", 'label' => __('US Dollar')]
        ];
    }
}