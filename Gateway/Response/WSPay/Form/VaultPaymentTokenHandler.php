<?php

namespace Monri\Payments\Gateway\Response\WSPay\Form;

use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Monri\Payments\Gateway\Helper\CcTypeMapper;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;

class VaultPaymentTokenHandler implements HandlerInterface
{
    private const PAYMENT_METHOD_CODE = 'monri_wspay';
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
     * VaultPaymentTokenHandler constructor.
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param Json $json
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Json $json,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->json = $json;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    /**
     * @inheritDoc
     */
    public function handle(array $handlingSubject, array $response): void
    {
        // @todo: check if vault enabled?

        if (!isset($response['Token'], $response['TokenNumber'], $response['TokenExp'])) {
            return;
        }

        $paymentDO = SubjectReader::readPayment($handlingSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

        $paymentToken
            ->setGatewayToken($response['Token'])
            ->setExpiresAt($this->getExpirationDate($response['TokenExp']));

        $ccType = $response['PaymentType'] ?? ($response['CreditCardName'] ?? '');

        $paymentToken->setTokenDetails($this->json->serialize([
            'type' => CcTypeMapper::getCcTypeId($ccType),
            'maskedCC' => $response['TokenNumber'],
            'expirationDate' => $response['TokenExp']
        ]));

        $extensionAttributes = $this->getExtensionAttributes($payment);
        $extensionAttributes->setVaultPaymentToken($paymentToken);
    }

    /**
     * Resolve UTC expiration date
     *
     * @param string $yearMonth
     * @return string
     * @throws \Exception
     */
    private function getExpirationDate(string $yearMonth): string
    {
        $year = substr($yearMonth, 0, 2);
        $month = substr($yearMonth, 2, 2);

        $expDate = new \DateTime("$year-$month-01 00:00:00", new \DateTimeZone('UTC'));
        $expDate->add(new \DateInterval('P1M'));

        return $expDate->format('Y-m-d 00:00:00');
    }

    /**
     * Gets payment extension attributes.
     *
     * @param OrderPaymentInterface $payment
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
}
