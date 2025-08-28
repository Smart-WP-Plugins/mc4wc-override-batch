=== Mailchimp for WooCommerce – Override & Batch Subscribe ===
Contributors: jeetsaha86, wecodify, smartwpplugins
Requires at least: 6.5
Tested up to: 6.6
Requires PHP: 7.4
Requires Plugins: mailchimp-for-woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: mailchimp, woocommerce, newsletter, batch, subscribe, admin

Admin-only subscribe tools for Mailchimp for WooCommerce: Add New User checkbox + batch subscribe via MC4WC queue. No extra meta, no API fallback.

== Description ==

**Mailchimp for WooCommerce – Override & Batch Subscribe** gives store admins fast, conflict-free ways to opt users into marketing email **using the official Mailchimp for WooCommerce (MC4WC) integration**.

- **Add-user opt-in:** A checkbox on **Users → Add New** sets `mailchimp_woocommerce_is_subscribed = '1'` and queues MC4WC’s `MailChimp_WooCommerce_User_Submit` job.
- **Batch subscribe tool:** In **WooCommerce → Status → Tools**, run a one-click action that finds users whose Mailchimp flag is **missing/empty/‘0’** (receive order updates only) and subscribes them in bulk. The tool **skips** users marked **‘unsubscribed’** or already **‘1’**.
- **No extra overhead:** Writes only the MC4WC flag and enqueues MC4WC jobs—**no custom consent/audit meta** and **no direct Mailchimp API calls**.
- **Plays nice with MC4WC:** All syncing and double opt-in behavior remain under MC4WC’s control via Action Scheduler.

**Key features**
- Admin checkbox on Add New User (optional filter to also show on Edit/Profile).
- Batch subscribe tool with smart targeting (0/missing) and safe skips (1/unsubscribed).
- Idempotent by design; re-runs won’t requeue already-subscribed users.
- Zero direct Mailchimp API usage; leverages MC4WC’s public queue.
- Minimal writes for large user tables.

**Requirements**
- WooCommerce
- Mailchimp for WooCommerce (connected)
- WordPress 6.5+ (uses plugin dependency header) or the included activation guard on older versions

== Installation ==

1. Install and activate **Mailchimp for WooCommerce** and connect your store.
2. Upload this plugin to `/wp-content/plugins/` (folder name: `mc4wc-override-batch`) and activate it.
3. (Optional) To show the checkbox on Edit User/Profile screens:
   `add_filter('swp_mc_show_on_edit', '__return_true');`

== Frequently Asked Questions ==

= Does this call the Mailchimp API directly? =
No. It sets the MC4WC user flag and queues MC4WC’s own job. MC4WC handles all API calls and syncing.

= Will it conflict with Mailchimp for WooCommerce? =
No. It uses MC4WC’s public queue (`MailChimp_WooCommerce_User_Submit` via `mailchimp_handle_or_queue`) and mirrors how MC4WC expects admin subscriptions to occur.

== Changelog ==

= 1.0.0 =
* Initial release.