=== Secure WP Admin Login ===
Contributors: ufukart
Donate link: https://www.paypal.com/donate/?business=53EHQKQ3T87J8&no_recurring=0&currency_code=USD
Tags: login url, wp admin, change wp login, security, custom login
Author: UfukArt
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Change the default WordPress login URL to something custom. Lightweight, simple, and free from bloat.


== Description ==
= Secure WP Admin Login =

Secure WP Admin Login lets you change the default WordPress login URL (`wp-login.php`) to a custom path of your choosing. This makes it harder for bots and attackers to find your login page.

There is no .htaccess modification, no rewrite rules, no database bloat, and no extra requests. It’s a clean solution.

Lightweight and focused — no branding, ads, or upsells. Just what you need to make brute-force attacks a bit more difficult.

== Features ==

* Change the login URL from `wp-login.php` to a custom path.
* Automatically redirects access attempts to the default login to 404 or home (configurable).
* Works with most themes and plugins.
* Clean and minimal — no performance hit, no dependencies.


== How it works ==

1. Go to `Settings` → `Permalinks`
2. Scroll down to "Secure WP Admin Login Settings"
3. Define a custom login slug (e.g., `/my-secret-login`)
4. Optionally define a redirect URL for unauthorized access attempts

**Important:** Bookmark your new login URL. If you forget it, delete the plugin folder via FTP.

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/secure-wp-admin-login`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your login slug and redirect settings under `Settings > Permalinks`

== Frequently Asked Questions ==

= What happens if I forget the custom login URL? =
You will need to disable the plugin via FTP or your hosting file manager by renaming the plugin folder. You can also manually reset the `secure_login_slug` option in the database.

= Does it work with TranslatePress? =
Yes, but select NO for "Use a subdirectory for the default language"

= Is it compatible with Multisite? =
Yes. Each site must configure its own login slug separately under its own Permalink settings.

= Does it work with BuddyBoss or BuddyPress? =
No. These plugins override wp-admin routing.

== Changelog ==

= 1.0.0 =
### Initial Release
* Forked from Secure WP Admin Login v1.8 by Saad Iqbal
* Added nonce verification and input sanitization
* Implemented XSS and CSRF protection
* Improved PHP and WordPress compatibility
* Refactored code to follow PSR-4 and modern PHP standards

== Support ==

Found a bug or need help? Open an issue on the [GitHub repo](https://github.com/ufukart/secure-wp-admin-login) or ask on the [WordPress Support Forum](https://wordpress.org/support/).

Love this plugin? Please [leave a review](https://wordpress.org/support/plugin/secure-wp-admin-login/reviews/).
