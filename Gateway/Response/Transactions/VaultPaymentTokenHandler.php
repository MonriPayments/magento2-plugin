<?php

namespace Monri\Payments\Gateway\Response\Transactions;

use Magento\Framework\Api\SearchCriteriaBuilder;
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
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * VaultPaymentTokenHandler constructor.
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param Json $json
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @param CcTypeMapper $ccTypeMapper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Json $json,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository,
        CcTypeMapper $ccTypeMapper,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->paymentTokenFactory     = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->json                    = $json;
        $this->paymentTokenRepository  = $paymentTokenRepository;
        $this->orderPaymentRepository  = $orderPaymentRepository;
        $this->ccTypeMapper            = $ccTypeMapper;
        $this->searchCriteriaBuilder   = $searchCriteriaBuilder;
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
        $responseCcExpDate = $response['expiration_date'] ?? '';

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('gateway_token', $response['pan_token'])
            ->create();

        $searchResult = $this->paymentTokenRepository->getList($searchCriteria);
        $items = $searchResult->getItems();
        $existingToken = !empty($items) ? $items[array_key_first($items)] : null;

        //callback came after success redirect. we update expiration date of already saved token
        if ($responseCcExpDate && $existingToken) {
            $expDate = $this->getExpirationDate($responseCcExpDate);
            $existingToken->setExpiresAt($expDate);

            $details = $this->json->unserialize($existingToken->getTokenDetails());
            $details['expirationDate'] = $responseCcExpDate;
            $existingToken->setTokenDetails($this->json->serialize($details));

            $this->paymentTokenRepository->save($existingToken);
            return;
        }
        //user refreshed the success page. return early
        if ($existingToken) {
            return;
        }

        //continue creating new token from success redirect or callback
        $expDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $expDate = $expDate->add(new \DateInterval('P1Y'));
        //update expiration date if it exists
        if ($responseCcExpDate) {
            $expDate = $this->getExpirationDate($responseCcExpDate);
        }

        $paymentDO = SubjectReader::readPayment($handlingSubject);
        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();

        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);

        $paymentToken
            ->setGatewayToken($response['pan_token'])
            ->setExpiresAt($expDate);

        $ccType = $response['cc_type'] ?? ($response['ch_full_name'] ?? '');

        $paymentToken->setTokenDetails($this->json->serialize([
            'type'           => $this->ccTypeMapper->getCcTypeId($ccType),
            'maskedCC'       => $this->getLast4($response['masked_pan']),
            'expirationDate' => $responseCcExpDate
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
}
