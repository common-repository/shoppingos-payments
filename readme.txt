=== ShoppingOS Payments  ===
Contributors: shoppingosinc
Tags: woocommerce, shoppingos, payments, e-commerce, ecommerce
Requires at least: 5.8.0
Tested up to: 5.9.0
Stable tag: 2.0.3
Requires PHP: 7.2
WC requires at least: 5.0.0
WC tested up to: 5.9.0
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== DESCRIPTION ==
Account-2-Account payments made very simple.

Securely accept account-2-account payments, and allow customers to pay you directly from their bank account. Extra-low fees compared to card payments and no chargebacks.

Extended WooCommerce interface allows you to accept account-2-account payment together with other payment methods and manage transactions from one interface - your WordPress dashboard.

=== How to get started ===
- Create a merchant profile at [ShoppingOS Dashboard](https://service.shoppingos.com)
- Set up your Account Number and Sort Code
- Download and install the plugin [ShoppingOS Payments](https://wordpress.org/plugins/shoppingos-payments/)
- Configure App ID and App Secret received from the Dashboard
- Complete a test payment using the **test mode**
- Contact ShoppingOS so we can verify your shop and enable live access
- Start saving money

== Frequently Asked Questions ==
= What banks is ShoppingOS currently connected with? =
We currently partner with authorised [Payment Initiation Service Provider (PISP)](https://www.fca.org.uk/consumers/account-information-and-payment-initiation-services) which provides connectivity to all banks in United Kingdom.
= What is 'Pay with Bank'? =
This is a new online checkout option that lets customers pay merchants directly using the bank app or online mobile account without any intermediaries. Making online checkout easier, faster & more secure.
= What are the current Open banking security standards? =
We address the fundamental shortcomings of cards by strongly authenticating consumers via open banking technology, allowing customers authorise payment via Secure Customer Authentication (SCA) [see regulators page here](https://www.fca.org.uk/firms/strong-customer-authentication).
= Is 'Pay with bank' available on mobile & desktop? =
Yes, our modern online banking ux is simple and intuitive, driving superior conversion when compared to cards across mobile and desktop.
= How do I add my bank? =
Once you sign up on the [dashboard](https://service.shoppingos.com), you can add your business bank account and start accepting payments and issuing reverse payments.

== Installation ==
1. Upload plugin folder to to the "/wp-content/plugins/" directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Register at [ShoppingOS Merchant Dashboard](https://service.shoppingos.com) to obtain API credentials.
4. Save the API credentials in plugin settings.

= 2022.06.17  	- version 2.0.3 =
* Fix           - Bug fix on handling failed payments status after customer is redirected back from bank

= 2022.06.08  	- version 2.0.2 =
* Fix           - Bug fix on handling failed payments message

= 2022.06.01  	- version 2.0.1 =
* Fix           - Bug fix on missing admin notice with refund status

= 2022.05.24  	- version 2.0.0 =
* Add           - Automatic notes to order with explanation on failed payment attempt
* Add           - Requirements checks on activation
* Enhancement   - Better and way more stable refunds
* Fix           - Fix 'pending payment' status on succesful payment attempts
* Fix           - Fix callback url not working with disabled pretty permalinks
* Tweak         - Status updates to 'failed' after unsuccessful payment attempts
* Dev           - Increased response timeout for `send_*_callback` methods
* Dev           - Add coding standards
* Dev           - Add class autoload and namespaces, follow PSR-4
* Dev           - Multiple coding style improvements

= 2022.04.07  	- version 1.0.6 =
* Enhancement   - Add more banks for selections during checkout page
* Fix           - Fix plugin styles on checkout page
* Fix           - Display proper errors for incomplete payments/refunds
* Fix           - Fix invalid callback request for failed payments

= 2022.03.18  	- version 1.0.5 =
* Enhancement   - Add bank selection on checkout page
* Fix           - Fix a bug where complete payments didn't update status on Woocommerce

= 2022.03.18  	- version 1.0.4 =
* Enhancement   - Add payment progress bar
* Enhancement   - Collect user agent and customer checksum

= 2022.02.28  	- version 1.0.3 =
* Fix           - Fix error when accessing non-order pages

= 2022.02.28  	- version 1.0.2 =
* Tweak         - Made possible to enter decimals for refund
* Enhancement   - Add dashboard statistics widget
* Enhancement   - Add 'learn more' setting
* Enhancement   - Add possibility to filter orders by payment method

= 2022.02.20  	- version 1.0.1 =
* Tweak         - Update image sizes for mobile view
* Enhancement   - Improve plugin settings page
* Enhancement   - Add plugin settings links

= 1.0.0 =
* Initial release.
