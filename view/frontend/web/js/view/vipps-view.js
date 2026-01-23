/*
 * Copyright 2022 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */
define([
    'ko',
    'uiComponent',
    'underscore',
    'domReady',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/get-totals',
    'mage/translate',
    'Magento_Ui/js/model/messageList',
    'mage/url',
    'Magento_Customer/js/model/customer'
], function (
    ko,
    Component,
    _,
    domReady,
    stepNavigator,
    shippingService,
    customerData,
    quote,
    getTotalsAction,
    $t,
    messageList,
    url,
    customer
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Vipps_Checkout/vipps_view'
        },
        isVisible: ko.observable(true),
        currentTotals: {},
        processCounter: 0,

        initialize: function () {
            this._super();
            stepNavigator.registerStep(
                'vipps_checkout',
                null,
                $t('Checkout'),
                this.isVisible,
                _.bind(this.navigate, this),
                100
            );

            return this;
        },

        loadFrame: function () {
            window.vippsCheckout = VippsCheckout({
                checkoutFrontendUrl: window.checkoutConfig.vippsCheckout.checkoutFrontendUrl,
                iFrameContainerId: "vipps-checkout-frame-container",
                language: "no",
                on: {
                    "shipping_option_selected": function(data) {
                        if (data.price !== undefined) {
                            window.vippsShipping = {};
                            window.vippsShipping.id = data.id;
                            window.vippsShipping.price = data.price.fractionalDenomination;
                        }
                        data['cartId'] = quote.getQuoteId();
                        jQuery.ajax({
                            url: url.build('checkout/vipps/UpdateShippingOption'),
                            type: "POST",
                            dataType: "json",
                            data: data,
                            beforeSend: () => this.startProcess(),
                            complete:   () => this.stopProcess()
                        }).done(() => {
                            quote.shippingMethod({
                                "carrier_title": data.brand,
                                "method_title": data.product
                            });
                            getTotalsAction([], jQuery.Deferred());
                        });
                    },
                    "total_amount_changed": function(data) {
                        this.handleTotalAmountChanged(data);
                    },
                    "session_status_changed": function(data) {
                        // Do something when status changed
                    },
                    "shipping_address_changed": function(data) {
                        data['cartId'] = quote.getQuoteId();
                        jQuery.ajax({
                            url: url.build('checkout/vipps/UpdateShippingAddress'),
                            type: "POST",
                            dataType: "json",
                            data: data
                        }).done((response) => {
                            getTotalsAction([], jQuery.Deferred());
                        });
                    },
                    "customer_information_changed": function(data) {
                        data['cartId'] = quote.getQuoteId();
                        jQuery.ajax({
                            url: url.build('checkout/vipps/UpdateCustomerInformation'),
                            type: "POST",
                            dataType: "json",
                            data: data
                        }).done((response) => {
                            getTotalsAction([], jQuery.Deferred());
                        });
                    },
                },
            });
        },

        startProcess() {
            if (this.processCounter === 0) {
                jQuery(document.body).trigger('processStart');
            }
            this.processCounter = this.processCounter + 1;
        },

        stopProcess() {
            if (this.processCounter > 0) {
                this.processCounter = this.processCounter - 1;
                if (this.processCounter === 0) {
                    jQuery(document.body).trigger('processStop');
                }
            }
        },

        /**
         * The navigate() method is responsible for navigation between checkout step
         * during checkout. You can add custom logic, for example some conditions
         * for switching to your custom step. (This method is required even though it
         * is blank, don't delete)
         */
        navigate: function () {

        },

        navigateToNextStep: function () {
            stepNavigator.next();
        }
    });
});