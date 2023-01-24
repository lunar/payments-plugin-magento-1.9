# Magento 1.9 plugin for Lunar

The software is provided “as is”, without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement.


## Supported Magento versions

* The plugin has been tested with most versions of Magento at every iteration. We recommend using the latest version of Magento, but if that is not possible for some reason, test the plugin with your Magento version and it would probably function properly.

## Third party modules compatibility
1. FME_QuickCheckout

## Installation

Once you have installed Magento, follow these simple steps:
1. Signup at [lunar.app](https://www.lunar.app) (it’s free)
1. Create an account
1. Create an app key for your Magento 1.9 website
1. Log in as administrator and upload the tgz file using the magento connect manager
    * If you have installed the plugin before, you may encounter issues as it has been renamed. If this happens please contact [support](https://www.lunar.app/en/personal/contact) and we will assist on fixing the issues.
1. After the plugin is installed go to the configuration screen : System-> Configuration (top menu)  -> Sales -> Payment Methods (sidebar menu) -> Lunar Payment
1. In this settings screen you need to  add the Public and App key that you can find in your Lunar account.


## Updating settings

Under the extension settings, you can:
 * Update the payment method text in the payment gateways list
 * Update the payment method description in the payment gateways list
 * Update the credit card logos that you want to show (you can change which one you accept under the lunar account).
 * Update the title that shows up in the payment popup
 * Update the popup description, choose whether you want to show the popup  (the cart contents will show up instead)
 * Add app/public keys
 * Change the capture type (Instant/Delayed)

## Payment management

### Capture

* In *instant* mode, the authorization takes place via the popup, while the capturing is done on the server right on the order checkout page, so you don't need to capture after.
* In *delayed* mode you can capture payments by creating an invoice from the order in question from magento. Leave the capture online on to capture the payment automatically.

### Refund

* Orders can only be refunded if they have been captured. If that is the case, you can create a credit memo from the invoice of the order. *All transactions take place in the amount converted to the currency the user selected. Because of that partial refund is not yet possible on magento*

### Void

* In delayed mode, you can click void to void the payment if when this hasn't been captured, on the order interface screen.


## Changelog

#### 1.0.0:
* initial version
