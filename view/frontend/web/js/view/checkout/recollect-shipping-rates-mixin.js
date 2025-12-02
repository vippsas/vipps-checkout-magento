define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/action/select-shipping-address'
], function (wrapper, quote, rateRegistry, selectShippingAddress) {
    'use strict';

    return function (recollectShippingRates) {
        return wrapper.wrap(recollectShippingRates, function () {
            var shippingAddress = null;

            if (!quote.isVirtual()) {
                shippingAddress = quote.shippingAddress();

                // Fix for Vipps Checkout if coupon code is entered before shipping address is set
                if (shippingAddress && typeof shippingAddress.getCacheKey === 'function') {
                    rateRegistry.set(shippingAddress.getCacheKey(), null);
                    selectShippingAddress(shippingAddress);
                }
            }

            return recollectShippingRates;
        });
    };
});
