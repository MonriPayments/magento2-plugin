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

        var scriptTagAdded = false;

        return Component.extend({
            defaults: {
                template: 'Monri_Payments/components',
                monriCardContainerId: 'monri-card-container'
            },
            redirectAfterPlaceOrder: true,
            monriInstance: null,
            monriCardInstance: null,


            // /monri/components/createPayment -> get clientSecret

            getCode: function() {
                return 'monri_components';
            },

            initialize: function () {
                this._super();

                //make sure script is loaded before any other action!!
                this.monriAddScriptTag();
                this.monriCreatePayment();
            },

            monriAddScriptTag: function() {
                var element, scriptTag;

                if (!scriptTagAdded) {
                    element = document.createElement('script');
                    scriptTag = document.getElementsByTagName('script')[0];

                    //element.async = true;
                    // get base url from window.checkout
                    element.src = 'https://ipgtest.monri.com/dist/components.js';

                    scriptTag.parentNode.insertBefore(element, scriptTag);
                    scriptTagAdded = true;
                }
            },

            monriCreatePayment: function() {

                var url = urlBuilder.build('monripayments/components/createPayment');

                $.get(url)
                    .done(function (response) {
                        console.log(response);
                        this.monriInit(response.data);
                    }.bind(this))
                    .fail(function (response) {

                    });
            },

            monriInit: function (data) {
                //authenticity_token, locale and stlye settings should come from window.checkout

                this.monriInstance = Monri(data.authenticity_token, {locale: 'hr'});
                var components = this.monriInstance.components({clientSecret: data.client_secret});

                this.monriCardInstance = components.create('card');
                this.monriCardInstance.mount(this.monriCardContainerId);
            },

            placeOrder: function(data, event) {
                if (event) {
                    event.preventDefault();
                }

                var self = this;

                console.log('placeOrder', data);

                // get from billing address
                const transactionParams = {
                    address: "Adresa 123",
                    fullName: "Test Test",
                    city: "Osijek",
                    zip: "31000",
                    phone: "+385123456789",
                    country: "HR",
                    email: "ivan@favicode.net",
                    orderInfo: "Testna trx"
                };

                this.monriInstance.confirmPayment(this.monriCardInstance, transactionParams).then(function (result) {
                    console.log(result);

                    if (result.error) {
                        // add to magento error message ?
                        alert(result.error.message);

                        //var errorElement = document.getElementById('card-errors');
                        //errorElement.textContent = result.error.message;
                    } else {

                        // handle declined on 3DS Cancel

                        if (result.status === 'approved') {
                            // place order
                            //alert('Call parent');
                            self._super(data, event);
                        }

                    }
                });

            }

        });
    }
);
