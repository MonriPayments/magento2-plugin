<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Plugin;

use Closure;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\CsrfValidator;
use Magento\Framework\App\Request\Http;

class CallbackCsrfSkip
{
    /**
     * Disable Csrf validation for Monri Payments Gateway Callback
     *
     * @param CsrfValidator $subject
     * @param Closure $proceed
     * @param Http $request
     * @param ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        Closure $proceed,
        $request,
        $action
    ) {
        if ($request->getFullActionName() == 'monripayments_gateway_callback') {
            return;
        }
        $proceed($request, $action);
    }
}
