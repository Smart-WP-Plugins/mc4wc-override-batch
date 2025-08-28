# Mailchimp for WooCommerce – Override & Batch Subscribe

**Slug / Text Domain:** `mc4wc-override-batch`  
**Author:** [Smart WP Plugins](https://smartwpplugins.com/)  
**Contributors:** @jeetsaha86, @wecodify, @smartwpplugins  
**License:** GPL-2.0-or-later

Admin-only subscribe tools for **Mailchimp for WooCommerce**: an Add New User checkbox and a WooCommerce **Status → Tools** batch action that subscribe users using **MC4WC’s own queue**. No direct Mailchimp API calls and no custom audit meta.

---

## ✨ Features

- **Add-user opt-in** (Users → Add New)  
  When checked, the user is marked as subscribed locally and queued for MC4WC sync.

- **Batch subscribe tool** (WooCommerce → Status → Tools)  
  One-click action to find users currently **not subscribed** (meta missing/empty/`'0'`) and subscribe them in bulk.  
  Skips users marked **`'1'` (subscribed)** or **`'unsubscribed'`**.

- **Zero extra meta**  
  We only set MC4WC’s own flag: `mailchimp_woocommerce_is_subscribed = '1'`, then queue the official job.

- **Works with MC4WC, not against it**  
  Uses `MailChimp_WooCommerce_User_Submit` + `mailchimp_handle_or_queue()` (Action Scheduler).

---

## 🔧 Requirements

- WordPress **6.5+** (uses “Requires Plugins” dependency)  
  *(An activation guard is included for older WordPress versions.)*
- PHP **7.4+**
- WooCommerce
- **Mailchimp for WooCommerce** (installed, active, and **connected**)

---

## 📦 Installation

1. Ensure **Mailchimp for WooCommerce** is installed, active, and connected.
2. Upload the folder `mc4wc-override-batch` to `/wp-content/plugins/`.
3. Activate **Mailchimp for WooCommerce – Override & Batch Subscribe**.
4. That’s it—no configuration screens.

> If MC4WC isn’t active, activation is blocked with a clear message.

---

## ▶️ Usage

### 1) Add New User checkbox
- Go to **Users → Add New**.
- Tick **“Subscribe to our newsletter”** and create the user.
- On save, the plugin:
   - sets `mailchimp_woocommerce_is_subscribed = '1'`
   - queues `MailChimp_WooCommerce_User_Submit( $user_id, true )` via `mailchimp_handle_or_queue()`

> By default, we **do not** render our checkbox on Edit/Profile screens (MC4WC already provides controls there). To enable it, see [Filters](#-filters).

### 2) Batch subscribe tool
- Go to **WooCommerce → Status → Tools**.
- Run **“Subscribe all non-subscribed users (MC4WC)”**.
- The tool targets users where `mailchimp_woocommerce_is_subscribed` is:
   - **missing**, **empty string**, or **`'0'`** → **included**
   - **`'1'`** (**subscribed**) → **skipped**
   - **`'unsubscribed'`** → **skipped**
- The tool runs via **Action Scheduler**. Track progress in **WooCommerce → Status → Scheduled Actions**.

---

## ⚙️ Filters

- `swp_mc_show_on_edit` (bool)  
  Show our checkbox on **Edit User/Profile** screens (default: `false`).

  ```php
  // In a small must-use plugin or theme functions.php
  add_filter('swp_mc_show_on_edit', '__return_true');
