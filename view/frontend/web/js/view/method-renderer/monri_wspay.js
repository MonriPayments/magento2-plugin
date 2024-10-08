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
    function (Component, VaultEnabler, $, _,
              mageTemplate, errorProcessor, fullScreenLoader, customerData, urlBuilder) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Monri_Payments/wspay_form'
            },
            redirectAfterPlaceOrder: false,

            initialize: function () {
                this._super();

                this.vaultEnabler = new VaultEnabler();
                this.vaultEnabler.setPaymentCode(this.getVaultCode());
                this.vaultEnabler.isActivePaymentTokenEnabler(false); //unchecked by default

                return this;
            },

            getCode: function() {
                return 'monri_wspay';
            },


            /**
             * @return {Object}
             */
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

            getDescription: function () {
                return window.checkoutConfig.payment[this.getCode()].description;
            },

            afterPlaceOrder: function() {
                fullScreenLoader.startLoader();
                var url = urlBuilder.build('monripayments/wspay/buildFormData');

                $.get(url)
                    .done(function (response) {
                        customerData.invalidate(['cart', 'checkout-data']);
                        this.buildForm(response).submit();
                    }.bind(this)).fail(function (response) {
                        errorProcessor.process(response, self.messageContainer);
                        fullScreenLoader.stopLoader();
                    });
            },

            buildForm: function(data) {
                var formTmpl =
                    '<form action="<%= data.action %>" method="POST" enctype="application/x-www-form-urlencoded">' +
                    '<% _.each(data.fields, function(val, key){ %>' +
                    '<input value="<%= val %>" name="<%= key %>" type="hidden">' +
                    '<% }); %>' +
                    '</form>';

                var form = mageTemplate(formTmpl, {data: data});
                return $(form).appendTo($('body'));
            }
        });
    }
);
