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
                template: 'Monri_Payments/google_pay'
            },
            redirectAfterPlaceOrder: false,

            getCode: function () {
                return 'monri_google_pay';
            },

            initialize: function () {
                this._super();
                return this;
            },

            getData: function () {
                var data = {
                    'method': this.getCode(),
                    'additional_data': {}
                };
                return data;
            },

            afterPlaceOrder: function() {
                fullScreenLoader.startLoader();
                customerData.invalidate(['cart', 'checkout-data']);
                window.location.href = urlBuilder.build('monripayments/googlepay/payment');
            }
        });
    }
);
