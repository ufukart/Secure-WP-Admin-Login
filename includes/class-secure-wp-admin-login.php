<?php
/**
 * Main plugin class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

if (!class_exists('Secure_WP_Admin_Login')) {

    class Secure_WP_Admin_Login {
        
        /**
         * @var bool Flag to track wp-login.php access
         */
        private $wp_login_php = false;
        
        /**
         * @var string Plugin version
         */
        private $version = '1.0.0';
        
        /**
         * @var array Cached forbidden slugs
         */
        private $forbidden_slugs_cache = null;

        /**
         * Constructor
         */
        public function __construct() {
            // Initialize hooks
            $this->init_hooks();
        }

        /**
         * Initialize WordPress hooks
         */
        private function init_hooks(): void {
            // Activation and uninstall hooks
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_notices', array($this, 'admin_notices'));
            add_action('network_admin_notices', array($this, 'admin_notices'));

            // Plugin action links
            add_filter('plugin_action_links_' . $this->get_basename(), array($this, 'plugin_action_links'));

            // Multisite support
            if (is_multisite() && $this->is_network_active()) {
                add_filter('network_admin_plugin_action_links_' . $this->get_basename(), array($this, 'plugin_action_links'));
                add_action('wpmu_options', array($this, 'wpmu_options'));
                add_action('update_wpmu_options', array($this, 'update_wpmu_options'));
            }

            // Core functionality hooks
            add_action('plugins_loaded', array($this, 'plugins_loaded'), 1);
            add_action('wp_loaded', array($this, 'wp_loaded'));

            // URL filtering hooks
            add_filter('site_url', array($this, 'site_url'), 10, 4);
            add_filter('network_site_url', array($this, 'network_site_url'), 10, 3);
            add_filter('wp_redirect', array($this, 'wp_redirect'), 10, 2);
            add_filter('site_option_welcome_email', array($this, 'welcome_email'));

            // Remove default redirect behavior
            remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
        }

        /**
         * Get plugin basename
         */
        private function get_basename(): string {
            return plugin_basename(dirname(__DIR__) . '/secure-wp-admin-login.php');
        }

        /**
         * Check if plugin is network active
         */
        private function is_network_active(): bool {
            if (!function_exists('is_plugin_active_for_network')) {
                require_once(ABSPATH . '/wp-admin/includes/plugin.php');
            }
            return is_plugin_active_for_network($this->get_basename());
        }

        /**
         * Check if using trailing slashes
         */
        private function use_trailing_slashes(): bool {
            return '/' === substr(get_option('permalink_structure'), -1, 1);
        }

        /**
         * Add or remove trailing slash based on permalink structure
         */
        private function user_trailingslashit(string $string): string {
            return $this->use_trailing_slashes() ? trailingslashit($string) : untrailingslashit($string);
        }

        /**
         * Handle template loading for blocked access
         */
        private function wp_template_loader(): void {
            global $pagenow;
            
            $pagenow = 'index.php';

            if (!defined('WP_USE_THEMES')) {
                define('WP_USE_THEMES', true);
            }

            wp();

            // Prevent infinite loops
            if (isset($_SERVER['REQUEST_URI']) && 
                $_SERVER['REQUEST_URI'] === $this->user_trailingslashit(str_repeat('-/', 10))) {
                $_SERVER['REQUEST_URI'] = $this->user_trailingslashit('/wp-login-php/');
            }

            require_once(ABSPATH . WPINC . '/template-loader.php');
            exit;
        }

        /**
         * Get the new login slug
         */
        private function new_login_slug(): string {
            $slug = get_option('swal_page');
            
            if (!$slug && is_multisite() && $this->is_network_active()) {
                $slug = get_site_option('swal_page', 'login');
            }
            
            return $slug ?: 'login';
        }

        /**
         * Get the new login URL
         */
        public function new_login_url(?string $scheme = null): string {
            if (get_option('permalink_structure')) {
                return $this->user_trailingslashit(home_url('/', $scheme) . $this->new_login_slug());
            } else {
                return home_url('/', $scheme) . '?' . $this->new_login_slug();
            }
        }

        /**
         * Plugin activation
         */
        public function activate(): void {
            add_option('swal_redirect', '1');
            delete_option('swal_admin');
            
            // Flush rewrite rules
            flush_rewrite_rules();
        }

        /**
         * Plugin uninstall
         */
		public static function uninstall(): void {
			if (is_multisite()) {
				$blogs = get_sites(['fields' => 'blog_id']);

				if ($blogs) {
					foreach ($blogs as $blog_id) {
						switch_to_blog($blog_id);
						delete_option('swal_page');
						delete_option('swal_redirect_field');
						delete_option('swal_redirect');
					}
					restore_current_blog();
				}

				delete_site_option('swal_page');
			} else {
				delete_option('swal_page');
				delete_option('swal_redirect_field');
				delete_option('swal_redirect');
			}

			flush_rewrite_rules();
		}

        /**
         * Network admin options
         */
        public function wpmu_options(): void {
            $current_value = get_site_option('swal_page', 'login');
            ?>
            <h3><?php esc_html_e('Secure WP Admin Login', 'secure-wp-admin-login'); ?></h3>
            <p><?php esc_html_e('This option allows you to set a networkwide default, which can be overridden by individual sites. Simply go to the site\'s permalink settings to change the url.', 'secure-wp-admin-login'); ?></p>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Networkwide default', 'secure-wp-admin-login'); ?></th>
                    <td>
                        <input id="swal-page-input" type="text" name="swal_page" value="<?php echo esc_attr($current_value); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Enter the slug for the custom login URL (e.g., "login", "signin", "access").', 'secure-wp-admin-login'); ?></p>
                    </td>
                </tr>
            </table>
            <?php
        }

        /**
         * Update network admin options
         */
        public function update_wpmu_options(): void {
			if (!current_user_can('manage_network_options') || !isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'secure-wp-admin-login-options')) {
				return;
			}

            $swal_page = isset($_POST['swal_page']) ? sanitize_text_field(wp_unslash($_POST['swal_page'])) : '';
            $swal_page = sanitize_title_with_dashes($swal_page);

            if ($swal_page && 
                strpos($swal_page, 'wp-login') === false && 
                !in_array($swal_page, $this->forbidden_slugs(), true)) {
                update_site_option('swal_page', $swal_page);
            }
        }

        /**
         * Admin initialization
         */
        public function admin_init(): void {
            // Add settings section
            add_settings_section(
                'secure-wp-admin-login-section',
                esc_html__('Secure WP Admin Login', 'secure-wp-admin-login'),
                array($this, 'swal_section_desc'),
                'permalink'
            );

            // Add login URL field
            add_settings_field(
                'swal-page',
                '<label for="swal-page">' . esc_html__('Login URL', 'secure-wp-admin-login') . '</label>',
                array($this, 'swal_page_input'),
                'permalink',
                'secure-wp-admin-login-section'
            );

            // Add redirect field
            add_settings_field(
                'swal_redirect_field',
                esc_html__('Redirect URL', 'secure-wp-admin-login'),
                array($this, 'swal_redirect_func'),
                'permalink',
                'secure-wp-admin-login-section'
            );

            // Handle form submission
            $this->handle_settings_update();
        }

        /**
         * Handle settings update with proper security checks
         */
        private function handle_settings_update(): void {
            if (!current_user_can('manage_options')) {
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update-permalink')) {
				return;
			}


            if (!isset($_POST['permalink_structure'])) {
                return;
            }

            // Handle redirect field
            if (isset($_POST['swal_redirect_field'])) {
                $redirect_field = sanitize_text_field(wp_unslash($_POST['swal_redirect_field']));
                $redirect_field = sanitize_title_with_dashes($redirect_field);
                update_option('swal_redirect_field', $redirect_field);
            }

            // Handle login page slug
            if (isset($_POST['swal_page'])) {
                $swal_page = sanitize_text_field(wp_unslash($_POST['swal_page']));
                $swal_page = sanitize_title_with_dashes($swal_page);

                if ($swal_page && 
                    strpos($swal_page, 'wp-login') === false && 
                    !in_array($swal_page, $this->forbidden_slugs(), true)) {
                    
                    if (is_multisite() && $swal_page === get_site_option('swal_page', 'login')) {
                        delete_option('swal_page');
                    } else {
                        update_option('swal_page', $swal_page);
                    }
                }
            }

            // Handle redirect after activation
            if (get_option('swal_redirect')) {
                delete_option('swal_redirect');

                $redirect_url = admin_url('options-permalink.php#swal-page-input');
                if (is_multisite() && is_super_admin() && $this->is_network_active()) {
                    $redirect_url = network_admin_url('settings.php#swal-page-input');
                }

                wp_safe_redirect($redirect_url);
                exit;
            }
        }

        /**
         * Section description
         */
        public function swal_section_desc(): void {
            if (is_multisite() && is_super_admin() && $this->is_network_active()) {
                printf(
                    '<p>%s</p>',
                    sprintf(
						/* Translators: %s is a link to the network settings page. */
                        esc_html__('To set a networkwide default, go to %s.', 'secure-wp-admin-login'),
                        '<a href="' . esc_url(network_admin_url('settings.php#swal-page-input')) . '">' . 
                        esc_html__('Network Settings', 'secure-wp-admin-login') . '</a>'
                    )
                );
            }
        }

        /**
         * Redirect field output
         */
        public function swal_redirect_func(): void {
            $value = get_option('swal_redirect_field', '');
            ?>
            <code><?php echo esc_html(trailingslashit(home_url())); ?></code>
            <input type="text" value="<?php echo esc_attr($value); ?>" name="swal_redirect_field" id="swal_redirect_field" class="regular-text" />
            <code>/</code>
            <p class="description">
                <strong><?php esc_html_e('If you leave the above field empty, the plugin will redirect to the website homepage.', 'secure-wp-admin-login'); ?></strong>
            </p>
            <?php
        }

        /**
         * Login page input field
         */
        public function swal_page_input(): void {
            $slug = $this->new_login_slug();
            
            if (get_option('permalink_structure')) {
                echo '<code>' . esc_html(trailingslashit(home_url())) . '</code> ';
                echo '<input id="swal-page-input" type="text" name="swal_page" value="' . esc_attr($slug) . '" class="regular-text" />';
                echo $this->use_trailing_slashes() ? ' <code>/</code>' : '';
            } else {
                echo '<code>' . esc_html(trailingslashit(home_url())) . '?</code> ';
                echo '<input id="swal-page-input" type="text" name="swal_page" value="' . esc_attr($slug) . '" class="regular-text" />';
            }
            
            echo '<p class="description">' . 
                 esc_html__('Enter a custom slug for your login URL (letters, numbers, and hyphens only).', 'secure-wp-admin-login') . 
                 '</p>';
        }

        /**
         * Admin notices
         */
        public function admin_notices(): void {
            global $pagenow;

            if (!is_network_admin() && 
                $pagenow === 'options-permalink.php' && 
                isset($_GET['settings-updated'])) {
                
                printf(
                    '<div class="updated"><p>%s</p></div>',
                    sprintf(
						/* Translators: %s is the new login page URL wrapped in a link. */
                        esc_html__('Your login page is now here: %s. Bookmark this page!', 'secure-wp-admin-login'),
                        '<strong><a href="' . esc_url($this->new_login_url()) . '">' . 
                        esc_html($this->new_login_url()) . '</a></strong>'
                    )
                );
            }
        }

        /**
         * Plugin action links
         */
        public function plugin_action_links(array $links): array {
            $settings_link = admin_url('options-permalink.php#swal-page-input');
            
            if (is_network_admin() && $this->is_network_active()) {
                $settings_link = network_admin_url('settings.php#swal-page-input');
            }

            array_unshift($links, '<a href="' . esc_url($settings_link) . '">' . 
                         esc_html__('Settings', 'secure-wp-admin-login') . '</a>');

            return $links;
        }

        /**
         * Handle plugins loaded hook
         */
        public function plugins_loaded(): void {
            global $pagenow;

            // Block wp-signup and wp-activate on single site
            if (!is_multisite()) {
                $request_uri = isset($_SERVER['REQUEST_URI']) ? rawurldecode(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))) : '';
                if (strpos($request_uri, 'wp-signup') !== false || 
                    strpos($request_uri, 'wp-activate') !== false) {
                    wp_die(esc_html__('This feature is not enabled.', 'secure-wp-admin-login'));
                }
            }

            $this->handle_login_page_access();
        }

        /**
         * Handle login page access logic
         */
        private function handle_login_page_access(): void {
            global $pagenow;
            
            if (!isset($_SERVER['REQUEST_URI'])) {
                return;
            }

            $request_uri = rawurldecode(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
            $request = wp_parse_url($request_uri);

            // Handle wp-login.php access
            if ((strpos($request_uri, 'wp-login.php') !== false ||
                 (isset($request['path']) && untrailingslashit($request['path']) === site_url('wp-login', 'relative'))) &&
                !is_admin()) {
                
                $this->wp_login_php = true;
                $_SERVER['REQUEST_URI'] = $this->user_trailingslashit('/' . str_repeat('-/', 10));
                $pagenow = 'index.php';
                
            } elseif ($this->is_custom_login_url($request)) {
                // Handle custom login URL
                $pagenow = 'wp-login.php';
                
            } elseif ((strpos($request_uri, 'wp-register.php') !== false ||
                      (isset($request['path']) && untrailingslashit($request['path']) === site_url('wp-register', 'relative'))) &&
                     !is_admin()) {
                
                $this->wp_login_php = true;
                $_SERVER['REQUEST_URI'] = $this->user_trailingslashit('/' . str_repeat('-/', 10));
                $pagenow = 'index.php';
            }
        }

        /**
         * Check if current request is for custom login URL
         */
        private function is_custom_login_url(array $request): bool {
            $login_slug = $this->new_login_slug();
            
            if (isset($request['path']) && 
                untrailingslashit($request['path']) === home_url($login_slug, 'relative')) {
                return true;
            }
            
            if (!get_option('permalink_structure') && 
                isset($_GET[$login_slug]) && 
                empty($_GET[$login_slug])) {
                return true;
            }
            
            return false;
        }

        /**
         * Handle wp_loaded hook
         */
        public function wp_loaded(): void {
            global $pagenow;

            // Redirect admin access for non-logged-in users
            if (is_admin() && !is_user_logged_in() && !defined('DOING_AJAX') && !defined('DOING_CRON')) {
                $redirect_field = get_option('swal_redirect_field', '');
                $redirect_url = $redirect_field ? '/' . $redirect_field : '/';
                wp_safe_redirect($redirect_url);
                exit;
            }

            $this->handle_login_redirects();
        }

        /**
         * Handle login page redirects
         */
        private function handle_login_redirects(): void {
            global $pagenow;
            
            if (!isset($_SERVER['REQUEST_URI'])) {
                return;
            }

            $request = wp_parse_url(rawurldecode(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))));

            // Handle permalink structure redirects
            if ($pagenow === 'wp-login.php' &&
                isset($request['path']) &&
                $request['path'] !== $this->user_trailingslashit($request['path']) &&
                get_option('permalink_structure')) {
                
                $query_string = isset($_SERVER['QUERY_STRING']) ? sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING'])) : '';
                $redirect_url = $this->user_trailingslashit($this->new_login_url());
                
                if (!empty($query_string)) {
                    $redirect_url .= '?' . $query_string;
                }
                
                wp_safe_redirect($redirect_url);
                exit;
            }

            // Handle blocked access
            if ($this->wp_login_php) {
                $this->handle_activation_redirects();
                $this->wp_template_loader();
            } elseif ($pagenow === 'wp-login.php') {
                global $error, $interim_login, $action, $user_login;
                require_once ABSPATH . 'wp-login.php';
                exit;
            }
        }

        /**
         * Handle activation redirects for multisite
         */
        private function handle_activation_redirects(): void {
            if (!is_multisite()) {
                return;
            }

            $referer = wp_get_referer();
            
            if ($referer && strpos($referer, 'wp-activate.php') !== false) {
                $referer_parts = wp_parse_url($referer);
                
                if (!empty($referer_parts['query'])) {
                    parse_str($referer_parts['query'], $referer_query);
                    
                    if (!empty($referer_query['key'])) {
                        $result = wpmu_activate_signup($referer_query['key']);
                        
                        if (is_wp_error($result) && 
                            in_array($result->get_error_code(), ['already_active', 'blog_taken'], true)) {
                            
                            $query_string = isset($_SERVER['QUERY_STRING']) ? sanitize_text_field(wp_unslash($_SERVER['QUERY_STRING'])) : '';
                            $redirect_url = $this->new_login_url();
                            
                            if (!empty($query_string)) {
                                $redirect_url .= '?' . $query_string;
                            }
                            
                            wp_safe_redirect($redirect_url);
                            exit;
                        }
                    }
                }
            }
        }

        /**
         * Filter site URL
         */
        public function site_url(string $url, string $path, ?string $scheme, ?int $blog_id): string {
            return $this->filter_wp_login_php($url, $scheme);
        }

        /**
         * Filter network site URL
         */
        public function network_site_url(string $url, string $path, ?string $scheme): string {
            return $this->filter_wp_login_php($url, $scheme);
        }

        /**
         * Filter wp_redirect
         */
        public function wp_redirect(string $location, int $status): string {
            return $this->filter_wp_login_php($location);
        }

        /**
         * Filter wp-login.php URLs
         */
        public function filter_wp_login_php(string $url, ?string $scheme = null): string {
            // Replace wp-login.php with custom URL
            if (strpos($url, 'wp-login.php') !== false || strpos($url, 'wp-login') !== false) {
                if (is_ssl()) {
                    $scheme = 'https';
                }
                
                $args = explode('?', $url);
                if (isset($args[1])) {
                    wp_parse_str($args[1], $query_args);
                    $url = add_query_arg($query_args, $this->new_login_url($scheme));
                } else {
                    $url = $this->new_login_url($scheme);
                }
            }

            // Handle admin redirects for non-logged-in users
            $current_url = isset($_SERVER['PHP_SELF']) ? sanitize_text_field(wp_unslash($_SERVER['PHP_SELF'])) : '';
            
            if (strpos($current_url, 'wp-admin') === false ||
                defined('DOING_AJAX') ||
                !function_exists('is_user_logged_in') ||
                is_user_logged_in()) {
                return $url;
            }

            $redirect_field = get_option('swal_redirect_field', '');
            return $redirect_field ? '/' . $redirect_field : '/';
        }

        /**
         * Filter welcome email
         */
        public function welcome_email(string $value): string {
            return str_replace(
                'wp-login.php',
                trailingslashit(get_site_option('swal_page', 'login')),
                $value
            );
        }

        /**
         * Get forbidden slugs
         */
        public function forbidden_slugs(): array {
            if ($this->forbidden_slugs_cache === null) {
                $wp = new WP();
                $this->forbidden_slugs_cache = array_merge(
                    $wp->public_query_vars,
                    $wp->private_query_vars,
                    ['admin', 'wp-admin', 'wp-content', 'wp-includes', 'wp-json']
                );
            }
            
            return $this->forbidden_slugs_cache;
        }
    }

    // Initialize the plugin
    new Secure_WP_Admin_Login();
}