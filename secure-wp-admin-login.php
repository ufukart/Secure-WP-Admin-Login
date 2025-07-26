<?php
/**
 * Plugin Name: Secure WP Admin Login
 * Plugin URI: https://wordpress.org/plugins/secure-wp-admin-login/
 * Description: Change wp-admin login to whatever you want whatever you want. Example: https://www.example.com/my-login. Go under Settings and then click on "Permalinks" and change your URL under "Secure WP Admin Login".
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: UfukArt
 * Author URI: https://www.zumbo.net
 * Text Domain: secure-wp-admin-login
 * Domain Path: /languages
 * Network: true
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Forked Plugin: Change WP Admin Login (v1.8)
 * Original Author: Saad Iqbal (saad@objects.ws)
 * Modified and maintained by Zumbo (2025)
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

// Check PHP version compatibility
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>Secure WP Admin Login:</strong> ' . 
             esc_html__('This plugin requires PHP 7.4 or higher. Your current PHP version is ', 'secure-wp-admin-login') . 
             esc_html(PHP_VERSION) . '</p></div>';
    });
    return;
}

// Check WordPress version compatibility
if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>Secure WP Admin Login:</strong> ' . 
             esc_html__('This plugin requires WordPress 5.0 or higher.', 'secure-wp-admin-login') . '</p></div>';
    });
    return;
}

/**
 * Constants
 */
if (!defined('Secure_WP_Admin_Login_Version')) {
    define('Secure_WP_Admin_Login_Version', '1.0.0');
}

if (!defined('Secure_WP_Admin_Login_Name')) {
    define('Secure_WP_Admin_Login_Name', 'Secure WP Admin Login');
}

if (!defined('Secure_WP_Admin_Login_Path')) {
    define('Secure_WP_Admin_Login_Path', plugin_dir_path(__FILE__));
}

if (!defined('Secure_WP_Admin_Login_Base_Uri')) {
    define('Secure_WP_Admin_Login_Base_Uri', plugin_dir_url(__FILE__));
}


/**
 * Include main class
 */
require_once Secure_WP_Admin_Login_Path . 'includes/class-secure-wp-admin-login.php';

/**
 * Plugin activation hook
 */
register_activation_hook(__FILE__, function() {
    if (class_exists('Secure_WP_Admin_Login')) {
        $plugin = new Secure_WP_Admin_Login();
        $plugin->activate();
    }
});

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, function() {
    // Clean up any temporary options or cache if needed
    wp_cache_flush();
});

/**
 * Plugin uninstall hook
 */
register_uninstall_hook(__FILE__, array('Secure_WP_Admin_Login', 'uninstall'));