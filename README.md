<!-- START_METADATA
---
title: Vipps Checkout Module for Magento 2
sidebar_position: 1
pagination_next: null
pagination_prev: null
---
END_METADATA -->

# Vipps Checkout Module for Magento 2

![Support and development by Vaimo ](./docs/images/vaimo.svg#gh-light-mode-only)![Support and development by Vaimo](./docs/images/vaimo_dark.svg#gh-dark-mode-only)

![Vipps](./docs/images/vipps.png) *Available for Vipps.*

![MobilePay](./docs/images/mp.png) *Availability for MobilePay has not yet been determined.*


*This plugin is built and maintained by [Vaimo](https://www.vaimo.com/) and is hosted on [GitHub](https://github.com/vippsas/vipps-checkout-magento).*

<!-- START_COMMENT -->
💥 Please use the plugin pages on [https://developer.vippsmobilepay.com](https://developer.vippsmobilepay.com/docs/plugins-ext/checkout-magento/). 💥
<!-- END_COMMENT -->
Vipps is the leading provider of smart payments in the Nordic region. Our goal is to engage and excite people every day through world-class simplification. We are collectively owned by 110 banks in Norway and provide a broad range of payment and digital identification services. The Vipps mobile wallet has achieved worldwide attention, and is widely recognized for its success, having achieved nearly 80 percent market penetration in the Norwegian market.

## Requirements/Prerequisites

* Magento 2.2+
  * [Magento 2 System Requirements](http://devdocs.magento.com/magento-system-requirements.html)
* SSL is installed on your site and active on the Checkout page
* Supported protocols HTTP1/HTTP1.1
  * Magento relies on the [Zend Framework](https://framework.zend.com), which does not support HTTP/2
  * HTTP/1.1 must therefore be "forced", typically by using [CPanel](https://documentation.cpanel.net/display/EA4/Apache+Module%3A+HTTP2) or similar
* A verified Vipps Checkout merchant account - [sign up here](https://portal.vipps.no/register/vippscheckout)

## Account & Pricing

Use of Vipps Checkout module requires an agreement with Vipps. Additional fees apply.
Log in and [register here to sign up](https://portal.vipps.no/register/vippscheckout) for an agreement.

## Feature Highlights

With this extension, your customers will be able to choose Vipps, VISA or MasterCard as a payment method directly in the checkout. There is no need to go via a third party payment method. The customer is identified in the Checkout, and his/her address/contact details will be available to the webshop during the payment process, and shipping options will be displayed in the Checkout, for the customer to choose from.

## Security Features

Vipps offers PCI DSS compliant payment services. No cardholder data or sensitive authentication data is stored in Magento. All PCI DSS relevant information is sent directly to PCI DSS validated servers. This means that Vipps payments services will not increase the merchant's PCI DSS scope. Additionally, no personal information is stored in Magento.
