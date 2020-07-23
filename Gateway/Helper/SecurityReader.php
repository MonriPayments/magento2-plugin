<?php

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
    public static function readVerificationData(array $subject)
    {
        if (!isset($subject['verification_data']) || !is_array($subject['verification_data'])) {
            throw new InvalidArgumentException('Verification digest should be provided.');
        }

        return $subject['verification_data'];
    }
}
