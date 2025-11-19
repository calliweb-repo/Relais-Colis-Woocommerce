=== Relais Colis Officiel ===
Contributors: Calliweb
Donate link: https://www.relaiscolis.com/
Tags: Relais Colis, WooCommerce
Requires at least: 6.6
Tested up to: 6.8
Stable tag: 2.0.9
Requires PHP: 8.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
URI: https://www.relaiscolis.com/

Integrate Relais Colis delivery services directly into your WooCommerce store. Offer pickup point and home delivery options, generate shipping labels, track parcels automatically, and notify customers every step of the way with free setup assistance included.

== Description ==

The **100% Free** Relais Colis Solution, **the most affordable offer on the market starting at €3.33 (excl. tax), rated 9.4/10** on Verified Reviews.

Relais Colis offers innovative solutions tailored to your needs and accessible to everyone, for all parcel sizes to Relais Colis pickup points or home delivery for parcels over 20 kg.

**Relais Colis Shipping for WooCommerce** is a shipping plugin that integrates Relais Colis delivery services directly into your WooCommerce store.

With this plugin, you can:
- Offer customers the option to choose a Relais Colis pickup point during checkout.
- Display an interactive map of pickup locations.
- Generate and print shipping labels.
- Automatically synchronize shipment tracking information.
- Send customer notifications from dispatch to delivery.
- Get free setup assistance. We'll help you install the module at no additional cost. Simply send a request to: [relaiscolissurmonsite@relaisccolis.com](mailto:relaiscolissurmonsite@relaisccolis.com)

Whether you’re a small online shop or a large retailer, this module simplifies your logistics process with Relais Colis services seamlessly integrated into WooCommerce.

This plugin is developed and maintained by **Calliweb**, a digital agency.

The module is distributed under the [GNU General Public License v3.0 (GPLv3)](https://www.gnu.org/licenses/gpl-3.0.en.html), which allows you to use, modify, and distribute it freely under its terms.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install it directly via the WordPress Plugin Directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WooCommerce > Settings > Shipping > Relais Colis** to configure your API credentials and preferences.
4. Set up shipping zones and methods to include Relais Colis.
5. Save your settings and start offering Relais Colis delivery options to your customers.

== Frequently Asked Questions ==

**FAQ**: [Besoin d'aide FAQ - Relais Colis](https://www.relaiscolis.com/faq)

== Screenshots ==

1. Admin Page configuration: screenshot-1.png
2. Admin bar: screenshot-2.png

== Changelog ==

= 2.0.9 (2025-11-07) =
* Added: Display new api errors
* Added: Better handle error when save relay
* Fixed: Handle special chars in store name
* Fixed: Handle different units of weight in csv export
* Fixed Display free on shipping choice on old checkout
* Fixed Securing the AJAX call that registers the relay
* Fixed Handle multi shipping select block

= 2.0.8 (2025-07-22) =
* Added: Change order statuses at same time as RC statuses during cron tasks
* Added: Waiting block when save relay
* Fixed: Update shipping method check in WC_Relacoof_Order_Packages_Manager to use dedicated method for Relais Colis identification
* Fixed: No map button when change postal
* Fixed: Adapte logo display to more themes

= 2.0.7 (2025-07-15) =
* Fixed: Loading font awesome

= 2.0.6 (2025-07-09) =
* Changed: App name
* Fixed: Translation loading

= 2.0.5 (2025-07-02) =
* Changed: App name
* Changed: Domain name
* Fixed: Translations

= 2.0.4 (2025-06-25) =
* Fixed: Total weight conversion

= 2.0.3 (2025-06-24) =
* Fixed: Plugin Check error
* Changed: Add Relay ID to shipping info
* Changed: Change treeshold text
* Changed: Remove Monolog

= 2.0.2 (2025-05-27) =
* Fixed: Fix Relay address saved as customer address
* Fixed: Fix tariff grid gap when use VAT
* Changed: Change Wording block retour
* Changed: Change display WC selected weigth units in tariff grid

= 2.0.1 (2025-05-20) =
* Fixed: Fix multi-render select with Paypal

= 2.0.0 (2025-05-15) =
* Initial release

== External services ==

This plugin connects to the following external services:

1. **Relais Colis API**  
   - **Purpose**: Used to retrieve relay points, shipping status, and other logistics information for orders.  
   - **Data sent**: Shipping address, postal code, city, and sometimes order references.  
   - **When**: When searching for a relay point or tracking a parcel.  
   - **Service**: [Relais Colis](https://www.relaiscolis.com/)  
   - **Terms of Service**: [https://www.relaiscolis.com/page/mentions-legales](https://www.relaiscolis.com/page/mentions-legales)  
   - **Privacy Policy**: [https://www.relaiscolis.com/page/donnees-personnelles](https://www.relaiscolis.com/page/donnees-personnelles)

2. **Google Maps**  
   - **Purpose**: Used to display the location of relay points on a map.  
   - **Data sent**: The address of the selected relay point.  
   - **When**: When the user clicks to view a relay point on Google Maps.  
   - **Service**: [Google Maps](https://maps.google.com/)  
   - **Terms of Service**: [https://maps.google.com/help/terms_maps/](https://maps.google.com/help/terms_maps/)  
   - **Privacy Policy**: [https://policies.google.com/privacy](https://policies.google.com/privacy)

3. **API Adresse (data.gouv.fr)**  
   - **Purpose**: Used to autocomplete and geolocate addresses for relay point search.  
   - **Data sent**: The address entered by the user.  
   - **When**: When searching for a relay point by address.  
   - **Service**: [API Adresse - data.gouv.fr](https://api-adresse.data.gouv.fr/)  
   - **Terms of Service**: [https://www.data.gouv.fr/pages/legal/legal-notice](https://www.data.gouv.fr/pages/legal/legal-notice)  
   - **Privacy Policy**: [https://www.data.gouv.fr/pages/suivi](https://www.data.gouv.fr/pages/suivi)