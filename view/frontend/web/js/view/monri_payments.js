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
                type: 'monri_payments',
                component: 'Monri_Payments/js/view/method-renderer/monri_payments'
            }
        );

        return Component.extend({});
    }
);
