<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Gateway\Exception;

use Magento\Payment\Gateway\Command\CommandException;

class TransactionAlreadyProcessedException extends CommandException
{
}
