<?php

namespace Monri\Payments\Lock\Order;

interface LockInterface
{
    /**
     * Lock a given order
     *
     * @param string|int $orderId
     * @return bool
     */
    public function lock($orderId);

    /**
     * Unlock a given order
     *
     * @param string|int $orderId
     * @return bool
     */
    public function unlock($orderId);

    /**
     * Check if a given order is locked
     *
     * @param string|int $orderId
     * @return bool
     */
    public function isLocked($orderId);
}
