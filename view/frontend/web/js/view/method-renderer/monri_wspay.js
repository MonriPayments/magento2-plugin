/*browser:true*/
/*global define*/
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
    function (Component, $, _,
              mageTemplate, errorProcessor, fullScreenLoader, customerData, urlBuilder) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Monri_Payments/wspay_form'
            },
            redirectAfterPlaceOrder: false,

            initialize: function () {
                this._super();

                return this;
            },

            getCode: function() {
                return 'monri_wspay';
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
