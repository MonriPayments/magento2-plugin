<?php

namespace Monri\Payments\Lock;

use Magento\Framework\Cache\FrontendInterface;
use Monri\Payments\Lock\Order\LockInterface;

class Order implements LockInterface
{
    /**
     * @var FrontendInterface
     */
    private $cache;

    /**
     * Cache lock prefix
     */
    private const IDENTIFIER_PREFIX = 'MONRI_ORDER_LOCK_';

    /**
     * Cache timeout
     */
    private const LOCK_TIMEOUT = 3600;

    /**
     * Order Locker constructor.
     *
     * @param FrontendInterface $cache
     */
    public function __construct(FrontendInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Lock a given order
     *
     * @param string|int $orderId
     * @return bool
     */
    public function lock($orderId)
    {
        return $this->cache->save('1', $this->getIdentifier($orderId), [], self::LOCK_TIMEOUT);
    }

    /**
     * Unlock a given order
     *
     * @param string|int $orderId
     * @return bool
     */
    public function unlock($orderId)
    {
        return $this->cache->remove($this->getIdentifier($orderId));
    }

    /**
     * Check if a given order is locked
     *
     * @param string|int $orderId
     * @return bool
     */
    public function isLocked($orderId)
    {
        return (bool) $this->cache->test($this->getIdentifier($orderId));
    }

    /**
     * Get identifier for a given order ID
     *
     * @param string $orderId
     * @return string
     */
    private function getIdentifier($orderId)
    {
        return self::IDENTIFIER_PREFIX . $orderId;
    }
}
