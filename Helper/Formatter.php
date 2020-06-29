<?php


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
        return (int)round($price * 10);
    }

    public function formatText($text, $maxLength = 30, $stripNonAlphanumeric = true) {
//        if ($stripNonAlphanumeric === true) {
//            $text = preg_replace('/[^\u010D\u0107\u0161\u0111\u0173a-z\d ]/i', '', $text);
//        }

        if (strlen($text) > $maxLength) {
            //TODO: Consider doing wordwrap instead?
            $text = substr($text, 0, $maxLength);
        }

        return $text;
    }
}
