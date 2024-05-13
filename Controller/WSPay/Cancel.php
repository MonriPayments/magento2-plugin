<?php

namespace Monri\Payments\Controller\WSPay;

use Monri\Payments\Gateway\Helper\TestModeHelper;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\ResultInterface;

class Cancel extends Action implements HttpGetActionInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var CommandInterface
     */
    private $command;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CommandInterface
     */
    private CommandInterface $paymentReviewCommand;

    /**
     * Cancel constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param CommandInterface $command
     * @param CommandInterface $paymentReviewCommand
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        CommandInterface $command,
        CommandInterface $paymentReviewCommand,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->command = $command;
        $this->logger = $logger;
        $this->paymentReviewCommand = $paymentReviewCommand;
    }

    /**
     * Payment cancel action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            // execute response cancel command
            //@todo: remove?
            if ($this->getRequest()->getParam('ErrorCodes') === 'E00012') {
                $this->paymentReviewCommand->execute([
                    'response' => $this->getRequest()->getParams()
                ]);
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('checkout/onepage/success');
                return $resultRedirect;
            }

            /** @var \Magento\Payment\Gateway\Command\Result\ArrayResult $commandResult */
            $commandResult = $this->command->execute([
                'response' => $this->getRequest()->getParams()
            ]);
            $this->messageManager->addNoticeMessage(__('Payment has been canceled.'));

            $order = SubjectReader::readPayment($commandResult->get())->getOrder();
            $orderIncrementId = $order->getOrderIncrementId();
            $this->checkoutSession->setLastRealOrderId($orderIncrementId);

        } catch (CommandException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('System error occurred.'));
            $this->logger->critical($e);
        }

        // try to restore the quote; if error occurred it will be restored from session
        $this->checkoutSession->restoreQuote();

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('checkout/cart');

        return $resultRedirect;
    }
}
