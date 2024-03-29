<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Controller;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use Monri\Payments\Gateway\Exception\TransactionAlreadyProcessedException;
use Monri\Payments\Model\GetOrderIdByIncrement;

/**
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
abstract class AbstractGatewayResponse extends Action
{
    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var CommandManagerInterface
     */
    protected $commandManager;

    /**
     * @var GetOrderIdByIncrement
     */
    private $getOrderIdByIncrement;

    /**
     * AbstractGatewayResponse constructor.
     *
     * @param Context $context
     * @param OrderRepository $orderRepository
     * @param CommandManagerInterface $commandManager
     * @param GetOrderIdByIncrement $getOrderIdByIncrement
     */
    public function __construct(
        Context $context,
        OrderRepository $orderRepository,
        CommandManagerInterface $commandManager,
        GetOrderIdByIncrement $getOrderIdByIncrement
    ) {
        parent::__construct($context);

        $this->orderRepository = $orderRepository;
        $this->commandManager = $commandManager;
        $this->getOrderIdByIncrement = $getOrderIdByIncrement;
    }

    /**
     * Get order by ID
     *
     * @param int $orderId
     * @return OrderInterface|null
     * @throws InputException
     * @throws NoSuchEntityException
     */
    protected function getOrderById($orderId)
    {
        return $this->orderRepository->get($orderId);
    }

    /**
     * Get order by increment id
     *
     * @param string $orderIncrementId
     * @return OrderInterface|null
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    protected function getOrderByIncrementId($orderIncrementId)
    {
        $id = $this->getOrderIdByIncrement->execute($orderIncrementId);

        if (!$id) {
            throw new NoSuchEntityException(__('Order not found.'));
        }

        return $this->getOrderById($id);
    }

    /**
     * Processes the gateway request
     *
     * @param array $gatewayResponse
     * @param InfoInterface $payment
     * @param mixed $verificationData
     * @return array
     * @throws CommandException
     * @throws NotFoundException
     * @throws TransactionAlreadyProcessedException
     */
    protected function processGatewayResponse(array $gatewayResponse, InfoInterface $payment, $verificationData)
    {
        $arguments = [
            'response' => $gatewayResponse,
            'verification_data' => $verificationData
        ];

        $result = $this->commandManager->executeByCode('gateway_response', $payment, $arguments);

        return $result->get();
    }
}
