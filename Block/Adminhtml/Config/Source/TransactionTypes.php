<?php

/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

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
