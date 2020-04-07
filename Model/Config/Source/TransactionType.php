<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Leftor\PikPay\Model\Config\Source;

class TransactionType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => "purchase", 'label' => __('Purchase')],
            ['value' => "authorize", 'label' => __('Authorize')]
        ];
    }
}