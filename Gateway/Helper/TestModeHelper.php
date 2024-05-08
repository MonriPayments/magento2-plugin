<?php

namespace Monri\Payments\Gateway\Helper;

class TestModeHelper
{
    private const TEST_SUFFIX = '-test';

    /**
     * @param string $orderId
     * @return string
     */
    public static function generateTestOrderId(string $orderId): string
    {
        return $orderId . self::TEST_SUFFIX . time();
    }

    /**
     * @param string $testOrderId
     * @return string
     */
    public static function resolveRealOrderId(string $testOrderId): string
    {
        return strstr($testOrderId, self::TEST_SUFFIX, true);
    }
}
