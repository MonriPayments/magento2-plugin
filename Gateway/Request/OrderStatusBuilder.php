<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Request;

use InvalidArgumentException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Payment;
use Monri\Payments\Gateway\Config;
use Monri\Payments\Helper\Formatter;
use Monri\Payments\Model\Crypto\Digest;

class OrderStatusBuilder implements BuilderInterface
{
    const ORDER_GROUP_FIELD = 'order';

    const ORDER_NUMBER_FIELD = 'order-number';

    const AUTHENTICITY_TOKEN_FIELD = 'authenticity-token';

    const DIGEST_FIELD = 'digest';

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Digest
     */
    private $digest;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Formatter $formatter,
        Digest $digest,
        Config $config
    ) {
        $this->formatter = $formatter;
        $this->digest = $digest;
        $this->config = $config;
    }

    /**
     * Builds the order capture request.
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        $orderNumber = $order->getIncrementId();
        $digest = $this->digest->buildSimple(
            $orderNumber,
            $order->getStoreId()
        );

        return [
            self::ORDER_GROUP_FIELD => [
                self::ORDER_NUMBER_FIELD => $orderNumber,
                self::AUTHENTICITY_TOKEN_FIELD => $authenticityToken,
                self::DIGEST_FIELD => $digest,
            ],
            '__store' => $order->getStoreId(),
        ];
    }
}
