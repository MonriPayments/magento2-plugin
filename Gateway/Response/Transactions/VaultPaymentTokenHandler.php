<?php

namespace Monri\Payments\Gateway\Response\Transactions;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Monri\Payments\Gateway\Helper\CcTypeMapper;

class VaultPaymentTokenHandler implements HandlerInterface
{
    private const PAYMENT_METHOD_CODE = 'monri_payments';
    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    private $paymentExtensionFactory;

    /**
     * @var Json
     */
    private $json;
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;
    /**
     * @var OrderPaymentRepositoryInterface
     */
    private $orderPaymentRepository;
    /**
     * @var CcTypeMapper
     */
    private $ccTypeMapper;

    /**
     * VaultPaymentTokenHandler constructor.
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param Json $json
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param CcTypeMapper $ccTypeMapper
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Json $json,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        CcTypeMapper $ccTypeMapper
    ) {
        $this->paymentTokenFactory     = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->json                    = $json;
        $this->paymentTokenRepository  = $paymentTokenRepository;
        $this->orderPaymentRepository  = $orderPaymentRepository;
        $this->ccTypeMapper            = $ccTypeMapper;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response): void
    {
        // @todo: check if vault enabled?

        if (! isset($response['cc_type'], $response['pan_token'], $response['masked_pan'])) {
            return;
        }

        $paymentDO = SubjectReader::readPayment($handlingSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        // Monri does not include cc expiration date in response
        $expDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $expDate->add(new \DateInterval('P1Y'))
                ->format('Y-m-d 00:00:00');

        $paymentToken
            ->setGatewayToken($response['pan_token'])
            ->setExpiresAt($expDate);

        $ccType = $response['cc_type'] ?? ($response['ch_full_name'] ?? '');

        $paymentToken->setTokenDetails($this->json->serialize([
            'type'           => $this->ccTypeMapper->getCcTypeId($ccType),
            'maskedCC'       => $this->getLast4($response['masked_pan']),
            'expirationDate' => $expDate->format('dm')
        ]));

        $extensionAttributes = $this->getExtensionAttributes($payment);
        $extensionAttributes->setVaultPaymentToken($paymentToken);
    }

    /**
     * Gets payment extension attributes.
     *
     * @param OrderPaymentInterface $payment
     *
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(OrderPaymentInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * Get the last 4 digits of masked pan
     *
     * @param string $maskedPan
     *
     * @return string
     */
    private function getLast4($maskedPan)
    {
        return (strlen($maskedPan) >= 4) ? substr($maskedPan, -4) : '';
    }
}
