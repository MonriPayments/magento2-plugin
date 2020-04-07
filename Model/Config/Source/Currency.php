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
            ['value' => "BAM", 'label' => __('BAM')],
            ['value' => "HRK", 'label' => __('HRK')],
            ['value' => "EUR", 'label' => __('EUR')],
            ['value' => "USD", 'label' => __('USD')]
        ];
    }
}