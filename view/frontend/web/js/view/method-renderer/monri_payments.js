/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'underscore',
        'mage/template',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/customer-data',
        'mage/url'
    ],
    function (Component, $, _, mageTemplate, errorProcessor, fullScreenLoader, customerData, urlBuilder) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Monri_Payments/form'
            },
            redirectAfterPlaceOrder: false,

            getCode: function() {
                return 'monri_payments';
            },

            afterPlaceOrder: function() {
                fullScreenLoader.startLoader();
                var url = urlBuilder.build('monripayments/redirect/form_data');

                $.get(url)
                    .done(function (response) {
                        customerData.invalidate(['cart', 'checkout-data']);
                        this.redirect(response['url'], response['payload']);
                    }.bind(this))
                    .fail(function (response) {
                        errorProcessor.process(response, self.messageContainer);
                        fullScreenLoader.stopLoader();
                    });
            },

            redirect: function (url, payload) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = url;

                for (var field in payload) {
                    if (payload.hasOwnProperty(field)) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = field;

                        var data = payload[field];
                        if (typeof data === 'object') {
                            data = JSON.stringify(data);
                        }

                        input.value = data;
                        form.appendChild(input);
                    }
                }

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
);
