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
use Monri\Payments\Gateway\Response\Components\PaymentCreateHandler;

class CreatePayment extends Action
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var CommandManagerInterface
     */
    private $commandManager;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * CreatePayment constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param CommandManagerInterface $commandManager
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        CommandManagerInterface $commandManager,
        Logger $logger
    ) {
        $this->commandManager = $commandManager;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * Init Components payment
     *
     * @return Json
     */
    public function execute()
    {
        $log = [
            'location' => __METHOD__,
            'errors' => [],
            'success' => true,
            'payload' => []
        ];

        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $quote = $this->checkoutSession->getQuote();
            $quote->reserveOrderId();

            $this->commandManager->executeByCode(
                'create_payment',
                $quote->getPayment()
            );

            $payload = $quote->getPayment()->getAdditionalInformation(PaymentCreateHandler::INITIAL_DATA);

            $quote->save(); // TODO: repository alternative

            $response = [
                'data' => [
                    'client_secret' => $payload['client_secret'],
                ],
                'error' => null
            ];

            $resultJson->setData($response);

            $log['payload'] = $response;
        } catch (\Exception $e) {
            $log['errors'] = [$e->getMessage()];
            $log['success'] = false;

            $resultJson->setData([
                'data' => [],
                'error' => __('Failed to initialize.')
            ]);
        } finally {
            $this->logger->debug($log);
        }

        return $resultJson;
    }
}
