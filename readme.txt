=== Affiliates Manager WooCommerce Subscription Integration ===
Contributors: wp.insider, affmngr
Donate link: https://wpaffiliatemanager.com/
Tags: affiliate, affiliates, affiliates manager, integration, woocommerce, checkout, subscription
Requires at least: 3.8
Tested up to: 6.6
Stable tag: 1.1.5
License: GPLv2 or later

Process an affiliate commission via Affiliates Manager plugin after a WooCommerce subscription payment

== Description ==

This addon allows you to integrate WooCommerce subscription extension with the Affiliates Manager plugin.

When a recurring payment is charged, this addon will check to see if the original sale was referred to your site by an affiliate. It will then give appropriate commission to the affiliate.

This addon requires the [Affiliates Manager Plugin](https://wordpress.org/plugins/affiliates-manager/).

Read the usage documentation [here](https://wpaffiliatemanager.com/affiliates-manager-woocommerce-subscription-integration/).

== Installation ==

1. Upload `affiliates-manager-woocommerce-subscription-integration` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==
None

== Screenshots ==
Check the following page for screenshots
https://wpaffiliatemanager.com/

== Changelog ==

= 1.1.5 =
* Additional check to ensure the existence of parent orders.

= 1.1.4 =
* Fees are deducted when calculating commissions.

= 1.1.3 =
* Updated the integration to be compatible with WooCommerce CRUD objects.

= 1.1.2 =
* Fixed an issue where a commission was being awarded for both the parent and the switch orders.

= 1.1.1 =
* Fixed an issue where the email address was not getting captured for new subscriptions.

= 1.1.0 =
* Capture the email address of the customer.

= 1.0.9 =
* Fixed an issue where the addon was not being able to retrieve the referrer for a coupon.

= 1.0.8 =
* Updated the integration code to make it compatible with the latest version of WooCommerce Subscriptions.

= 1.0.7 =
* Added an option to only reward a commission on the first payment.

= 1.0.6 =
* Updated the integration code so it works with a subscription upgrade/downgrade.

= 1.0.5 =
* Updated the integration code to make it compatible with the latest version of the WooCommerce subscription addon.

= 1.0.4 =
* Subscription tracking option is now also integrated with [WooCommerce coupons addon](https://wpaffiliatemanager.com/tracking-affiliate-commission-using-woocommerce-coupons-or-discount-codes/)
* Rebill payments now also award affiliate commission for it

= 1.0.3 =
* Made some improvements to the WooCommerce subscription tracking option

= 1.0.2 =
* fixed a function name

= 1.0.1 =
* First commit to wordpress.org

== Upgrade Notice ==
None
