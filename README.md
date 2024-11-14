=== Fixed Attribute Price Adjustments ===
Contributors: Patryk Czemarnik
Tags: woocommerce, price adjustments, attributes, variable products
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 2.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds fixed amount or percentage price increases to WooCommerce products based on selected attributes.

== Description ==

Fixed Attribute Price Adjustments is a WooCommerce plugin that allows you to add fixed amount or percentage-based price adjustments to product attributes. This is particularly useful for variable products where certain attributes may increase the product's price.

**Features:**

- Add fixed amount or percentage price adjustments to any product attribute term.
- Display price adjustments next to attribute terms on the product page.
- Compatible with variation swatch plugins like Swatches Variations by GetWooPlugins.
- Adjusts the product price dynamically based on selected attributes.
- Easy-to-use interface integrated into WooCommerce attributes settings.

== Installation ==

1. **Upload the Plugin Files:**

   - Upload the `fixed-attribute-price-adjustments` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.

2. **Activate the Plugin:**

   - Activate the plugin through the 'Plugins' screen in WordPress.

3. **Configure Plugin Settings:**

   - Go to **WooCommerce > Fixed Attribute Price Adjustments** to configure the plugin settings.
   - Enable or disable the display of price adjustments next to attribute terms.

4. **Set Price Adjustments for Attributes:**

   - Navigate to **Products > Attributes**.
   - Edit an attribute and set the price adjustment for each term.
     - You can choose between a fixed amount or a percentage.
     - Enter the value for the price adjustment.

5. **Test the Functionality:**

   - Visit a variable product page that uses the adjusted attributes.
   - Confirm that price adjustments appear next to the attribute terms.
   - Add the product to the cart to ensure the price adjustment is applied.

== Frequently Asked Questions ==

**Q1: Can I use this plugin with any WooCommerce theme?**

A1: Yes, the plugin is designed to work with any WooCommerce-compatible theme. However, if you experience issues, please check for theme conflicts or contact support.

**Q2: Is this plugin compatible with variation swatch plugins?**

A2: Yes, the plugin is compatible with variation swatch plugins like Swatches Variations by GetWooPlugins. It uses standard WooCommerce filters to modify attribute term names.

**Q3: How do I display the price adjustment as a percentage instead of a fixed amount?**

A3: When editing an attribute term, select "Percentage" as the Adjustment Type and enter the percentage value in the Price Adjustment field.

**Q4: The price adjustments are not displaying correctly. What should I do?**

A4: Ensure that:

- The plugin is activated.
- Price adjustments are set for the attribute terms.
- The Display Price Adjustments option is enabled in the plugin settings.
- There are no conflicts with other plugins or your theme.

If the issue persists, enable debugging and check the `debug.log` for errors.

== Changelog ==

= 2.8 =
* Adjusted price formatting to display the currency symbol on the right side after a space.
* Improved compatibility with variation swatch plugins.
* Minor bug fixes and code optimizations.

= 2.7 =
* Modified price formatting to display adjustments as plain text without HTML tags.
* Ensured compatibility with themes and plugins that handle their own formatting.

= 2.6 =
* Added compatibility with Swatches Variations plugin from GetWooPlugins.
* Switched to using `woocommerce_variation_option_name` filter for better integration.

= 2.5 =
* Created a dedicated settings page under WooCommerce menu.
* Fixed issues with the settings not appearing in WooCommerce settings.

= 2.4 =
* Added option to enable or disable the display of price adjustments next to attribute terms.
* Implemented JavaScript to update product price dynamically based on selected attributes.

= 2.0 =
* Added support for percentage-based price adjustments.
* Improved the admin interface for setting price adjustments on attribute terms.

= 1.0 =
* Initial release of the plugin.
* Allows adding fixed amount price adjustments to product attributes.

== Upgrade Notice ==

= 2.8 =
Updated to version 2.8. Adjusted the display of the currency symbol in price adjustments. Please update to enjoy the new features and improvements.

== Additional Notes ==

- **Support:** For support, please visit BGLAM (https://bglam.pl).
- **Contribute:** Interested in contributing? Check out the plugin repository on [GitHub](https://github.com/patrixiooo/Fixed-Attribute-Price-Adjustments-for-Woocommerce).

== License ==

This plugin is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License version 2 as published by the Free Software Foundation.
