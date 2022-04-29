<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Block\Adminhtml\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TransactionTypes implements OptionSourceInterface
{
    public const ACTION_AUTORIZE = 'authorize';
    public const ACTION_PURCHASE = 'purchase';

    /**
     * Return array of transaction types for redirect gateway.
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ACTION_PURCHASE, 'label' => __('Purchase')],
            ['value' => self::ACTION_AUTORIZE, 'label' => __('Authorize Only')],
        ];
    }
}
