<?php

namespace Monri\Payments\Controller\WSPay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultInterface;

class Success extends Action implements HttpGetActionInterface
{
    /**
     * @var CommandInterface
     */
    private $command;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Success constructor.
     * @param Context $context
     * @param CommandInterface $command
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        CommandInterface $command,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->command = $command;
        $this->logger = $logger;
    }

    /**
     * Payment success action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        // execute response success command
        try {
            $this->command->execute([
                'response' => $this->getRequest()->getParams()
            ]);
        } catch (CommandException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->logger->critical($e);

            // 404 page
            // @todo: add generic error message here?
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('noroute');
            return $resultForward;
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/onepage/success');

        return $resultRedirect;
    }
}
