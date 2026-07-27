# MCRPD Regenerate Download Permissions for WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Required-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPLv3-green.svg)](https://www.gnu.org/licenses/gpl-3.0.html)

Regenerate downloadable permissions for WooCommerce orders in batches via AJAX.

## Description

MCRPD Regenerate Download Permissions is a specialized utility for WooCommerce stores that need to regenerate download permissions for past orders.

Unlike the default WooCommerce tools, this plugin allows you to:

- **Target specific products**: Only regenerate permissions for orders containing specific Product IDs. Leave empty to process all downloadable products.
- **Target specific categories**: Filter by product categories that contain downloadable products (AND logic with Product IDs).
- **Target specific order statuses**: Choose which order statuses to process via a modern, user-friendly interface.
- **Date range filter**: Restrict processing to orders created within a specific date range (From / To).
- **Batch Processing**: Uses AJAX to process orders in configurable batches (default: 20, up to 500) to avoid server timeouts on large stores.
- **Resumable Processing**: If an error occurs mid-process, your progress is saved automatically. Use the **Continue** button to resume from where you left off.
- **Real-time Feedback**: View detailed logs and progress scanning/update counts in real-time.
- **Modern UI**: Clean, minimalist interface designed for clarity and ease of use.

## Use Cases

- You added a downloadable file to an existing product and need past purchasers to get access.
- You accidentally deleted permissions and need to restore them for completed orders.
- You want to refresh permissions across your entire catalog or for a specific date range.
- You use a dropshipping setup where permissions need to be refreshed based on status changes.

## Requirements

- WordPress 5.0 or higher
- WooCommerce (active)
- PHP 7.4 or higher

## Installation

1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **WooCommerce > Download Perms Regen**.
4. Enter Product IDs (comma-separated), select Product Categories, Order Statuses, or Date Range.
5. Save settings.
6. Click **Start regeneration**.

## Frequently Asked Questions

### Does it work with all products?

It works with any Simple or Variable product that has downloadable files configured. You can specify Product IDs, filter by Categories, or leave empty to process all downloadable products.

### What happens if I leave the Product IDs field empty?

If the Product IDs field is left empty, the plugin will process all downloadable products (both simple and variable).

### How do categories work with Product IDs?

When you select both Product IDs and categories, the plugin uses AND logic: only products that appear in the specified IDs AND belong to one of the selected categories will be processed.

### What happens if I stop the process?

You can stop the regeneration at any time using the **Stop** button. Your progress is saved automatically. Use the **Continue** button to resume from the last successful batch.

### Can I change the batch size?

Yes! The default batch size is 20 orders per request. You can increase it up to 500 in the settings.

## Screenshots

1. Admin Interface with settings, filters, and progress log.
2. Order Status and Category selection with modern pill design.

## Changelog

### 1.1.1
- **NEW**: Empty Product IDs field now processes all downloadable products instead of returning an error.
- **NEW**: Product Category filter (multiselect) to restrict processing to orders containing products from specific categories (only categories with downloadable products are shown).
- **NEW**: Date range filter (From / To) to restrict processing to orders created within a specific period.
- Filters work in combination using AND logic: Product IDs ∩ Categories ∩ Date Range.
- Updated description text and help descriptions across the admin interface.

### 1.1.0
- **NEW**: Configurable batch size (1–500, default: 20) with server capacity warning.
- **NEW**: "Continue" button to resume processing from the last successful batch after errors or stops.
- **NEW**: Progress is automatically saved to browser storage (expires after 5 hours).
- Improved error handling with informative messages about saved progress.

### 1.0.3
- Added compatibility with WordPress 7.0.
- Added `Requires Plugins: woocommerce` header for WordPress 6.5+ plugin dependencies.
- Renamed plugin to include "for WooCommerce" suffix.
- Security hardening in settings saving and AJAX requests.

### 1.0.0
- Initial release.
- AJAX batch processing to handle large volumes of orders.
- Filter by Product IDs.
- Modern, user-friendly UI for selecting Order Statuses.
- Real-time processing logs.

## Feedback & Suggestions

This plugin is in constant evolution. If you have ideas or suggestions, do not hesitate to contact me at **hola@devcristian.com**. Your collaboration is highly valued!

## Support

- [Donate via PayPal](https://www.paypal.com/paypalme/cristian18josue)

## License

This plugin is licensed under the [GPLv3](https://www.gnu.org/licenses/gpl-3.0.html).
