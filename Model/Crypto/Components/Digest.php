<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Model\Crypto\Components;

use Monri\Payments\Gateway\Config\Components as Config;

class Digest
{
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
     * @param string $timestamp
     * @param string $bodyAsString
     * @param null $storeId
     * @return string
     */
    public function build(string $timestamp, string $bodyAsString, $storeId = null)
    {
        $clientKey = $this->config->getClientKey($storeId);
        $clientAuthenticityToken = $this->config->getClientAuthenticityToken($storeId);

        $data = "{$clientKey}{$timestamp}{$clientAuthenticityToken}{$bodyAsString}";

        return hash('sha512', $data);
    }
}
