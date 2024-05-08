<?php

namespace Monri\Payments\Controller\WSPay;

use Monri\Payments\Gateway\Command\WSPay\CallbackCommand;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\HTTP\ZendClient;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Model\Method\Logger;
use Psr\Log\LoggerInterface;

class Callback extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var CallbackCommand
     */
    private $command;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Logger
     */
    private $paymentLogger;

    /**
     * Callback constructor.
     *
     * @param Context $context
     * @param Json $jsonSerializer
     * @param CallbackCommand $command
     * @param Logger $paymentLogger
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Json $jsonSerializer,
        CallbackCommand $command,
        Logger $paymentLogger,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->jsonSerializer = $jsonSerializer;
        $this->command = $command;
        $this->paymentLogger = $paymentLogger;
        $this->logger = $logger;
    }

    /**
     * Payment callback action
     *
     * @inheritDoc
     */
    public function execute()
    {
        /** @var $request \Magento\Framework\App\Request\Http */
        $request = $this->getRequest();

        if ($request->getMethod() !== ZendClient::POST) {
            return $this->forwardNoRoute();
        }

        //$this->paymentLogger->debug(['payment_callback_request' => $this->getRequest()->getContent()]);

        if (empty($this->getRequest()->getContent())) {
            return $this->forwardNoRoute();
        }

        $requestData = $this->jsonSerializer->unserialize($this->getRequest()->getContent());

        // execute response success command
        try {
            $this->command->execute([
                'response' => $requestData
            ]);
        } catch (CommandException $e) {
            return $this->forwardNoRoute();

        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * 404 page
     * @return ResultInterface
     */
    private function forwardNoRoute(): ResultInterface
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        $resultForward->forward('noroute');
        return $resultForward;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
