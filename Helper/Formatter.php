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
     * @param float $price
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
     * @param bool $transliterate
     * @return string
     */
    public function formatText($text, $maxLength = 30, $transliterate = false)
    {
        /**
         * older ICU versions don't have Latin-ASCII,
         * so we're trying from best to worse
         */
        if ($transliterate) {
            $trans = [
                'Any-Latin; Latin-ASCII;',
                'Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC;',
                'Any-Latin;'
            ];
            foreach ($trans as $t) {
                // Find one that exists
                $transliterate = \Transliterator::create($t);
                if ($transliterate == null) {
                    continue;
                }

                // Transliterate and get out
                $text = $transliterate->transliterate($text);
                break;
            }
        }

        if (mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }

        return $text;
    }
}
