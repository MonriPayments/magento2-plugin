/*browser:true*/
/*global define*/
define([
    'Magento_Vault/js/view/payment/method-renderer/vault',
    'jquery',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/url',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function (VaultComponent, $, errorProcessor,fullScreenLoader, urlBuilder, customerData, $t) {
    'use strict';

    return VaultComponent.extend({
        /**
         * @returns {String}
         */
        getToken: function () {
            return this.publicHash;
        },

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
            //todo: remove this once real expiration date is given
            return $t('In beta, no accurate expiration date');
            //return this.details.expirationDate;
        },

        /**
         * Get card type
         * @returns {String}
         */
        getCardType: function () {
            return this.details.type;
        },
    });
});
