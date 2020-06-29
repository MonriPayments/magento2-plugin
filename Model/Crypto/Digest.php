<?php

namespace Monri\Payments\Model\Crypto;

class Digest
{
    const DIGEST_ALGO = 'sha512';

    /**
     * Calculates the digest required for signing the request.
     *
     * Amount is given in the same exact format as sent to the gateway.
     *
     * @param $clientKey
     * @param string $orderNumber
     * @param string $currencyCode
     * @param int $amount
     * @return string
     */
    public function build($clientKey, $orderNumber, $currencyCode, $amount) {
        $data = "{$clientKey}{$orderNumber}{$amount}{$currencyCode}";

        return hash(self::DIGEST_ALGO, $data);
    }

    /**
     * Verifies a digest.
     *
     * @param $clientKey
     * @param $digest
     * @param $payload
     * @return bool
     */
    public function verify($clientKey, $digest, $payload)
    {
        $expectedPayload = "{$clientKey}{$payload}";

        $expectedDigest = hash(self::DIGEST_ALGO, $expectedPayload);

        return $expectedDigest === $digest;
    }
}
