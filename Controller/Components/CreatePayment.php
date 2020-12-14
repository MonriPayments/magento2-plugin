<?php

declare(strict_types=1);

namespace Monri\Payments\Controller\Components;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Gateway\Config\Components as Config;

/**
 * Class CreatePayment
 */
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
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        //OrderRepository $orderRepository,
        Config $config,
        CommandManagerInterface $commandManager,
        Logger $logger
    ) {
        $this->commandManager = $commandManager;
        $this->checkoutSession = $checkoutSession;
        //$this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->logger = $logger;

        parent::__construct($context);
    }


    public function execute()
    {
        // save to session?
        // call CreatePaymentCommand

        //@todo: add no cache header !!

        $quote = $this->checkoutSession->getQuote();
        $quote->reserveOrderId();

        // monri payment needs to be set on quote!! is it always set? (/set-payment request from checkout)
        $payment = $quote->getPayment();

        //$payment->setAdditionalData('{"status":"approved","id":"6b3f83ff195e0cbade637f2dcb42b77195073dd0","client_secret":"6b3f83ff195e0cbade637f2dcb42b77195073dd0"}');

        // check payment data, if we have data for this quote, don't init again?

        $payload = false;
        if (!$payload) {
            // command needs to either set
            $this->commandManager->executeByCode('create_payment', $payment);
            $payload = $payment->getAdditionalInformation(\Monri\Payments\Gateway\Response\Components\PaymentCreateHandler::INITIAL_DATA);
        }

        // save reserved id and additional data
        $quote->save();

        //$payload = json_decode($additionalData);

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $resultJson->setData([
            'data' => [
                'client_secret' => $payload['client_secret'],
                'authenticity_token' => $this->config->getClientAuthenticityToken()
            ],
            'error' => null
        ]);

        return $resultJson;
    }

}