<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Helper;

class RawDetailsFormatter
{
    /**
     * Format data, flatten multi array to simple one with string keys
     *
     * @param array $rawData
     * @return array
     */
    public function format(array $rawData): array
    {
        $flatArray = $rawData;
        foreach ($flatArray as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            if (empty($item)) {
                $flatArray[$key] = '[]';
                continue;
            }

            foreach ($item as $name => $value) {
                $flatArray[$key . '[' . $name . ']'] = $value;
            }
            unset($flatArray[$key]);
        }
        return $flatArray;
    }
}
