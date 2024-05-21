<?php

namespace Monri\Payments\Gateway\Helper;

class TestModeHelper
{
    private const TEST_SUFFIX = '-test';

    /**
     * Generate a random test order id
     *
     * @param string $orderId
     * @return string
     */
    public static function generateTestOrderId(string $orderId): string
    {
        return $orderId . self::TEST_SUFFIX . time();
    }

    /**
     * Find the first occurrence of test order id
     *
     * @param string $testOrderId
     * @return string
     */
    public static function resolveRealOrderId(string $testOrderId): string
    {
        return strstr($testOrderId, self::TEST_SUFFIX, true);
    }
}
