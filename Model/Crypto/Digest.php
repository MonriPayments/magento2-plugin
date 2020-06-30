<?php

namespace Monri\Payments\Model\Crypto;

class Digest
{
    const DIGEST_ALGO_256 = 'sha512';

    const DIGEST_ALGO_1 = 'sha1';

    /**
     * Calculates the digest required for signing the request.
     *
     * Amount is given in the same exact format as sent to the gateway.
     *
     * @param $clientKey
     * @param string $orderNumber
     * @param string $currencyCode
     * @param int $amount
     * @param string $digestAlgo
     * @return string
     */
    public function build($clientKey, $orderNumber, $currencyCode, $amount, $digestAlgo = self::DIGEST_ALGO_256) {
        $data = "{$clientKey}{$orderNumber}{$amount}{$currencyCode}";

        return hash($digestAlgo, $data);
    }

    /**
     * Verifies a digest.
     *
     * @param $clientKey
     * @param $digest
     * @param $payload
     * @param string $digestAlgo
     * @return bool
     */
    public function verify($clientKey, $digest, $payload, $digestAlgo = self::DIGEST_ALGO_256)
    {
        $expectedPayload = "{$clientKey}{$payload}";

        $expectedDigest = hash($digestAlgo, $expectedPayload);

        return $expectedDigest === $digest;
    }
}
