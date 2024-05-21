<?php

namespace Monri\Payments\Gateway\Command\WSPay;

use Monri\Payments\Gateway\Request\WSPay\FormDataBuilder;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Model\Method\Logger;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Payment\Gateway\Command\ResultInterface;

class BuildFormDataCommand implements CommandInterface
{
    /**
     * @var FormDataBuilder
     */
    private $builder;

    /**
     * @var ArrayResultFactory
     */
    private $arrayResultFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param FormDataBuilder $builder
     * @param ArrayResultFactory $arrayResultFactory
     * @param Logger $logger
     */
    public function __construct(
        FormDataBuilder $builder,
        ArrayResultFactory $arrayResultFactory,
        Logger $logger
    ) {
        $this->builder = $builder;
        $this->arrayResultFactory = $arrayResultFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $commandSubject): ResultInterface
    {
        $paymentDO = SubjectReader::readPayment($commandSubject);

        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $result = $this->builder->build($commandSubject);

        $this->logger->debug(['form_data' => $result]);

        return $this->arrayResultFactory->create(['array' => $result]);
    }
}
