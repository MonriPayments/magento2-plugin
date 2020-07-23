<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Sales\Model\Order\Payment;
use Monri\Payments\Gateway\Config;
use Monri\Payments\Gateway\Helper\SecurityReader;
use Monri\Payments\Model\Crypto\Digest;

class DigestValidator extends AbstractValidator
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Digest
     */
    private $digest;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Config $config,
        Digest $digest
    ) {
        parent::__construct($resultFactory);

        $this->config = $config;
        $this->digest = $digest;
    }

    /**
     * Performs domain-related validation for business object
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $verificationData = SecurityReader::readVerificationData($validationSubject);

        if (isset($verificationData['disabled']) && $verificationData['disabled'] === true) {
            return $this->createResult(true);
        }

        if (!isset($verificationData['digest']) || !isset($verificationData['digest_data'])) {
            return $this->createResult(false, [__('Request has an invalid signature.')]);
        }

        $paymentDO = SubjectReader::readPayment($validationSubject);

        /** @var Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();

        $verificationDigest = $verificationData['digest'];
        $verificationDigestData = $verificationData['digest_data'];

        $result = $this->digest->verify($verificationDigest, $verificationDigestData, $order->getStoreId());

        if ($result === true) {
            return $this->createResult(true);
        }

        return $this->createResult(false, [__('Request has an invalid signature.')]);
    }
}
