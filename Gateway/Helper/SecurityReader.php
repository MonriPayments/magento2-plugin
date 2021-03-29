<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Helper;

use InvalidArgumentException;

class SecurityReader
{
    /**
     * Read verification data.
     *
     * @param array $subject
     * @return array
     */
    // phpcs:ignore Magento2.Functions.StaticFunction
    public static function readVerificationData(array $subject)
    {
        if (!isset($subject['verification_data']) || !is_array($subject['verification_data'])) {
            throw new InvalidArgumentException('Verification digest should be provided.');
        }

        return $subject['verification_data'];
    }
}
