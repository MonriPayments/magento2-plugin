/*browser:true*/
/*global define*/
define([
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'jquery',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/url',
    'Magento_Customer/js/customer-data',
], function (VaultComponent, $, errorProcessor,fullScreenLoader, urlBuilder, customerData) {
    'use strict';

    return VaultComponent.extend({
        /**
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        },
        // This flag lets us fire afterPlaceOrder instead of redirecting to success page!
        redirectAfterPlaceOrder: false,

        /**
         * Get last 4 digits of card
         * @returns {String}
         */
        getMaskedCard: function () {
            return this.details.maskedCC;
        },

        /**
         * Get expiration date
         * @returns {String}
         */
        getExpirationDate: function () {
            return this.details.expirationDate;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },

        afterPlaceOrder: function() {
            fullScreenLoader.startLoader();
            var url = urlBuilder.build('monripayments/redirect/form_vaultdata');

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
});
