define([
    'jquery',
    'uiComponent',
    'mage/translate',
    'mage/url',
    'Magento_Customer/js/customer-data'
], function ($, Component, $t, urlBuilder, customerData) {
    'use strict';

    return Component.extend({
        initialize: function (config) {
            this._super();

            var self = this;

            if (config.error) {
                $('#monri-error').text(config.message || $t('Google Pay is unavailable.'));
                return;
            }

            function monriAddScriptTag(url) {
                var deferred = $.Deferred();
                var script = document.createElement('script');
                script.src = url;
                script.onload = deferred.resolve;
                script.onerror = deferred.reject;
                document.head.appendChild(script);
                return deferred.promise();
            }

            function monriCreatePayment() {
                var transaction = {
                    ch_full_name: config.payload.ch_full_name,
                    ch_address: config.payload.ch_address,
                    ch_city: config.payload.ch_city,
                    ch_zip: config.payload.ch_zip,
                    ch_phone: config.payload.ch_phone,
                    ch_country: config.payload.ch_country,
                    ch_email: config.payload.ch_email,
                    ch_language: config.payload.locale
                };

                var monri = Monri(config.authenticityToken, {locale: config.payload.locale});
                var components = monri.components({clientSecret: config.payload.client_secret});

                var googlePay = components.create('google-pay', {
                    style: { invalid: { color: 'red' } },
                    trx_token: config.payload.client_secret,
                    environment: config.isTest ? 'test' : 'production',
                    transaction: transaction
                });

                googlePay.mount('google-pay-element');

                window.addEventListener('message', (event) => {
                    if (event.data?.sentinel === '__ACTIVITIES__' && event.data?.cmd === 'check') {
                        customerData.invalidate(['cart', 'checkout-data']);
                        window.location.href = urlBuilder.build('monripayments/googlepay/cancel');
                    }

                    if (event.data?.type === 'PAYMENT_RESULT') {
                        const {transaction} = event.data;
                        if (transaction.status === 'approved') {
                            window.location.href = urlBuilder.build('monripayments/googlepay/success');
                        } else {
                            customerData.invalidate(['cart', 'checkout-data']);
                            window.location.href = urlBuilder.build('monripayments/googlepay/cancel');
                        }
                    }
                });
            }

            function monriFailed() {
                $('#monri-error').text($t('Monri failed to initialize.'));
            }

            $.when(monriAddScriptTag(config.componentsJsUrl))
                .then(monriCreatePayment)
                .fail(monriFailed);
        }
    });
});
