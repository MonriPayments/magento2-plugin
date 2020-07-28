<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\ErrorMapper;

use Magento\Framework\Config\DataInterface;

class ErrorMessageMapper extends \Magento\Payment\Gateway\ErrorMapper\ErrorMessageMapper
{
    /**
     * @var bool
     */
    private $mapRawMessages;

    public function __construct(
        DataInterface $messageMapping,
        $mapRawMessages = false
    ) {
        parent::__construct($messageMapping);
        $this->mapRawMessages = $mapRawMessages;
    }

    public function getMessage(string $code)
    {
        $message = parent::getMessage($code);
        if (!$message && $this->mapRawMessages === true) {
            $message = $code;
        }

        return $message;
    }
}
