/*
 * Copyright 2022 Vipps
 * Event deduplication approach to prevent duplicate event processing
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
        messageEventHandler: null,
        lastEventData: {}, // Store last event data for each event type
        eventTimestamps: {}, // Store timestamps to handle rapid identical events
        processCounter: 0, // Loader counter flag to prevent overlapping processes

        initialize: function () {
            this._super();

            window.VippsComponent = this;
            this.currentFrameId = 0;

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

        // Generate a hash/fingerprint of event data for comparison
        generateEventFingerprint: function(eventType, data) {
            try {
                // Create a stable string representation of the data
                const dataString = JSON.stringify(data, Object.keys(data).sort());
                const fingerprint = eventType + '|' + dataString;
                return fingerprint;
            } catch (e) {
                // Fallback for circular references or non-serializable data
                return eventType + '|' + Date.now() + '|' + Math.random();
            }
        },

        // Check if this event is a duplicate of the last one
        isDuplicateEvent: function(eventType, data) {
            const fingerprint = this.generateEventFingerprint(eventType, data);
            const now = Date.now();
            const timeWindow = 1000; // 1 second window for duplicate detection
            console.log(fingerprint);

            // Check if we have a recent identical event
            if (this.lastEventData[eventType] &&
                this.lastEventData[eventType].fingerprint === fingerprint) {

                const timeDiff = now - this.lastEventData[eventType].timestamp;

                if (timeDiff < timeWindow) {
                    console.log('Duplicate event detected:', eventType, 'within', timeDiff, 'ms');
                    return true;
                }
            }

            // Store this event as the latest
            this.lastEventData[eventType] = {
                fingerprint: fingerprint,
                timestamp: now,
                data: _.clone(data) // Store a copy for debugging
            };

            return false;
        },

        // Wrapper for event handlers that includes deduplication
        createDedupedHandler: function(eventType, handlerFunction) {
            const self = this;

            return function(data) {
                console.log('Event received:', eventType, 'frame:', self.currentFrameId);

                // Check for duplicate
                if (self.isDuplicateEvent(eventType, data)) {
                    console.log('Discarding duplicate event:', eventType);
                    return;
                }

                console.log('Processing unique event:', eventType);

                // Call the actual handler
                handlerFunction.call(self, data);
            };
        },

        loadFrame: function (a, b, c, token = undefined) {
            var self = this;

            this.currentFrameId++;
            console.log({'loading frame': this.currentFrameId});

            // Create deduplicated event handlers
            const eventHandlers = {
                "shipping_option_selected": this.createDedupedHandler(
                    "shipping_option_selected",
                    function(data) {
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
                    }
                ),

                "total_amount_changed": this.createDedupedHandler(
                    "total_amount_changed",
                    this.handleTotalAmountChanged
                ),

                "session_status_changed": this.createDedupedHandler(
                    "session_status_changed",
                    function(data) {
                    }
                ),

                "shipping_address_changed": this.createDedupedHandler(
                    "shipping_address_changed",
                    function(data) {
                        data['cartId'] = quote.getQuoteId();
                        jQuery.ajax({
                            url: url.build('checkout/vipps/UpdateShippingAddress'),
                            type: "POST",
                            dataType: "json",
                            data: data
                        }).done(() => {
                            getTotalsAction([], jQuery.Deferred());
                        });
                    }
                ),

                "customer_information_changed": this.createDedupedHandler(
                    "customer_information_changed",
                    function(data) {
                        data['cartId'] = quote.getQuoteId();
                        jQuery.ajax({
                            url: url.build('checkout/vipps/UpdateCustomerInformation'),
                            type: "POST",
                            dataType: "json",
                            data: data
                        }).done(() => {
                            getTotalsAction([], jQuery.Deferred());
                        });
                    }
                ),
            };

            const options = {
                checkoutFrontendUrl: window.checkoutConfig.vippsCheckout.checkoutFrontendUrl,
                iFrameContainerId: "vipps-checkout-frame-container",
                language: "no",
                on: eventHandlers,
            };

            if (token) {
                options.token = token;
            }

            delete window.vippsCheckout;
            window.vippsCheckout = VippsCheckout(options);
            console.log('Created new VippsCheckout instance for frame', this.currentFrameId);
        },

        // Clear event history when reloading frame
        clearEventHistory: function() {
            this.lastEventData = {};
            this.eventTimestamps = {};
            console.log('Cleared event deduplication history');
        },

        reloadFrame: function (token = undefined) {
            // Clear event history to allow legitimate events after reload
            this.clearEventHistory();

            // Get current URL and modify query params
            const url = new URL(window.location.href);
            url.searchParams.set('token', token);

            // Update the browser's address bar without reloading
            window.history.replaceState({}, document.title, url.toString());

            document.querySelector('#vipps-checkout-frame-container iframe')?.remove();

            // Small delay before reloading
            setTimeout(() => {
                this.loadFrame(null, null, null, token);
            }, 100);
        },

        fetchNewToken: function() {
            var self = this;
            jQuery.ajax({
                url: url.build('checkout/vipps/initsession'),
                type: 'POST',
                dataType: 'json',
                showLoader: true,
                data: {cartId: quote.getQuoteId()}
            }).done(function (response) {
                if (response.success) {
                    console.log('Token:', response.token);
                    self.reloadFrame(response.token)
                } else {
                    console.error('Failed to fetch token:', response.message);
                }
            }).fail(function (jqXHR, textStatus, errorThrown) {
                console.error('Error fetching token:', textStatus, errorThrown);
            });
        },

        reload: function() {
            window.location.assign(url.build('checkout/vipps/session'));
        },

        handleTotalAmountChanged: function (data) {
            console.log('Processing total_amount_changed:', data);

            if (window.vippsRecentlyUpdated) {
                window.vippsRecentlyUpdated = false;
                return;
            }
            data['cartId'] = quote.getQuoteId();
            if (window.vippsShipping) {
                data['shippingId'] = window.vippsShipping.id;
                data['shippingPrice'] = window.vippsShipping.price;
            }
            var totals = quote.getTotals();
            var updateTotals = true;
            // Check if totals are different from the data received from Vipps
            if (data.fractionalDenomination == parseInt(totals().grand_total * 100)) {
                updateTotals = false;
            }
            // Check if totals are different from the data received from Vipps without shipping
            if (data.fractionalDenomination == parseInt((totals().grand_total - totals().shipping_incl_tax) * 100)) {
                updateTotals = false;
            }

            if (updateTotals) {
                new Promise((resolve) => {
                    this.startProcess();
                    window.vippsCheckout.lock();
                    setTimeout(() => {
                        resolve();
                    }, 500); // Simulate delay for lock operation
                }).then(() => {
                    jQuery.ajax({
                        url: url.build('checkout/vipps/UpdateTotals'),
                        type: "POST",
                        dataType: "json",
                        data: data
                    }).done((response) => {
                        getTotalsAction([], jQuery.Deferred());
                        window.vippsCheckout.unlock();
                        this.stopProcess();
                    });
                });
            }
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

        // Debug method to see event history
        getEventHistory: function() {
            return this.lastEventData;
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
})

