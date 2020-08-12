<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Helper;

class Formatter
{
    /**
     * Formats the price according to Monri's specification
     *
     * @param $price
     * @return string
     */
    public function formatPrice($price)
    {
        return (int)round($price * 100);
    }

    /**
     * Formats text according to Monri's specification.
     *
     * @param string $text
     * @param int $maxLength
     * @return string
     */
    public function formatText($text, $maxLength = 30)
    {
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength);
        }

        return $text;
    }
}
