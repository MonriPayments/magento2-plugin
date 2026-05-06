/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Vault/js/view/payment/vault-enabler',
        'jquery',
        'underscore',
        'mage/template',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Customer/js/customer-data',
        'mage/url'
    ],
    function (Component, VaultEnabler, $, _, mageTemplate, errorProcessor, fullScreenLoader, customerData, urlBuilder) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Monri_Payments/form'
            },
            redirectAfterPlaceOrder: false,

            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                this.vaultEnabler.isActivePaymentTokenEnabler(false); //unchecked by default

                return this;
            },

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {}
                };
                this.vaultEnabler.visitAdditionalData(data);
                return data;
            },

            /**
             * @return {Boolean}
             */
            isVaultEnabled: function () {
                return this.vaultEnabler.isVaultEnabled();
            },

            /**
             * @return {String}
             */
            getVaultCode: function () {
                return window.checkoutConfig.payment[this.getCode()].vaultCode;
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

            collectBrowserInfo: function () {
                var screen_width = window && window.screen ? window.screen.width : '';
                var screen_height = window && window.screen ? window.screen.height : '';
                var color_depth = window && window.screen ? window.screen.colorDepth : '';
                var user_agent = window && window.navigator ? window.navigator.userAgent : '';
                var java_enabled = window && window.navigator ? navigator.javaEnabled() : false;
                var ip_address = window.checkoutConfig.payment[this.getCode()].customerIp || '';

                var language = '';
                if (window && window.navigator) {
                    language = window.navigator.language
                        ? window.navigator.language
                        : window.navigator.browserLanguage || '';
                }

                var time_zone_offset = (new Date()).getTimezoneOffset();

                return {
                    screen_width: screen_width,
                    screen_height: screen_height,
                    color_depth: color_depth,
                    user_agent: user_agent,
                    time_zone_offset: time_zone_offset,
                    language: language,
                    java_enabled: java_enabled,
                    http_accept: '*/*',
                    http_user_agent: user_agent,
                    http_accept_language: language || '*',
                    ip: ip_address
                };
            },

            redirect: function (url, payload) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = url;

                payload['browser_info'] = JSON.stringify(this.collectBrowserInfo());

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
