/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 * @version 1.8.0
 */

define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'monri_google_pay',
                component: 'Monri_Payments/js/view/method-renderer/monri_google_pay'
            }
        );
        return Component.extend({});
    }
);
