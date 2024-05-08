<?php

namespace Monri\Payments\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Language implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' =>'HR', 'label' => __('Croatian')],
            ['value' =>'SR', 'label' => __('Serbian')],
            ['value' =>'SL', 'label' => __('Slovenian')],
            ['value' =>'BS', 'label' => __('Bosnian')],
            ['value' =>'CG', 'label' => __('Montenegro')],
            ['value' =>'EN', 'label' => __('English')],
            ['value' =>'DE', 'label' => __('German')],
            ['value' =>'IT', 'label' => __('Italian')],
            ['value' =>'FR', 'label' => __('French')],
            ['value' =>'NL', 'label' => __('Dutch')],
            ['value' =>'HU', 'label' => __('Hungarian')],
            ['value' =>'RU', 'label' => __('Russian')],
            ['value' =>'SK', 'label' => __('Slovak')],
            ['value' =>'CZ', 'label' => __('Czech')],
            ['value' =>'PL', 'label' => __('Polish')],
            ['value' =>'PT', 'label' => __('Portuguese')],
            ['value' =>'ES', 'label' => __('Spanish')]
        ];
    }
}
