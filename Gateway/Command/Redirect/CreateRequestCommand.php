<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Command\Redirect;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class CreateRequestCommand implements CommandInterface
{
    /**
     * @var BuilderInterface
     */
    private $requestBuilder;

    /**
     * @method Command\Result\ArrayResult create(array $params)
     * @var ArrayResultFactory
     */
    private $resultFactory;

    /**
     * CreateRequestCommand constructor.
     *
     * @param BuilderInterface $builder
     * @param ArrayResultFactory $resultFactory
     */
    public function __construct(
        BuilderInterface $builder,
        ArrayResultFactory $resultFactory
    ) {
        $this->requestBuilder = $builder;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Builds the data object for redirect.
     *
     * @param array $commandSubject
     * @return null|Command\ResultInterface
     */
    public function execute(array $commandSubject)
    {
        $requestData = $this->requestBuilder->build($commandSubject);

        /** @var Command\Result\ArrayResult $result */
        $result = $this->resultFactory->create([
            'array' => $requestData
        ]);

        return $result;
    }
}
