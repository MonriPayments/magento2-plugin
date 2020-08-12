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

class Installments implements OptionSourceInterface
{
    const MAX_INSTALLMENTS = 12;

    const MIN_INSTALLMENTS = 2;

    /**
     * Return array of options for installments.
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $installments = [
            ['value' => 'disabled', 'label' => __('No installments')]
        ];

        for ($i = self::MIN_INSTALLMENTS; $i <= self::MAX_INSTALLMENTS; $i++) {
            $installments[] = [
                'value' => "{$i}",
                'label' => $i,
            ];
        }

        return $installments;
    }
}
