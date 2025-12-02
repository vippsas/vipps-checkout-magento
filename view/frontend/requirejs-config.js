var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/recollect-shipping-rates': {
                'Vipps_Checkout/js/view/checkout/recollect-shipping-rates-mixin': true
            },
        }
    },
    map: {
        '*': {
            'Magento_SalesRule/js/action/set-coupon-code': 'Vipps_Checkout/js/action/set-coupon-code',
            'Magento_SalesRule/js/action/cancel-coupon': 'Vipps_Checkout/js/action/cancel-coupon'
        }
    }
};
