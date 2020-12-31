<?php

declare(strict_types=1);

namespace Monri\Payments\Controller\Components;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Gateway\Config\Components as Config;
use Monri\Payments\Gateway\Response\Components\PaymentCreateHandler;
use Magento\Quote\Model\Quote\Payment;

class CreatePayment extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var OrderRepository
     */
    //private $orderRepository;

    /**
     * @var CommandManagerInterface
     */
    private $commandManager;

    /**
     * @var Config
     */
    //private $config;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        //OrderRepository $orderRepository,
        //Config $config,
        CommandManagerInterface $commandManager,
        Logger $logger
    ) {
        $this->commandManager = $commandManager;
        $this->checkoutSession = $checkoutSession;
        //$this->orderRepository = $orderRepository;
        //$this->config = $config;
        $this->logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        $quote->reserveOrderId();
        $ttl = (int)$this->getRequest()->getParam('ttl', false);
        if (!$ttl) {
            return;
        }

        if (!$this->_isValidTransaction($quote->getPayment(), $ttl)) {
            $dataObject = new \Magento\Framework\DataObject();
            $dataObject->setData('ttl', $ttl);
            
            $this->commandManager->executeByCode(
                'create_payment',
                $quote->getPayment(),
                ['stateObject' => $dataObject]
            );
        }

        $payload = $quote->getPayment()->getAdditionalInformation(PaymentCreateHandler::INITIAL_DATA);

        // save reserved id and additional data
        $quote->save();

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $resultJson->setData([
            'data' => [
                'client_secret' => $payload['client_secret'],
                //'authenticity_token' => $this->config->getClientAuthenticityToken()
            ],
            'error' => null
        ]);

        return $resultJson;
    }

    /**
     * @param Payment $payment
     * @param $ttl
     * @return bool
     */
    protected function _isValidTransaction(Payment $payment, $ttl) : bool
    {
        $initialData = $payment->getAdditionalInformation(PaymentCreateHandler::INITIAL_DATA);
        if ($initialData === null) {
            return false;
        }

        if ($payment->getQuote()->getBaseGrandTotal() != $initialData['base_grand_total']) {
            return false;
        }

        $oldTtl = $payment->getAdditionalInformation(PaymentCreateHandler::TIME_LIMIT_TTL) + Config::TRANSACTION_TTL;

        if ($oldTtl < $ttl) {
            return false;
        }

        return true;
    }
}
