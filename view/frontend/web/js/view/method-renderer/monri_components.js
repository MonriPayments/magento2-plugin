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
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'mage/translate'
    ],
    function (Component, $, _, customerData, quote, urlBuilder, $t) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Monri_Payments/components',
                monriCardContainerId: 'monri-card-container',
                tracks: {
                    isLoading: true,
                    monriReady: true
                },
                listens: {
                    isChecked: 'fixMonriForm'
                }
            },
            redirectAfterPlaceOrder: true,
            isLoading: true,
            monriReady: false,
            monriCreatePaymentAction: null,

            monriInstance: null,
            monriCardInstance: null,
            dataSecret: null,
            result: null,

            getCode: function () {
                return 'monri_components';
            },

            initialize: function () {
                this._super();

                var totals = quote.getTotals();
                this.lastGrandTotal = totals() ? totals()['grand_total'] : 0;

                totals.subscribe(function (newTotals) {
                    if (newTotals && newTotals['grand_total'] !== this.lastGrandTotal) {

                        this.onTotalsUpdate();
                        this.lastGrandTotal = newTotals['grand_total'];
                    }
                }.bind(this));

                // add script & create payment
                /*
                $.getScript('https://ipgtest.monri.com/dist/components.js')
                    .done(this.monriCreatePayment.bind(this))
                    .fail(this.monriFailed.bind(this));
               */

                this.monriAddScriptTag()
                    .done(this.monriCreatePayment.bind(this))
                    .fail(this.monriFailed.bind(this));
            },

            monriAddScriptTag: function() {
                var deferred = $.Deferred();

                var element, scriptTag;
                element = document.createElement('script');

                // get base url from window.checkout
                element.src = 'https://ipgtest.monri.com/dist/components.js';
                element.onload = deferred.resolve;
                element.onerror = deferred.reject;

                scriptTag = document.getElementsByTagName('script')[0];
                scriptTag.parentNode.insertBefore(element, scriptTag);

                //@todo: add interval to check window.Monri if some browsers don't support onload?

                return deferred.promise();
            },

            onTotalsUpdate: function() {
                this.monriCreatePayment();
            },

            monriCreatePayment: function () {
                if (this.monriCreatePaymentAction) {
                    this.monriCreatePaymentAction.abort();
                    this.monriCreatePaymentAction = null;
                    console.log('monriCreatePayment ABORT');
                }

                console.log('monriCreatePayment CREATE');

                this.isLoading = true;
                this.monriReady = false;

                var url = urlBuilder.build('monripayments/components/createPayment');
                this.monriCreatePaymentAction = $.get(url)
                    .done(
                        function (response) {
                            this.monriInit(response.data);
                        }.bind(this)
                    ).fail(
                        this.monriFailed.bind(this)
                    );
            },

            monriInit: function (data) {
                //authenticity_token, locale and style settings should come from window.checkout
                console.log('monriInit', data);

                // if this is same init, don't change previous form
                if (this.dataSecret === data.client_secret) {
                    this.isLoading = false;
                    this.monriReady = true;
                    return;
                }

                this.dataSecret = data.client_secret;

                $('#'+this.monriCardContainerId).empty();
                this.monriInstance = Monri(data.authenticity_token, {locale: 'hr'});
                var components = this.monriInstance.components({clientSecret: this.dataSecret});
                this.monriCardInstance = components.create('card');
                this.monriCardInstance.mount(this.monriCardContainerId);

                this.isLoading = false;
                this.monriReady = true;
            },

            monriFailed: function () {
                this.monriReady = false;
                this.isLoading = false;
                this.messageContainer.addErrorMessage({
                    message: $t('Monri failed to initialize.')
                });
            },

            /**
             * Fixes problem when Monri iframe height is 0 when Magento payment block is hidden
             *
             * @param method
             */
            fixMonriForm: function(method) {
                if (method === this.getCode()) {
                    $('#'+this.monriCardContainerId+'>iframe').css('height', 'auto');
                }
            },

            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                var parentPlaceOrder = this._super.bind(this);

                this.monriInstance.confirmPayment(this.monriCardInstance, this.getTransactionData())
                    .then(function (result) {
                        console.log('MonriResult', result);

                        if (result.error) {
                            this.messageContainer.addErrorMessage({
                                message: $t(result.error.message)
                            });
                            return;
                        }

                        // handle declined on 3DS Cancel
                        if (result.result.status === 'approved') {
                            this.result = result.result;
                            console.log('parentPlaceOrder');
                            parentPlaceOrder(data, event);
                        } else {
                            this.messageContainer.addErrorMessage({
                                message: $t('Transaction declined.')
                            });
                        }

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
                var data = {
                    'method': this.item.method,
                    'additional_data': {
                        'data_secret': this.dataSecret
                    }
                };
                data['additional_data'] = _.extend(data['additional_data'], this.result);
                return data;
            }
        });
    }
);