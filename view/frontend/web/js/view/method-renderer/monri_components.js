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
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/model/customer',
        'Monri_Payments/js/view/method-renderer/monri_components/style',
        'mage/url',
        'mage/translate'
    ],
    function (
        Component,
        $,
        _,
        quote,
        customer,
        style,
        urlBuilder,
        $t
    ) {
        'use strict';

        var monriConfig = window.checkoutConfig.payment.monri_components;

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

            clientSecret: null,
            result: null,
            transactionTimeout: null,
            transactionTimeLimit: 900,
            afterRenderDefer: null,

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

                this.afterRenderDefer = $.Deferred();
                $.when(this.monriAddScriptTag(), this.afterRenderDefer.promise())
                    .then(this.monriCreatePayment.bind(this), this.monriFailed.bind(this));

            },

            monriAddScriptTag: function () {
                var deferred = $.Deferred();

                var element, scriptTag;
                element = document.createElement('script');

                element.src = monriConfig.componentsJsUrl;
                element.onload = deferred.resolve;
                element.onerror = deferred.reject;

                scriptTag = document.getElementsByTagName('script')[0];
                scriptTag.parentNode.insertBefore(element, scriptTag);

                return deferred.promise();
            },

            onTotalsUpdate: function () {
                this.monriCreatePayment();
            },

            monriCreatePayment: function () {
                if (this.monriCreatePaymentAction) {
                    this.monriCreatePaymentAction.abort();
                    this.monriCreatePaymentAction = null;
                }

                this.isLoading = true;
                this.monriReady = false;

                var currentTime = this.getCurrentTime();
                this.transactionTimeout = currentTime + this.transactionTimeLimit;

                var url = urlBuilder.build('monripayments/components/createPayment');
                this.monriCreatePaymentAction = $.post(url)
                    .done(
                        function (response) {
                            this.monriInit(response.data);
                        }.bind(this)
                    ).fail(
                        this.monriFailed.bind(this)
                    );
            },

            monriInit: function (data) {
                // if this is same init, don't change previous form
                if (this.clientSecret === data.client_secret) {
                    this.isLoading = false;
                    this.monriReady = true;
                    return;
                }

                this.clientSecret = data.client_secret;

                $('#' + this.monriCardContainerId).empty();

                this.monriInstance = Monri(monriConfig.authenticityToken, {
                    locale: monriConfig.locale
                });

                var components = this.monriInstance.components({clientSecret: this.clientSecret});

                this.monriCardInstance = components.create('card', {
                    style: style,
                    showInstallmentsSelection: Number(monriConfig.allowInstallments)
                });

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
            fixMonriForm: function (method) {
                if (method === this.getCode()) {
                    $('#' + this.monriCardContainerId + '>iframe').css('height', 'auto');
                }
            },

            checkIsValidOrder: function () {
                return this.getCurrentTime() < this.transactionTimeout;
            },

            placeOrder: function (data, event) {
                this.monriReady = false;

                if (event) {
                    event.preventDefault();
                }

                if (!this.checkIsValidOrder()) {
                    this.messageContainer.addErrorMessage({
                        message: $t('Sorry, timeout occured. Payment form will be reloaded.')
                    });
                    this.monriReady = true;
                    this.monriCreatePayment();
                    return;
                }

                var parentPlaceOrder = this._super.bind(this);

                this.monriInstance.confirmPayment(this.monriCardInstance, this.getTransactionData())
                    .then(function (result) {

                        if (result.error) {
                            var message = result.error.message ? result.error.message : $t('Transaction declined.');

                            this.messageContainer.addErrorMessage({
                                message: message
                            });

                            this.monriReady = true;
                            return;
                        }

                        // handle declined on 3DS Cancel
                        if (result.result.status === 'approved') {
                            this.result = result.result;
                            parentPlaceOrder(data, event);
                        } else {
                            this.messageContainer.addErrorMessage({
                                message: $t('Transaction declined.')
                            });
                        }

                    }.bind(this));
            },

            getTransactionData: function () {
                var email = customer.isLoggedIn() ? customer.customerData.email : quote.guestEmail;
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
                    fullName: (address.firstname + ' ' + address.lastname).substring(0,30),
                    city: address.city,
                    zip: address.postcode,
                    phone: address.telephone,
                    country: address.countryId,
                    email: email,
                    orderInfo: $t('Magento Order')
                };
            },

            start: function () {
                this.afterRenderDefer.resolve();
            },

            getData: function () {
                var additionalData = {
                    'data_secret': this.clientSecret,
                    'transaction_data': JSON.stringify(this.result),
                };

                return {
                    'method': this.item.method,
                    'additional_data': additionalData
                };
            },

            getCurrentTime: function () {
                return Math.floor(Date.now() / 1000);
            }
        });
    }
);
