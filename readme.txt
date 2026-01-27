=== MCRPD Regenerate Download Permissions for woocommerce ===
Contributors: crleguizamon
Donate link: https://www.paypal.com/paypalme/cristian18josue
Tags: woocommerce, permissions, regenerate, downloads, batch
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Regenerate downloadable permissions for orders in batches via AJAX.

== Description ==

MCRPD Regenerate Download Permissions is a specialized utility  stores that need to regenerate download permissions for past orders.

Unlike the default WooCommerce tools, this plugin allows you to:
*   **Target specific products**: Only regenerate permissions for orders containing specific Product IDs.
*   **Target specific order statuses**: Choose which order statuses to process via a modern, user-friendly interface.
*   **Batch Processing**: Uses AJAX to process orders in batches of 20 to avoid server timeouts on large stores.
*   **Real-time Feedback**: View detailed logs and progress scanning/update counts in real-time.
*   **Modern UI**: Clean, minimalist interface designed for clarity and ease of use.

= Use cases =

*   You added a downloadable file to an existing product and need past purchasers to get access.
*   You accidentally deleted permissions and need to restore them for completed orders.
*   You use a dropshipping setup where permissions need to be refreshed based on status changes.

== Installation ==

1.  Upload the plugin folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **WooCommerce > Download Perms Regen**.
4.  Enter the Product IDs (comma-separated).
5.  Select the desired Order Statuses using the status pills.
6.  Save settings.
7.  Click "Start regeneration".

== Frequently Asked Questions ==

= Does it work with all products? =

It works with any Simple or Variable product that has downloadable files configured. You must specify the Product IDs in the settings.

= Can I use the plugin without WooCommerce? =

No, this plugin requires WooCommerce to be installed and active.

= What happens if I stop the process? =

You can stop the regeneration at any time using the "Stop" button. You can restart it later, but it will start from the beginning of the query (though reprocessing permissions is generally safe and idempotent).

== Feedback & Suggestions ==

This plugin is in constant evolution. If you have ideas or suggestions, do not hesitate to contact me at hola@devcristian.com. Your collaboration is highly valued!

== Screenshots ==

1.  Admin Interface with settings and progress log.
2.  Order Status selection with modern pill design.

== Changelog ==

= 1.0.0 =
*   Initial release.
*   AJAX batch processing to handle large volumes of orders.
*   Filter by Product IDs.
*   Modern, user-friendly UI for selecting Order Statuses.
*   Real-time processing logs.