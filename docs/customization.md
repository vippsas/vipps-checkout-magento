# Extra Functionalities and Customizations for Vipps Checkout
*For support, email us at [vipps@bluemint.dev](mailto:vipps@bluemint.dev)*

## Quantity Selector Buttons in Vipps Checkout

You can add quantity selector buttons to Vipps Checkout by creating a mixin for
`Magento_Checkout/js/view/summary/item/details`, or by implementing the functionality in a custom JavaScript file.

After updating the quantity, update magento totals and implement session update for vipps checkout, more information can be found [here](https://developer.vippsmobilepay.com/api/checkout/#tag/Session/paths/~1checkout~1v3~1session~1%7Breference%7D/get):

## Discount Field in Vipps Checkout

To add a discount (coupon) field to the Vipps Checkout page:

1. Add the discount field to checkout template.
2. Add the discount field to the checkout layout in `checkout_vipps_index.xml`.
3. Extend or override the following JavaScript actions:
    - `Magento_SalesRule/js/action/set-coupon-code`
    - `Magento_SalesRule/js/action/cancel-coupon`

Once Magento has finished updating the checkout and recalculating totals, update the Vipps checkout session with the new amount.

## Adobe Commerce Giftcard support

By default, the Vipps Checkout module does not support orders placed using the Adobe Commerce Gift Card module when the final order total is 0.

To enable this scenario, create a plugin or preference for the `Magento\GiftCardAccount\Model\Total\Quote\Giftcardaccount::collect` method.

In this plugin/preference, adjust the logic so that when the grand total becomes 0 after applying a gift card, the total is returned as 1 instead. 

This allows the order to be processed correctly by Vipps Checkout.