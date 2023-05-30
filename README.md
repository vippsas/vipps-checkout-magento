# Vipps Checkout Module for Magento 2

This Vipps Checkout Module for Magento 2 is hosted on [GitHub](https://github.com/vippsas/vipps-checkout-magento).

Vipps is the leading provider of smart payments in the Nordic region. Our goal is to engage and excite people every day through world-class simplification. We are collectively owned by 110 banks in Norway and provide a broad range of payment and digital identification services. The Vipps mobile wallet has achieved worldwide attention, and is widely recognised for its success, having achieved nearly 80 percent market penetration in the Norwegian market.


## Requirements/Pre-requisites

* Magento 2.2+
   * [Magento 2 System Requirements](http://devdocs.magento.com/magento-system-requirements.html)
* SSL is installed on your site and active on the Checkout page
* Supported protocols HTTP1/HTTP1.1
   * Magento relies on the [Zend Framework](https://framework.zend.com), which does not support HTTP/2.
   * HTTP/1.1 must therefore be "forced", typically by using [CPanel](https://documentation.cpanel.net/display/EA4/Apache+Module%3A+HTTP2) or similar.
* A verified Vipps Checkout merchant account - [sign up here](https://portal.vipps.no/register/vippscheckout)
 
## Account & Pricing
Use of Vipps Checkout module requires an agreement with Vipps. Additional fees apply.
Log in and [register here to sign up](https://portal.vipps.no/register/vippscheckout) for an agreement.
 
## Feature Highlights
With this extension, your customers will be able to choose Vipps, VISA or MasterCard as a payment method directly in the checkout. There is no need to go via a third party payment method. The customer is identified in the Checkout, and his/her address/contact details will be available to the webshop during the payment process, and shipping options will be displayed in the Checkout, for the customer to choose from. 
 
## Security Features
Vipps offers PCI DSS compliant payment services. No cardholder data or sensitive authentication data is stored in Magento. All PCI DSS relevant information is sent directly to PCI DSS validated servers. This means that Vipps payments services will not increase the merchant's PCI DSS scope. Additionally, no personal information is stored in Magento.
 
### Vipps contact information

Please follow this [instruction](https://developer.vippsmobilepay.com/docs/vipps-developers/contact/) to contact us.

For plugin related issues, please submit an issue on the plugins GitHub repo [for Magento](https://github.com/vippsas/vipps-checkout-magento) or contact developer@vippsmobilepay.com.
