define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/error-processor',
    'Magento_SalesRule/js/model/payment/discount-messages',
    'mage/storage',
    'mage/translate',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/action/recollect-shipping-rates',
    'Vipps_Checkout/js/update-totals'
], function (
    $,
    quote,
    urlManager,
    errorProcessor,
    messageContainer,
    storage,
    $t,
    getPaymentInformationAction,
    totals,
    fullScreenLoader,
    recollectShippingRates,
    updateTotals
) {
    'use strict';

    var dataModifiers = [],
        successCallbacks = [],
        failCallbacks = [];

    var action = function (couponCode, isApplied) {
        var quoteId = quote.getQuoteId(),
            url = urlManager.getApplyCouponUrl(couponCode, quoteId),
            message = $t('Your coupon was successfully applied.'),
            headers = {},
            data = {},
            outer = $.Deferred();

        // Allow external modifiers to tweak request
        dataModifiers.forEach(function (modifier) { modifier(headers, data); });

        fullScreenLoader.startLoader();

        storage.put(url, data, false, null, headers)
            .done(function (response) {
                if (!response) {
                    fullScreenLoader.stopLoader();
                    outer.resolve();
                    return;
                }

                isApplied(true);
                totals.isLoading(true);
                recollectShippingRates();

                var refresh = $.Deferred();
                // Your get-payment-information expects a Deferred
                getPaymentInformationAction(refresh, messageContainer);

                $.when(refresh)
                    .done(function () {
                        fullScreenLoader.stopLoader();
                        totals.isLoading(false);

                        messageContainer.addSuccessMessage({ message: message });
                        successCallbacks.forEach(function (cb) { cb(response); });

                        $.when(updateTotals.updateTotals())
                            .done(function () { outer.resolve(response); })
                            .fail(function (err) { outer.reject(err); });
                    })
                    .fail(function (err) {
                        fullScreenLoader.stopLoader();
                        totals.isLoading(false);
                        outer.reject(err);
                    });
            })
            .fail(function (response) {
                fullScreenLoader.stopLoader();
                totals.isLoading(false);
                errorProcessor.process(response, messageContainer);
                failCallbacks.forEach(function (cb) { cb(response); });
                outer.reject(response);
            });

        return outer.promise();
    };

    // Preserve original API
    action.registerDataModifier = function (modifier) { dataModifiers.push(modifier); };
    action.registerSuccessCallback = function (callback) { successCallbacks.push(callback); };
    action.registerFailCallback = function (callback) { failCallbacks.push(callback); };

    return action;
});
