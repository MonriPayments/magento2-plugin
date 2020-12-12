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
        'mage/url',
        'Magento_Checkout/js/model/quote',
    ],
    function (Component, $, _, mageTemplate, errorProcessor, fullScreenLoader, customerData, urlBuilder, quote) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Monri_Payments/components',
                monriCardContainerId: 'monri-card-container',
                tracks: {
                    isLoading: true
                }
            },
            redirectAfterPlaceOrder: true,
            monriInstance: null,
            monriCardInstance: null,
            dataSecret: null,
            isLoading: false,
            result: null,

            getCode: function () {
                return 'monri_components';
            },

            initialize: function () {
                this._super();
                this.isLoading = true;
                this.monriAddScriptTag();
            },

            monriAddScriptTag: async function () {
                $.getScript('https://ipgtest.monri.com/dist/components.js')
                    .done(this.monriCreatePayment.bind(this));
            },

            monriCreatePayment: function () {

                var url = urlBuilder.build('monripayments/components/createPayment');

                $.get(url)
                    .done(function (response) {
                        this.monriInit(response.data);
                    }.bind(this))
                    .fail(function (response) {
                        alert('Error Occured.');
                    }).always(function () {
                    this.isLoading = false;
                }.bind(this));
            },

            monriInit: function (data) {
                //authenticity_token, locale and stlye settings should come from window.checkout
                console.log('Monri Init');
                console.log(data);
                this.dataSecret = data.client_secret;
                this.monriInstance = Monri(data.authenticity_token, {locale: 'hr'});
                var components = this.monriInstance.components({clientSecret: this.dataSecret});

                this.monriCardInstance = components.create('card');
                this.monriCardInstance.mount(this.monriCardContainerId);
            },

            placeOrder: function (data, event) {
                var original = this._super.bind(this);
                if (event) {
                    event.preventDefault();
                }
                var myPromise = new Promise(function (myresolve, myreject) {
                    this.monriInstance.confirmPayment(this.monriCardInstance, this.getTransactionData())
                        .then(function (result) {
                            if (result.error) {
                                myreject(result.error);
                            } else {
                                // handle declined on 3DS Cancel
                                if (result.result.status === 'approved') {
                                    this.result = result.result;
                                    myresolve(result.result)
                                } else {
                                    myreject(result);
                                }
                            }
                        }.bind(this));
                }.bind(this));

                myPromise.then(function (r) {
                    alert(r);
                    console.log(r);
                    original(data, event)
                }.bind(this)).catch(function (r) {
                    alert(r.message);
                }.bind(this));

            },
            getTransactionData: function () {
                var address = quote.billingAddress();

                var street = address.street[0];
                if (typeof address.street[1] !== "undefined") {
                    street += ' ' + address.street[1];
                }

                if (typeof address.street[2] !== "undefined") {
                    street += ' ' + address.street[2];
                }

                return {
                    address: street,
                    fullName: address.firstname + ' ' + address.lastname,
                    city: address.city,
                    zip: address.postcode,
                    phone: address.telephone,
                    country: address.countryId,
                    email: typeof quote.guestEmail === 'string' ? quote.guestEmail : address.email,
                    orderInfo: 'Test Order Magento'
                };
            },
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'data_secret': this.dataSecret,
                        'result': this.result
                    }
                };
            }
        });
    }
);