<!-- START_METADATA
---
title: Extra functionalities and customizations
sidebar_label: Customization
description: Extra functionalities and customizations for Checkout
sidebar_position: 1
pagination_next: null
pagination_prev: null
section: Plugins
---
END_METADATA -->

# Extra functionalities and customizations for Vipps Checkout

:::warning Checkout – important update

Vipps MobilePay has entered into an agreement to sell the Checkout solution to [Kustom](https://www.kustom.co/).

As part of this transition, *Vipps MobilePay Checkout* will become *Kustom Checkout*. This means the Checkout product you ordered will be delivered and developed by Kustom going forward.
If you have questions, you can check our [FAQ](https://vippsmobilepay.com/vippsmobilepay-kustom).

:::

*For support, email us at [vipps@bluemint.dev](mailto:vipps@bluemint.dev)*

## Quantity selector buttons in Checkout

You can add quantity selector buttons to Vipps Checkout by creating a mixin for
`Magento_Checkout/js/view/summary/item/details`, or by implementing the functionality in a custom JavaScript file.

After updating the quantity, update Magento totals and implement session update for Vipps Checkout, more information can be found in the [session update API reference](/api/checkout/#tag/Session/paths/~1checkout~1v3~1session~1%7Breference%7D/get):

## Discount field in Checkout

To add a discount (coupon) field to the Vipps Checkout page:

1. Add the discount field to the checkout template.
2. Add the discount field to the checkout layout in `checkout_vipps_index.xml`.
3. Extend or override the following JavaScript actions:
    - `Magento_SalesRule/js/action/set-coupon-code`
    - `Magento_SalesRule/js/action/cancel-coupon`

Once Magento has finished updating the checkout and recalculating totals, update the Vipps Checkout session with the new amount.

## Adobe Commerce gift card support

By default, the Vipps Checkout module does not support orders placed using the *Adobe Commerce Gift Card* module when the final order total is 0.

To enable this scenario, create a plugin or preference for the `Magento\GiftCardAccount\Model\Total\Quote\Giftcardaccount::collect` method.

In this plugin/preference, adjust the logic so that when the grand total becomes 0 after applying a gift card, the total is returned as 1 instead.

This allows the order to be processed correctly by Vipps Checkout.
