define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/error-processor',
    'Magento_SalesRule/js/model/payment/discount-messages',
    'mage/storage',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'mage/translate',
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
    getPaymentInformationAction,
    totals,
    $t,
    fullScreenLoader,
    recollectShippingRates,
    updateTotals
) {
    'use strict';

    var successCallbacks = [];

    function callSuccessCallbacks() {
        successCallbacks.forEach(function (cb) { cb(); });
    }

    var action = function (isApplied) {
        var quoteId = quote.getQuoteId(),
            url = urlManager.getCancelCouponUrl(quoteId),
            message = $t('Your coupon was successfully removed.'),
            outer = $.Deferred();

        messageContainer.clear();
        fullScreenLoader.startLoader();

        storage.delete(url, false)
            .done(function () {
                totals.isLoading(true);
                recollectShippingRates();

                var refresh = $.Deferred();
                // Pass Deferred as your code sample shows
                getPaymentInformationAction(refresh, messageContainer);

                $.when(refresh)
                    .done(function () {
                        isApplied(false);
                        totals.isLoading(false);
                        fullScreenLoader.stopLoader();

                        callSuccessCallbacks();
                        messageContainer.addSuccessMessage({ message: message });

                        $.when(updateTotals.updateTotals())
                            .done(function () { outer.resolve(); })
                            .fail(function (err) { outer.reject(err); });
                    })
                    .fail(function (err) {
                        totals.isLoading(false);
                        fullScreenLoader.stopLoader();
                        outer.reject(err);
                    });
            })
            .fail(function (response) {
                totals.isLoading(false);
                fullScreenLoader.stopLoader();
                errorProcessor.process(response, messageContainer);
                outer.reject(response);
            });

        return outer.promise();
    };

    // Preserve original API
    action.registerSuccessCallback = function (callback) { successCallbacks.push(callback); };

    return action;
});
