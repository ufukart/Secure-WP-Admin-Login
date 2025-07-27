# 🔒 Secure WP Admin Login

Change the default WordPress login URL (`wp-login.php`) to something custom. Lightweight, simple, and free from bloat.

[![Donate](https://img.shields.io/badge/Donate-PayPal-blue.svg)](https://www.paypal.com/donate/?business=53EHQKQ3T87J8&no_recurring=0&currency_code=USD)

---

## 🧾 Plugin Info

- **Contributors:** [ufukart](https://github.com/ufukart)  
- **Tags:** login url, wp admin, change wp login, security, custom login  
- **Requires at least:** WordPress 5.0  
- **Tested up to:** WordPress 6.8  
- **Requires PHP:** 7.4  
- **Stable tag:** 1.0.0  
- **License:** GPL-2.0+  
- **License URI:** [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

---

## 📌 Features

- Change the login URL from `wp-login.php` to a custom path.
- Automatically redirect access attempts to the default login to 404 or home (your choice).
- Works with most themes and plugins.
- No performance hit — no JavaScript, no CSS, no extra HTTP requests.
- No .htaccess rewrite rules or database bloat.

---

## ⚙️ How It Works

1. Go to `Settings → Permalinks`
2. Scroll down to **Secure WP Admin Login Settings**
3. Define your custom login slug (e.g., `/my-secret-login`)
4. Optionally define a redirect URL for unauthorized access attempts

> ⚠️ **Important:** Bookmark your new login URL. If you forget it, you will need to disable the plugin manually via FTP.

---

## 📥 Installation

1. Upload the plugin files to `/wp-content/plugins/secure-wp-admin-login`
2. Activate the plugin from the **Plugins** menu in WordPress
3. Go to `Settings → Permalinks` to configure your custom login slug

---

## ❓ FAQ

### What happens if I forget the custom login URL?

Disable the plugin via FTP by renaming or deleting the plugin folder.  
Alternatively, you can reset the `secure_login_slug` option directly from the database (e.g., via phpMyAdmin).

### Does it work with TranslatePress?

Yes. But you **must** select **"NO"** for the "Use a subdirectory for the default language" setting.

### Is it compatible with Multisite?

Yes, but each site in the network must configure its own login slug under its own Permalink settings.

### Does it work with BuddyBoss or BuddyPress?

**No.** These plugins override wp-admin routing, which conflicts with this plugin’s behavior.

---

## 📝 Changelog

### 1.0.0

- Forked from Secure WP Admin Login v1.8 by Saad Iqbal  
- Added nonce verification and input sanitization  
- Implemented XSS and CSRF protection  
- Improved PHP and WordPress compatibility  
- Refactored code to follow PSR-4 and modern PHP standards  

---

## 🧑‍💻 Support

- Report issues or request features on [GitHub](https://github.com/ufukart/secure-wp-admin-login/issues)
- Ask questions on the [WordPress Support Forum](https://wordpress.org/support/plugin/secure-wp-admin-login/)
- Like the plugin? [Leave a review](https://wordpress.org/support/plugin/secure-wp-admin-login/reviews/)

---

## ❤️ Donate

If you find this plugin useful, consider supporting its development:  
👉 [Donate via PayPal](https://www.paypal.com/donate/?business=53EHQKQ3T87J8&no_recurring=0&currency_code=USD)
