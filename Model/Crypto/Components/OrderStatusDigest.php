<?php

namespace Monri\Payments\Model\Crypto\Components;

use Monri\Payments\Gateway\Config;

class OrderStatusDigest
{
    public function __construct(
        private Config $config
    ){}

    /**
     * Build digest
     *
     * @param int|null $storeId
     * @return string
     */
    public function build(string $order_number, $storeId = null)
    {
        $key = $this->config->getClientKey($storeId);
        $data = $key . $order_number;

        return hash('SHA1', $data);
    }
}
