<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Model\Crypto;

use Monri\Payments\Gateway\Config;

class Digest
{
    const DIGEST_ALGO_256 = 'sha512';

    const DIGEST_ALGO_1 = 'sha1';

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Calculates the digest required for signing the request.
     *
     * Amount is given in the same exact format as sent to the gateway.
     *
     * @param string $orderNumber
     * @param string $currencyCode
     * @param int $amount
     * @param null|int $storeId
     * @param string $digestAlgo
     * @return string
     */
    public function build($orderNumber, $currencyCode, $amount, $storeId = null, $digestAlgo = self::DIGEST_ALGO_256)
    {
        $clientKey = $this->config->getClientKey($storeId);
        $data = "{$clientKey}{$orderNumber}{$amount}{$currencyCode}";

        return hash($digestAlgo, $data);
    }

    /**
     * Verifies a digest.
     *
     * @param $digest
     * @param $payload
     * @param null|int $storeId
     * @param string $digestAlgo
     * @return bool
     */
    public function verify($digest, $payload, $storeId = null, $digestAlgo = self::DIGEST_ALGO_256)
    {
        $clientKey = $this->config->getClientKey($storeId);
        $expectedPayload = "{$clientKey}{$payload}";

        $expectedDigest = hash($digestAlgo, $expectedPayload);

        return $expectedDigest === $digest;
    }
}
