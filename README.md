<!-- START_METADATA
---
title: Vipps Checkout Module for Adobe Commerce / Magento
sidebar_position: 1
description: Checkout Module for Adobe Commerce allows customers to choose Vipps, VISA or MasterCard as a payment method directly in the checkout.
pagination_next: null
pagination_prev: null
---
END_METADATA -->

# Checkout Module for Adobe Commerce / Magento

![Support and development by Vaimo ](./docs/images/vaimo.svg#gh-light-mode-only)![Support and development by Vaimo](./docs/images/vaimo_dark.svg#gh-dark-mode-only)

![Vipps](./docs/images/vipps.png) *Available for Vipps.*

![MobilePay](./docs/images/mp.png) *The availability for MobilePay has not been finalized yet.*

*This plugin is built and maintained by [Vaimo](https://www.vaimo.com/) and is hosted on [GitHub](https://github.com/vippsas/vipps-checkout-magento).
For support, [submit an issue](https://github.com/vippsas/vipps-checkout-magento/issues) on GitHub.*

<!-- START_COMMENT -->
💥 Please use the plugin pages on [https://developer.vippsmobilepay.com](https://developer.vippsmobilepay.com/docs/plugins-ext/checkout-magento/). 💥
<!-- END_COMMENT -->

Vipps MobilePay is the leading provider of smart payments in the Nordic region. Our goal is to engage and excite people every day through world-class simplification. We are collectively owned by 110 banks in Norway and provide a broad range of payment and digital identification services. The Vipps mobile wallet has achieved worldwide attention, and is widely recognized for its success, having achieved nearly 80 percent market penetration in the Norwegian market.

*Checkout Module for Adobe Commerce* allows customers to choose Vipps, VISA or MasterCard as a payment method directly in the checkout.

## Requirements/Prerequisites

* Adobe Commerce 2.2+
  * [System Requirements](https://developer.adobe.com/commerce/docs/)
* SSL is installed on your site and active on the Checkout page
* Supported protocols HTTP1/HTTP1.1
  * Adobe Commerce relies on the [Zend Framework](https://framework.zend.com), which does not support HTTP/2
  * HTTP/1.1 must therefore be "forced", typically by using [CPanel](https://api.docs.cpanel.net/) or similar
* A verified Vipps MobilePay Checkout merchant account

## Account and Pricing

Use of the Vipps Checkout module requires an agreement with Vipps MobilePay. Additional fees apply.
To sign up for an agreement, register on [https://portal.vippsmobilepay.com](https://portal.vippsmobilepay.com/register/vippscheckout).

## Feature Highlights

With this extension, your customers will be able to choose Vipps, VISA, or MasterCard as a payment method directly in the checkout. There is no need to go via a third party payment method. The customer is identified in the Checkout, and his/her address and contact details will be available to the webshop during the payment process. Shipping options will be displayed in the checkout for the customer to choose from.

## Security Features

Vipps MobilePay offers PCI DSS compliant payment services. No cardholder data or sensitive authentication data is stored in Adobe Commerce. All PCI DSS relevant information is sent directly to PCI DSS validated servers. This means that Vipps payments services will not increase the merchant's PCI DSS scope. Additionally, no personal information is stored in Adobe Commerce.

## Support

For problems with the plugin,
[submit an issue](https://github.com/vippsas/vipps-checkout-magento/issues) on GitHub.
