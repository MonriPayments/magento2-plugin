<?php

/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
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
