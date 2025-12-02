define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'Magento_Checkout/js/action/get-totals'
], function ($, quote, url, getTotalsAction) {
    'use strict';

    return {
        /**
         * Update totals method
         */
        updateTotals: function () {
            const totals = quote.getTotals(),
                data = {
                    forceUpdate: true,
                    cartId: quote.getQuoteId()
                };

            if (totals && window.vippsCheckout) {
                if (window.vippsShipping) {
                    data['shippingId'] = window.vippsShipping.id;
                    data['shippingPrice'] = window.vippsShipping.price;
                }
                return new Promise((resolve) => {
                    window.vippsCheckout.lock();
                    setTimeout(() => {
                        resolve();
                    }, 500); // Simulate delay for lock operation
                }).then(() => {
                    jQuery.ajax({
                        url: url.build('checkout/vipps/UpdateTotals'),
                        type: 'POST',
                        dataType: 'json',
                        data: data
                    }).done(() => {
                        getTotalsAction([], jQuery.Deferred());
                        window.vippsCheckout.unlock();
                        jQuery(document.body).trigger('processStop');
                        window.vippsRecentlyUpdated = true;
                    });
                });
            }
        }
    };
});
