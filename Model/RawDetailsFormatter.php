<?php

namespace Leftor\PikPay\Model;

class RawDetailsFormatter
{
    /**
     * @param array $rawData
     * @return array
     */
    public function format(array $rawData): array
    {
        $flatArray = $rawData;
        foreach ($flatArray as $key => $item) {
            if (is_array($item)) {
                foreach ($item as $name => $value) {

                    if(is_array($value)){
                        continue;
                    }
                    $flatArray[$key . '[' . $name . ']'] = $value;
                }
                unset($flatArray[$key]);
            }
        }
        return $flatArray;
    }
}
