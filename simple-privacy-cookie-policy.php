<?php
/**
 * Plugin Name: WPSimpleCompliance
 * Plugin URI: https://github.com/CodyCloudSrls/WPSimpleCompliance
 * Description: Lightweight EU-oriented cookie consent, visual banner editor, scanner, accessibility statement and multilingual privacy/cookie policy generator.
 * Version: 1.2.1
 * Author: CodyCloud Srls
 * License: AGPL-3.0
 * Text Domain: simple-privacy-cookie-policy
 * Update URI: https://github.com/CodyCloudSrls/WPSimpleCompliance
 */

if (! defined('ABSPATH')) {
	exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/legal-generator.php';

final class Simple_Privacy_Cookie_Policy {
	const VERSION = '1.2.1';
	const OPTION = 'spcp_settings';
	const SCAN_OPTION = 'spcp_scan';
	const VERSION_OPTION = 'spcp_version';
	const UPGRADE_LOCK_OPTION = 'spcp_upgrade_lock';
	const UPDATE_TRANSIENT = 'spcp_github_release';
	const UPDATE_CACHE_TTL = 6 * HOUR_IN_SECONDS;
	const UPDATE_REPOSITORY = 'CodyCloudSrls/WPSimpleCompliance';
	const SCAN_HOOK = 'spcp_scheduled_scan';
	const COOKIE = 'simple_privacy_cookie_consent';
	const MANAGED_PAGE_META = '_spcp_managed_page';

	public static function init() {
		add_action('init', array(__CLASS__, 'maybe_upgrade'), 20);
		add_action('admin_init', array(__CLASS__, 'register_settings'));
		add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
		add_action('admin_post_spcp_scan', array(__CLASS__, 'handle_scan_request'));
		add_action(self::SCAN_HOOK, array(__CLASS__, 'run_cookie_scan'));
		add_action('upgrader_process_complete', array(__CLASS__, 'clear_update_cache'), 10, 2);
		add_action('wp_head', array(__CLASS__, 'print_consent_mode_defaults'), 0);
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
		add_action('wp_footer', array(__CLASS__, 'render_accessibility_footer_link'), 1);
		add_action('wp_footer', array(__CLASS__, 'render_consent_ui'), 2);
		add_action('template_redirect', array(__CLASS__, 'start_iubenda_strip_buffer'), 0);
		add_action('wp_head', array(__CLASS__, 'print_policy_document_css'), 20);
		add_filter('template_include', array(__CLASS__, 'maybe_use_policy_document_template'), 99);
		add_filter('the_content', array(__CLASS__, 'prepare_facebook_embeds'), 30);
		add_filter('wp_list_pages_excludes', array(__CLASS__, 'exclude_policy_pages_from_page_menus'));
		add_filter('wp_page_menu_args', array(__CLASS__, 'exclude_policy_pages_from_page_menu_args'));
		add_filter('wp_nav_menu_objects', array(__CLASS__, 'exclude_policy_pages_from_nav_menu'), 10, 2);
		add_filter('pre_set_site_transient_update_plugins', array(__CLASS__, 'check_github_update'));
		add_filter('plugins_api', array(__CLASS__, 'github_plugin_information'), 20, 3);

		add_shortcode('simple_cookie_settings', array(__CLASS__, 'settings_shortcode'));
		add_shortcode('simple_cookie_policy', array(__CLASS__, 'cookie_policy_shortcode'));
		add_shortcode('simple_privacy_policy', array(__CLASS__, 'privacy_policy_shortcode'));
		add_shortcode('simple_accessibility_statement', array(__CLASS__, 'accessibility_statement_shortcode'));
	}

	public static function defaults() {
		$privacy_url = get_privacy_policy_url();
		if (! $privacy_url) {
			$page = get_page_by_path('privacy-policy');
			$privacy_url = $page ? self::permalink_for_page($page, 'privacy-policy') : home_url('/privacy-policy/');
		}
		$visual_defaults = self::visual_default_palette('glass');

		return array_merge(
			SPCP_Legal_Generator::defaults(),
			array(
				'controller_name' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
				'privacy_url' => $privacy_url,
				'cookie_policy_url' => self::page_url_by_slug('cookie-policy', home_url('/cookie-policy/')),
				'accessibility_statement_url' => self::page_url_by_slug('dichiarazione-accessibilita', home_url('/dichiarazione-accessibilita/')),
				'accessibility_contact_email' => self::default_contact_email(),
				'show_accessibility_footer_link' => '1',
				'cookie_version' => '2026-05-08',
				'consent_days' => 180,
				'strip_iubenda' => '1',
				'google_consent_mode' => '1',
				'hide_policy_pages_from_menus' => '1',
			),
			$visual_defaults
		);
	}

	public static function visual_templates() {
		return array(
			'dark' => array(
				'label' => 'Dark',
				'description' => 'Compatto, solido, adatto a siti scuri o ad alto contrasto.',
				'palette' => array(
					'visual_bg' => '#111318',
					'visual_border' => '#2c3340',
					'visual_text' => '#f8fafc',
					'visual_muted' => '#b7c0ce',
					'visual_link' => '#8cc8ff',
					'visual_primary_bg' => '#f8fafc',
					'visual_primary_text' => '#111318',
					'visual_secondary_bg' => '#20242d',
					'visual_secondary_border' => '#3a4454',
					'visual_focus' => '#ffd166',
				),
			),
			'white' => array(
				'label' => 'White',
				'description' => 'Pulito e neutro, pensato per siti istituzionali o editoriali chiari.',
				'palette' => array(
					'visual_bg' => '#ffffff',
					'visual_border' => '#cfd7e6',
					'visual_text' => '#172033',
					'visual_muted' => '#4a5568',
					'visual_link' => '#0057b8',
					'visual_primary_bg' => '#0057b8',
					'visual_primary_text' => '#ffffff',
					'visual_secondary_bg' => '#f7f9fc',
					'visual_secondary_border' => '#cfd7e6',
					'visual_focus' => '#f4b000',
				),
			),
			'glass' => array(
				'label' => 'Glass',
				'description' => 'Barra ampia con trasparenza e blur, derivata dalla grafica CodyCloud.',
				'palette' => array(
					'visual_bg' => '#121214',
					'visual_border' => '#ffffff',
					'visual_text' => '#f3f5f7',
					'visual_muted' => '#b8bec8',
					'visual_link' => '#8cc8ff',
					'visual_primary_bg' => '#ffffff',
					'visual_primary_text' => '#111111',
					'visual_secondary_bg' => '#ffffff',
					'visual_secondary_border' => '#ffffff',
					'visual_focus' => '#8cc8ff',
				),
			),
		);
	}

	private static function visual_default_palette($template) {
		$templates = self::visual_templates();
		$template = isset($templates[$template]) ? $template : 'glass';

		return array_merge(
			array('visual_template' => $template),
			$templates[$template]['palette']
		);
	}

	private static function visual_color_keys() {
		return array(
			'visual_bg',
			'visual_border',
			'visual_text',
			'visual_muted',
			'visual_link',
			'visual_primary_bg',
			'visual_primary_text',
			'visual_secondary_bg',
			'visual_secondary_border',
			'visual_focus',
		);
	}

	private static function visual_color_labels() {
		return array(
			'visual_bg' => 'Sfondo banner',
			'visual_border' => 'Bordo',
			'visual_text' => 'Testo principale',
			'visual_muted' => 'Testo secondario',
			'visual_link' => 'Link',
			'visual_primary_bg' => 'Bottone primario',
			'visual_primary_text' => 'Testo bottone primario',
			'visual_secondary_bg' => 'Bottone secondario',
			'visual_secondary_border' => 'Bordo secondario',
			'visual_focus' => 'Focus accessibile',
		);
	}

	public static function settings() {
		$saved = get_option(self::OPTION, array());
		return wp_parse_args(is_array($saved) ? $saved : array(), self::defaults());
	}

	public static function sanitize_settings($settings) {
		$settings = is_array($settings) ? $settings : array();
		$defaults = self::defaults();
		$legal = SPCP_Legal_Generator::sanitize($settings, $defaults);
		$visual_template = sanitize_key($settings['visual_template'] ?? $defaults['visual_template']);
		if (! isset(self::visual_templates()[$visual_template])) {
			$visual_template = $defaults['visual_template'];
		}

		$core = array(
			'controller_name' => sanitize_text_field(wp_specialchars_decode((string) ($settings['controller_name'] ?? $legal['controller_legal_name'] ?? $defaults['controller_name']), ENT_QUOTES)),
			'privacy_url' => esc_url_raw($settings['privacy_url'] ?? $defaults['privacy_url']),
			'cookie_policy_url' => esc_url_raw($settings['cookie_policy_url'] ?? $defaults['cookie_policy_url']),
			'accessibility_statement_url' => esc_url_raw($settings['accessibility_statement_url'] ?? $defaults['accessibility_statement_url']),
			'accessibility_contact_email' => sanitize_email($settings['accessibility_contact_email'] ?? $legal['privacy_contact_email'] ?? $defaults['accessibility_contact_email']),
			'show_accessibility_footer_link' => empty($settings['show_accessibility_footer_link']) ? '0' : '1',
			'cookie_version' => sanitize_key($settings['cookie_version'] ?? $defaults['cookie_version']),
			'consent_days' => max(180, min(365, absint($settings['consent_days'] ?? $defaults['consent_days']))),
			'strip_iubenda' => empty($settings['strip_iubenda']) ? '0' : '1',
			'google_consent_mode' => empty($settings['google_consent_mode']) ? '0' : '1',
			'hide_policy_pages_from_menus' => empty($settings['hide_policy_pages_from_menus']) ? '0' : '1',
			'visual_template' => $visual_template,
		);

		foreach (self::visual_color_keys() as $key) {
			$color = sanitize_hex_color($settings[$key] ?? $defaults[$key]);
			$core[$key] = $color ? $color : $defaults[$key];
		}

		if (empty($legal['controller_legal_name']) && ! empty($core['controller_name'])) {
			$legal['controller_legal_name'] = $core['controller_name'];
		}

		return array_merge($legal, $core);
	}

	public static function register_settings() {
		register_setting('spcp', self::OPTION, array(
			'type' => 'array',
			'sanitize_callback' => array(__CLASS__, 'sanitize_settings'),
			'default' => self::defaults(),
		));
	}

	public static function add_settings_page() {
		add_options_page(
			'WPSimpleCompliance',
			'WP Compliance',
			'manage_options',
			'simple-privacy-cookie-policy',
			array(__CLASS__, 'render_settings_page')
		);
	}

	public static function enqueue_admin_assets($hook) {
		if ('settings_page_simple-privacy-cookie-policy' !== $hook) {
			return;
		}

		wp_enqueue_style(
			'simple-privacy-cookie-policy-admin',
			plugins_url('assets/simple-privacy-cookie-policy-admin.css', __FILE__),
			array(),
			self::VERSION
		);
		wp_enqueue_script(
			'simple-privacy-cookie-policy-admin',
			plugins_url('assets/simple-privacy-cookie-policy-admin.js', __FILE__),
			array(),
			self::VERSION,
			true
		);
		wp_add_inline_script(
			'simple-privacy-cookie-policy-admin',
			'window.SPCPVisualPresets = '. wp_json_encode(self::visual_templates()) .';',
			'before'
		);
	}

	public static function activate() {
		self::install_default_pages();
		self::schedule_scan();
		update_option(self::VERSION_OPTION, self::VERSION, false);
	}

	public static function deactivate() {
		$timestamp = wp_next_scheduled(self::SCAN_HOOK);
		if ($timestamp) {
			wp_unschedule_event($timestamp, self::SCAN_HOOK);
		}
	}

	public static function maybe_upgrade() {
		$installed = (string) get_option(self::VERSION_OPTION, '');
		if ($installed && version_compare($installed, self::VERSION, '>=')) {
			return;
		}

		if (! self::acquire_upgrade_lock()) {
			return;
		}

		try {
			$installed = (string) get_option(self::VERSION_OPTION, '');
			if (! $installed || version_compare($installed, self::VERSION, '<')) {
				self::install_default_pages();
				self::schedule_scan();
				update_option(self::VERSION_OPTION, self::VERSION, false);
			}
		} finally {
			self::release_upgrade_lock();
		}
	}

	public static function check_github_update($transient) {
		if (! is_object($transient) || empty($transient->checked) || ! isset($transient->checked[self::plugin_file()])) {
			return $transient;
		}

		$release = self::github_release();
		if (! $release) {
			return $transient;
		}

		$current_version = (string) $transient->checked[self::plugin_file()];
		$update = self::github_update_payload($release);
		if (version_compare($release['version'], $current_version, '>')) {
			$transient->response[self::plugin_file()] = $update;
		} else {
			$transient->no_update[self::plugin_file()] = $update;
		}

		return $transient;
	}

	public static function github_plugin_information($result, $action, $args) {
		if ('plugin_information' !== $action || empty($args->slug) || self::plugin_slug() !== $args->slug) {
			return $result;
		}

		$release = self::github_release();
		if (! $release) {
			return $result;
		}

		$body = trim((string) ($release['body'] ?? ''));
		return (object) array(
			'name' => 'WPSimpleCompliance',
			'slug' => self::plugin_slug(),
			'version' => $release['version'],
			'author' => '<a href="https://www.codycloud.it/">CodyCloud Srls</a>',
			'homepage' => $release['html_url'],
			'requires' => '5.8',
			'tested' => '6.6',
			'requires_php' => '7.4',
			'last_updated' => $release['published_at'],
			'download_link' => $release['package'],
			'sections' => array(
				'description' => 'WPSimpleCompliance provides EU-oriented cookie consent, Google Consent Mode v2 defaults, policy pages, cookie scanning and accessibility statement helpers.',
				'changelog' => $body ? wp_kses_post(wpautop($body)) : 'See the GitHub release notes.',
			),
		);
	}

	public static function clear_update_cache($upgrader = null, $options = array()) {
		if (! is_array($options) || empty($options['plugins']) || ! in_array(self::plugin_file(), (array) $options['plugins'], true)) {
			return;
		}

		delete_site_transient(self::UPDATE_TRANSIENT);
	}

	private static function github_update_payload($release) {
		return (object) array(
			'id' => 'github.com/'. self::UPDATE_REPOSITORY,
			'slug' => self::plugin_slug(),
			'plugin' => self::plugin_file(),
			'new_version' => $release['version'],
			'url' => $release['html_url'],
			'package' => $release['package'],
			'requires' => '5.8',
			'tested' => '6.6',
			'requires_php' => '7.4',
		);
	}

	private static function github_release($force = false) {
		$cached = get_site_transient(self::UPDATE_TRANSIENT);
		if (! $force && is_array($cached)) {
			return $cached;
		}

		$response = wp_remote_get('https://api.github.com/repos/'. self::UPDATE_REPOSITORY .'/releases/latest', array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/vnd.github+json',
				'User-Agent' => 'WPSimpleCompliance/'. self::VERSION .'; '. home_url('/'),
			),
		));

		if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
			return false;
		}

		$data = json_decode((string) wp_remote_retrieve_body($response), true);
		$release = self::normalize_github_release(is_array($data) ? $data : array());
		if ($release) {
			set_site_transient(self::UPDATE_TRANSIENT, $release, self::UPDATE_CACHE_TTL);
		}

		return $release;
	}

	private static function normalize_github_release($data) {
		if (! empty($data['draft']) || ! empty($data['prerelease'])) {
			return false;
		}

		$version = ltrim((string) ($data['tag_name'] ?? ''), 'vV');
		if (! preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version)) {
			return false;
		}

		$package = '';
		foreach ((array) ($data['assets'] ?? array()) as $asset) {
			$name = strtolower((string) ($asset['name'] ?? ''));
			if (! empty($asset['browser_download_url']) && in_array($name, array('simple-privacy-cookie-policy.zip', 'wpsimplecompliance.zip', 'wp-simple-compliance.zip'), true)) {
				$package = esc_url_raw($asset['browser_download_url']);
				break;
			}
		}

		if (! $package) {
			return false;
		}

		return array(
			'version' => $version,
			'package' => $package,
			'html_url' => esc_url_raw($data['html_url'] ?? 'https://github.com/'. self::UPDATE_REPOSITORY),
			'published_at' => sanitize_text_field($data['published_at'] ?? ''),
			'body' => wp_kses_post($data['body'] ?? ''),
		);
	}

	private static function plugin_file() {
		return plugin_basename(__FILE__);
	}

	private static function plugin_slug() {
		$slug = dirname(self::plugin_file());
		if ('.' === $slug || '/' === $slug) {
			$slug = basename(dirname(__FILE__));
		}

		return sanitize_key($slug);
	}

	private static function acquire_upgrade_lock() {
		$lock = absint(get_option(self::UPGRADE_LOCK_OPTION, 0));
		if ($lock && time() - $lock > 5 * MINUTE_IN_SECONDS) {
			delete_option(self::UPGRADE_LOCK_OPTION);
		}

		return add_option(self::UPGRADE_LOCK_OPTION, time(), '', false);
	}

	private static function release_upgrade_lock() {
		delete_option(self::UPGRADE_LOCK_OPTION);
	}

	private static function install_default_pages() {
		$settings = self::settings();
		$current_privacy_url = (string) ($settings['privacy_url'] ?? '');
		$current_cookie_policy_url = (string) ($settings['cookie_policy_url'] ?? '');
		$current_accessibility_statement_url = (string) ($settings['accessibility_statement_url'] ?? '');

		$privacy_page_id = absint(get_option('wp_page_for_privacy_policy'));
		if (! $privacy_page_id || 'publish' !== get_post_status($privacy_page_id)) {
			$privacy_page = get_page_by_path('privacy-policy');
			$privacy_page_id = $privacy_page && 'trash' !== get_post_status($privacy_page) ? absint($privacy_page->ID) : 0;
		}
		if (! $privacy_page_id) {
			$privacy_page_id = self::ensure_page('privacy-policy', 'Privacy Policy', '[simple_privacy_policy]');
		}
		if ($privacy_page_id) {
			self::maybe_mark_managed_page($privacy_page_id, '[simple_privacy_policy]');
			update_option('wp_page_for_privacy_policy', $privacy_page_id);
			if (self::should_refresh_policy_url($current_privacy_url, 'privacy-policy', $privacy_page_id)) {
				$settings['privacy_url'] = self::permalink_for_page($privacy_page_id, 'privacy-policy');
			}
		}

		$cookie_page_id = self::ensure_page('cookie-policy', 'Cookie Policy', '[simple_cookie_policy]');
		if ($cookie_page_id) {
			if (self::should_refresh_policy_url($current_cookie_policy_url, 'cookie-policy', $cookie_page_id)) {
				$settings['cookie_policy_url'] = self::permalink_for_page($cookie_page_id, 'cookie-policy');
			}
		}

		$accessibility_page_id = self::ensure_page('dichiarazione-accessibilita', 'Dichiarazione di accessibilita', '[simple_accessibility_statement]');
		if ($accessibility_page_id) {
			if (self::should_refresh_policy_url($current_accessibility_statement_url, 'dichiarazione-accessibilita', $accessibility_page_id)) {
				$settings['accessibility_statement_url'] = self::permalink_for_page($accessibility_page_id, 'dichiarazione-accessibilita');
			}
		}

		if (empty($settings['controller_legal_name'])) {
			$settings['controller_legal_name'] = $settings['controller_name'] ?: get_bloginfo('name');
		}
		if (empty($settings['privacy_contact_email'])) {
			$settings['privacy_contact_email'] = self::default_contact_email();
		}
		if (empty($settings['accessibility_contact_email'])) {
			$settings['accessibility_contact_email'] = self::default_contact_email();
		}

		update_option(self::OPTION, self::sanitize_settings($settings), false);
	}

	private static function should_refresh_policy_url($current_url, $default_slug, $page_id) {
		$current_url = trim((string) $current_url);
		if ('' === $current_url) {
			return true;
		}

		$current_page_id = absint(url_to_postid($current_url));
		if ($current_page_id && self::is_generated_policy_page(get_post($current_page_id))) {
			return true;
		}

		if ($current_page_id && $current_page_id === absint($page_id)) {
			return true;
		}

		$default_url = home_url('/'. trim((string) $default_slug, '/') .'/');
		if (untrailingslashit($current_url) === untrailingslashit($default_url) && ! $current_page_id) {
			return true;
		}

		return false;
	}

	private static function ensure_page($slug, $title, $content) {
		$page = get_page_by_path($slug);
		if ($page && 'trash' !== get_post_status($page)) {
			self::maybe_mark_managed_page($page->ID, $content);
			return absint($page->ID);
		}

		$page_id = wp_insert_post(array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_name' => sanitize_title($slug),
			'post_title' => sanitize_text_field($title),
			'post_content' => $content,
			'comment_status' => 'closed',
			'ping_status' => 'closed',
		), true);

		if (is_wp_error($page_id)) {
			return 0;
		}

		$page_id = absint($page_id);
		update_post_meta($page_id, self::MANAGED_PAGE_META, '1');

		return $page_id;
	}

	private static function maybe_mark_managed_page($page_id, $expected_shortcode) {
		$page_id = absint($page_id);
		$post = $page_id ? get_post($page_id) : null;
		if (! $post) {
			return;
		}

		if (false !== strpos((string) $post->post_content, (string) $expected_shortcode) || self::contains_policy_shortcode($post)) {
			update_post_meta($page_id, self::MANAGED_PAGE_META, '1');
		}
	}

	private static function contains_policy_shortcode($post) {
		$content = is_object($post) ? (string) $post->post_content : (string) $post;
		foreach (array('[simple_privacy_policy', '[simple_cookie_policy', '[simple_accessibility_statement') as $shortcode) {
			if (false !== strpos($content, $shortcode)) {
				return true;
			}
		}

		return false;
	}

	private static function page_url_by_slug($slug, $fallback) {
		$page = get_page_by_path($slug);
		if ($page && 'publish' === get_post_status($page)) {
			return self::permalink_for_page($page, $slug);
		}

		return $fallback;
	}

	private static function permalink_for_page($page, $slug) {
		$post = is_object($page) ? $page : get_post($page);
		if ($post && function_exists('get_permalink')) {
			$url = get_permalink($post);
			if ($url) {
				return $url;
			}
		}

		return home_url('/'. trim((string) $slug, '/') .'/');
	}

	private static function default_contact_email() {
		$email = (string) get_option('admin_email');
		return is_email($email) ? $email : '';
	}

	private static function hide_policy_pages_from_menus() {
		$settings = self::settings();
		return ! is_admin() && '1' === ($settings['hide_policy_pages_from_menus'] ?? '1');
	}

	private static function managed_policy_page_ids() {
		static $ids = null;
		if (null !== $ids) {
			return $ids;
		}

		$ids = array();
		$slugs = array(
			'privacy-policy',
			'cookie-policy',
			'dichiarazione-accessibilita',
		);

		$privacy_page_id = absint(get_option('wp_page_for_privacy_policy'));
		if ($privacy_page_id) {
			$slugs[] = $privacy_page_id;
		}

		$settings = self::settings();
		foreach (array(
			$settings['privacy_url'] ?? '',
			self::privacy_url($settings),
			$settings['cookie_policy_url'] ?? '',
			$settings['accessibility_statement_url'] ?? '',
		) as $url) {
			$page_id = absint(url_to_postid((string) $url));
			if ($page_id) {
				$slugs[] = $page_id;
			}
		}

		foreach ($slugs as $slug_or_id) {
			$page = is_numeric($slug_or_id) ? get_post(absint($slug_or_id)) : get_page_by_path($slug_or_id);
			if (! $page || 'trash' === get_post_status($page)) {
				continue;
			}
			if (self::is_generated_policy_page($page)) {
				$ids[] = absint($page->ID);
			}
		}

		$ids = array_values(array_unique(array_filter($ids)));
		return $ids;
	}

	public static function exclude_policy_pages_from_page_menus($excluded) {
		if (! self::hide_policy_pages_from_menus()) {
			return $excluded;
		}

		$excluded = array_map('absint', is_array($excluded) ? $excluded : array());
		return array_values(array_unique(array_merge($excluded, self::managed_policy_page_ids())));
	}

	public static function exclude_policy_pages_from_page_menu_args($args) {
		if (! self::hide_policy_pages_from_menus()) {
			return $args;
		}

		$managed_ids = self::managed_policy_page_ids();
		if (! $managed_ids) {
			return $args;
		}

		$existing = array();
		if (! empty($args['exclude'])) {
			$existing = array_map('absint', explode(',', (string) $args['exclude']));
		}
		$args['exclude'] = implode(',', array_values(array_unique(array_merge($existing, $managed_ids))));

		return $args;
	}

	public static function exclude_policy_pages_from_nav_menu($items, $args) {
		if (! self::hide_policy_pages_from_menus()) {
			return $items;
		}

		$managed_ids = array_flip(self::managed_policy_page_ids());
		if (! $managed_ids) {
			return $items;
		}

		$hidden_menu_item_ids = array();
		$filtered = array();
		foreach ($items as $item) {
			if ('page' === ($item->object ?? '') && isset($managed_ids[absint($item->object_id)])) {
				$hidden_menu_item_ids[absint($item->ID)] = true;
				continue;
			}
			$filtered[] = $item;
		}

		if (! $hidden_menu_item_ids) {
			return $items;
		}

		$changed = true;
		while ($changed) {
			$changed = false;
			foreach ($filtered as $item) {
				$parent_id = absint($item->menu_item_parent ?? 0);
				if ($parent_id && isset($hidden_menu_item_ids[$parent_id]) && ! isset($hidden_menu_item_ids[absint($item->ID)])) {
					$hidden_menu_item_ids[absint($item->ID)] = true;
					$changed = true;
				}
			}
		}

		return array_values(array_filter($filtered, static function($item) use ($hidden_menu_item_ids) {
			return ! isset($hidden_menu_item_ids[absint($item->ID)]);
		}));
	}

	private static function is_managed_policy_singular() {
		if (is_admin() || ! is_singular('page')) {
			return false;
		}

		$page_id = absint(get_queried_object_id());
		$page = $page_id ? get_post($page_id) : null;
		return $page && self::is_generated_policy_page($page);
	}

	private static function is_generated_policy_page($page) {
		if (! $page || 'page' !== ($page->post_type ?? '')) {
			return false;
		}

		return self::contains_policy_shortcode($page);
	}

	private static function should_open_document_popup($url) {
		$page_id = absint(url_to_postid((string) $url));
		if (! $page_id) {
			return false;
		}

		return self::is_generated_policy_page(get_post($page_id));
	}

	public static function print_policy_document_css() {
		if (self::is_managed_policy_singular()) {
			self::print_inline_css();
		}
	}

	public static function maybe_use_policy_document_template($template) {
		if (! self::is_managed_policy_singular()) {
			return $template;
		}

		$plugin_template = plugin_dir_path(__FILE__) . 'templates/policy-document.php';
		return is_readable($plugin_template) ? $plugin_template : $template;
	}

	private static function schedule_scan() {
		if (! wp_next_scheduled(self::SCAN_HOOK)) {
			wp_schedule_single_event(time() + 90, self::SCAN_HOOK);
		}
	}

	private static function privacy_url($settings) {
		return SPCP_Legal_Generator::privacy_public_url($settings);
	}

	public static function render_settings_page() {
		if (! current_user_can('manage_options')) {
			return;
		}

		$settings = self::settings();
		$scan = self::get_scan_data();
		?>
		<div class="wrap">
			<h1>WPSimpleCompliance</h1>
			<p>Plugin leggero per consenso cookie first-party, Google Consent Mode v2, popup cookie policy, scansione tecnica dei cookie e generazione multilingua delle policy.</p>
			<div class="notice notice-warning" style="padding:12px 16px;">
				<p><strong>Nota legale:</strong> il plugin prepara documenti e controlli coerenti con GDPR e linee guida cookie, ma il titolare deve verificare dati reali, basi giuridiche, responsabili, trasferimenti e tempi di conservazione prima della pubblicazione definitiva.</p>
			</div>
			<?php self::render_status_box($settings, $scan); ?>
			<form method="post" action="options.php">
				<?php settings_fields('spcp'); ?>
				<?php self::render_visual_editor($settings); ?>
				<h2>Impostazioni tecniche</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="spcp-controller-name">Nome breve sito/titolare</label></th>
						<td><input id="spcp-controller-name" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[controller_name]" value="<?php echo esc_attr($settings['controller_name']); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="spcp-privacy-url">URL pagina privacy policy</label></th>
						<td>
							<input id="spcp-privacy-url" class="regular-text code" name="<?php echo esc_attr(self::OPTION); ?>[privacy_url]" value="<?php echo esc_url($settings['privacy_url']); ?>">
							<p class="description">Se l'URL punta a una pagina senza shortcode del plugin, il banner apre la pagina normale e non il popup.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="spcp-cookie-url">URL cookie policy</label></th>
						<td>
							<input id="spcp-cookie-url" class="regular-text code" name="<?php echo esc_attr(self::OPTION); ?>[cookie_policy_url]" value="<?php echo esc_url($settings['cookie_policy_url']); ?>">
							<p class="description">Le pagine custom restano link diretti; il popup si abilita solo sulle pagine generate con shortcode WPSimpleCompliance.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="spcp-accessibility-url">URL dichiarazione accessibilita</label></th>
						<td>
							<input id="spcp-accessibility-url" class="regular-text code" name="<?php echo esc_attr(self::OPTION); ?>[accessibility_statement_url]" value="<?php echo esc_url($settings['accessibility_statement_url']); ?>">
							<p class="description">Se indichi una pagina gia scritta, viene usata quella pagina senza aprire una seconda versione in popup.</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="spcp-accessibility-email">Email segnalazioni accessibilita</label></th>
						<td><input id="spcp-accessibility-email" type="email" class="regular-text code" name="<?php echo esc_attr(self::OPTION); ?>[accessibility_contact_email]" value="<?php echo esc_attr($settings['accessibility_contact_email']); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="spcp-cookie-version">Versione consenso</label></th>
						<td><input id="spcp-cookie-version" class="regular-text" name="<?php echo esc_attr(self::OPTION); ?>[cookie_version]" value="<?php echo esc_attr($settings['cookie_version']); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="spcp-consent-days">Durata consenso in giorni</label></th>
						<td>
							<input id="spcp-consent-days" type="number" min="180" max="365" name="<?php echo esc_attr(self::OPTION); ?>[consent_days]" value="<?php echo esc_attr($settings['consent_days']); ?>">
							<p class="description">Minimo 180 giorni per evitare riproposizioni troppo frequenti del banner, salvo cambio sostanziale di trattamenti o impossibilita tecnica di leggere la scelta.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Comportamento frontend</th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[strip_iubenda]" value="1" <?php checked($settings['strip_iubenda'], '1'); ?>> Rimuovi a runtime gli snippet Iubenda noti</label><br>
							<label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[google_consent_mode]" value="1" <?php checked($settings['google_consent_mode'], '1'); ?>> Abilita Google Consent Mode v2</label><br>
							<label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[hide_policy_pages_from_menus]" value="1" <?php checked($settings['hide_policy_pages_from_menus'], '1'); ?>> Nascondi le pagine policy generate dai menu automatici del tema</label><br>
							<label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[show_accessibility_footer_link]" value="1" <?php checked($settings['show_accessibility_footer_link'], '1'); ?>> Mostra link "Dichiarazione di accessibilita" nel footer</label>
						</td>
					</tr>
				</table>
				<?php SPCP_Legal_Generator::render_admin_fields($settings, self::OPTION); ?>
				<?php submit_button(); ?>
			</form>
			<hr>
			<h2>Scansione cookie</h2>
			<?php if (! empty($scan['scanned_at'])) : ?>
				<p>Ultima scansione: <strong><?php echo esc_html($scan['scanned_at']); ?></strong>. Cookie rilevati: <strong><?php echo esc_html(count($scan['cookies'] ?? array())); ?></strong>. Servizi rilevati: <strong><?php echo esc_html(count($scan['services'] ?? array())); ?></strong>.</p>
			<?php else : ?>
				<p>Nessuna scansione salvata. Alla prima installazione viene programmata una scansione automatica; puoi eseguirla subito dal pulsante qui sotto.</p>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="spcp_scan">
				<?php wp_nonce_field('spcp_scan'); ?>
				<?php submit_button('Esegui scansione cookie', 'secondary'); ?>
			</form>
			<p><strong>Shortcode disponibili:</strong> <code>[simple_cookie_settings]</code>, <code>[simple_cookie_policy]</code>, <code>[simple_privacy_policy]</code>, <code>[simple_accessibility_statement]</code>.</p>
		</div>
		<?php
	}

	private static function render_visual_editor($settings) {
		$templates = self::visual_templates();
		$defaults = self::defaults();
		$current_template = sanitize_key($settings['visual_template'] ?? 'glass');
		if (! isset($templates[$current_template])) {
			$current_template = 'glass';
		}
		?>
		<h2>Editor grafico banner</h2>
		<div class="spcp-visual-editor" data-spcp-visual-editor>
			<p class="description">Scegli un template di partenza e adatta la palette al sito. La preview e indicativa: il frontend usa le stesse variabili colore e la stessa scelta template.</p>

			<div class="spcp-template-grid" role="radiogroup" aria-label="Template grafico cookie banner">
				<?php foreach ($templates as $key => $template) : ?>
					<label class="spcp-template-card spcp-template-card--<?php echo esc_attr($key); ?>">
						<input type="radio" name="<?php echo esc_attr(self::OPTION); ?>[visual_template]" value="<?php echo esc_attr($key); ?>" data-spcp-template <?php checked($current_template, $key); ?>>
						<span class="spcp-template-card__body">
							<span class="spcp-template-card__title"><?php echo esc_html($template['label']); ?></span>
							<span class="spcp-mini-preview spcp-mini-preview--<?php echo esc_attr($key); ?>" style="<?php echo esc_attr(self::visual_style_attribute_from_palette($template['palette'])); ?>">
								<span class="spcp-mini-preview__line spcp-mini-preview__line--strong"></span>
								<span class="spcp-mini-preview__line"></span>
								<span class="spcp-mini-preview__actions">
									<span></span><span></span><span></span>
								</span>
							</span>
							<small><?php echo esc_html($template['description']); ?></small>
						</span>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="spcp-palette-panel">
				<div>
					<h3>Palette</h3>
					<p class="description">I colori restano salvati nel plugin, quindi puoi replicare il look su altri siti senza dipendere dal tema.</p>
				</div>
				<button type="button" class="button" data-spcp-apply-template>Applica palette del template</button>
			</div>

			<div class="spcp-palette-grid">
				<?php foreach (self::visual_color_labels() as $key => $label) : ?>
					<label class="spcp-color-field">
						<span><?php echo esc_html($label); ?></span>
						<input type="color" name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings[$key] ?? $defaults[$key]); ?>" data-spcp-color="<?php echo esc_attr($key); ?>">
						<code data-spcp-color-code="<?php echo esc_attr($key); ?>"><?php echo esc_html($settings[$key] ?? $defaults[$key]); ?></code>
					</label>
				<?php endforeach; ?>
			</div>

			<div class="spcp-live-preview spcp-live-preview--<?php echo esc_attr($current_template); ?>" data-spcp-preview style="<?php echo esc_attr(self::visual_style_attribute($settings)); ?>">
				<div class="spcp-preview-banner">
					<div class="spcp-preview-copy">
						<strong>Privacy e cookie</strong>
						<span>Usiamo cookie tecnici necessari. Con il tuo consenso possiamo usare anche preferenze, statistiche e marketing.</span>
						<a href="#">Privacy policy</a>
					</div>
					<div class="spcp-preview-actions">
						<button type="button" class="spcp-preview-button spcp-preview-button--ghost">Rifiuta</button>
						<button type="button" class="spcp-preview-button spcp-preview-button--ghost">Personalizza</button>
						<button type="button" class="spcp-preview-button">Accetta tutto</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	private static function visual_style_attribute($settings) {
		$defaults = self::defaults();
		$palette = array();
		foreach (self::visual_color_keys() as $key) {
			$palette[$key] = sanitize_hex_color($settings[$key] ?? $defaults[$key]) ?: $defaults[$key];
		}

		return self::visual_style_attribute_from_palette($palette);
	}

	private static function visual_style_attribute_from_palette($palette) {
		$map = array(
			'visual_bg' => '--spcp-bg',
			'visual_border' => '--spcp-border',
			'visual_text' => '--spcp-text',
			'visual_muted' => '--spcp-muted',
			'visual_link' => '--spcp-link',
			'visual_primary_bg' => '--spcp-primary-bg',
			'visual_primary_text' => '--spcp-primary-text',
			'visual_secondary_bg' => '--spcp-secondary-bg',
			'visual_secondary_border' => '--spcp-secondary-border',
			'visual_focus' => '--spcp-focus',
		);
		$parts = array();

		foreach ($map as $key => $css_var) {
			$color = sanitize_hex_color($palette[$key] ?? '');
			if (! $color) {
				continue;
			}
			$parts[] = $css_var . ': ' . $color;
			$parts[] = $css_var . '-rgb: ' . implode(', ', self::hex_to_rgb($color));
		}

		return implode('; ', $parts) . ';';
	}

	private static function hex_to_rgb($hex) {
		$hex = ltrim((string) $hex, '#');
		if (3 === strlen($hex)) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if (6 !== strlen($hex) || ! ctype_xdigit($hex)) {
			return array(0, 0, 0);
		}

		return array(hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
	}

	private static function render_status_box($settings, $scan) {
		$items = array(
			array('Privacy policy', self::privacy_url($settings), ! empty(self::privacy_url($settings))),
			array('Cookie policy', $settings['cookie_policy_url'], ! empty($settings['cookie_policy_url'])),
			array('Dichiarazione accessibilita', $settings['accessibility_statement_url'], ! empty($settings['accessibility_statement_url'])),
			array('Scansione cookie', '', ! empty($scan['scanned_at']), ! empty($scan['scanned_at']) ? 'Eseguita il '. self::format_scan_date($scan['scanned_at']) : 'In attesa'),
		);
		?>
		<div class="notice notice-info" style="padding:12px 16px;">
			<p><strong>Stato:</strong> il plugin crea/aggancia le pagine policy, mostra il banner, apre la cookie policy in popup e mantiene una scansione tecnica dei cookie rilevati.</p>
			<ul style="margin-left:18px;list-style:disc;">
				<?php foreach ($items as $item) : ?>
					<li>
						<?php echo esc_html($item[0]); ?>:
						<?php if (! empty($item[2])) : ?>
							<strong>OK</strong>
							<?php if (! empty($item[1])) : ?>
								<a href="<?php echo esc_url($item[1]); ?>" target="_blank" rel="noopener">apri</a>
							<?php elseif (! empty($item[3])) : ?>
								<?php echo esc_html($item[3]); ?>
							<?php endif; ?>
						<?php else : ?>
							<strong>da completare</strong>
							<?php echo ! empty($item[3]) ? esc_html($item[3]) : ''; ?>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	public static function read_consent_cookie() {
		if (empty($_COOKIE[self::COOKIE])) {
			return array();
		}

		$data = json_decode(wp_unslash($_COOKIE[self::COOKIE]), true);
		if (! is_array($data)) {
			return array();
		}

		$settings = self::settings();
		if (empty($data['version']) || ! hash_equals((string) ($settings['cookie_version'] ?? ''), (string) $data['version'])) {
			return array();
		}

		$normalized = array(
			'version' => sanitize_key($data['version']),
			'date' => sanitize_text_field($data['date'] ?? ''),
			'necessary' => true,
			'preferences' => ! empty($data['preferences']),
			'statistics' => ! empty($data['statistics']),
			'marketing' => ! empty($data['marketing']),
		);

		if (! empty($data['id'])) {
			$normalized['id'] = sanitize_key($data['id']);
		}
		if (! empty($data['method'])) {
			$normalized['method'] = sanitize_key($data['method']);
		}

		return $normalized;
	}

	private static function consent_granted($category) {
		$consent = self::read_consent_cookie();
		return ! empty($consent[$category]);
	}

	public static function get_scan_data() {
		$scan = get_option(self::SCAN_OPTION, array());
		if (! is_array($scan)) {
			$scan = array();
		}

		$scan['cookies'] = isset($scan['cookies']) && is_array($scan['cookies']) ? $scan['cookies'] : array();
		$scan['services'] = isset($scan['services']) && is_array($scan['services']) ? $scan['services'] : array();
		$scan['urls'] = isset($scan['urls']) && is_array($scan['urls']) ? $scan['urls'] : array();

		return $scan;
	}

	public static function handle_scan_request() {
		if (! current_user_can('manage_options')) {
			wp_die(esc_html__('Permesso negato.', 'simple-privacy-cookie-policy'));
		}

		check_admin_referer('spcp_scan');
		self::run_cookie_scan();

		wp_safe_redirect(add_query_arg(array(
			'page' => 'simple-privacy-cookie-policy',
			'spcp_scan' => 'done',
		), admin_url('options-general.php')));
		exit;
	}

	private static function scan_urls() {
		$settings = self::settings();
		$urls = array(
			home_url('/'),
			self::privacy_url($settings),
			$settings['cookie_policy_url'],
			$settings['accessibility_statement_url'],
		);

		foreach (array('contatti', 'richiesta-informazioni', 'privacy-policy', 'cookie-policy') as $path) {
			$page = get_page_by_path($path);
			if ($page && 'publish' === get_post_status($page)) {
				$urls[] = get_permalink($page);
			}
		}

		$pages = get_pages(array(
			'post_status' => 'publish',
			'sort_column' => 'menu_order,post_title',
			'number' => 8,
		));

		foreach ($pages as $page) {
			$urls[] = get_permalink($page);
		}

		$home_host = wp_parse_url(home_url('/'), PHP_URL_HOST);
		$urls = array_filter(array_map('esc_url_raw', array_unique($urls)), static function($url) use ($home_host) {
			return $url && wp_parse_url($url, PHP_URL_HOST) === $home_host;
		});

		return array_values($urls);
	}

	private static function consent_cookie_header($grant_all = false) {
		$consent = array(
			'version' => self::settings()['cookie_version'],
			'date' => gmdate('c'),
			'necessary' => true,
			'preferences' => (bool) $grant_all,
			'statistics' => (bool) $grant_all,
			'marketing' => (bool) $grant_all,
		);

		return rawurlencode(self::COOKIE) .'='. rawurlencode(wp_json_encode($consent));
	}

	private static function scan_request($url, $grant_all = false) {
		return wp_remote_get($url, array(
			'timeout' => 12,
			'redirection' => 3,
			'user-agent' => 'WPSimpleCompliance Scanner/'. self::VERSION .'; '. home_url('/'),
			'headers' => $grant_all ? array('Cookie' => self::consent_cookie_header(true)) : array(),
		));
	}

	public static function run_cookie_scan() {
		$urls = self::scan_urls();
		$cookies = array();
		$services = array();

		self::add_scan_cookie($cookies, array(
			'name' => self::COOKIE,
			'provider' => 'Prima parte',
			'category' => 'Necessari',
			'purpose' => 'Memorizza le preferenze di consenso cookie dell\'utente.',
			'duration' => absint(self::settings()['consent_days']) .' giorni',
			'source' => 'WPSimpleCompliance',
		));

		foreach ($urls as $url) {
			foreach (array(false, true) as $grant_all) {
				$response = self::scan_request($url, $grant_all);
				if (is_wp_error($response)) {
					continue;
				}

				foreach (self::extract_set_cookie_headers($response) as $header) {
					$cookie = self::parse_set_cookie_header($header, $url);
					if ($cookie) {
						self::add_scan_cookie($cookies, $cookie);
					}
				}

				$html = wp_remote_retrieve_body($response);
				if ($html) {
					foreach (self::detect_services_from_html($html, $url) as $service) {
						self::add_scan_service($services, $service);
					}
				}
			}
		}

		$scan = array(
			'scanned_at' => current_time('mysql'),
			'site_url' => home_url('/'),
			'urls' => $urls,
			'cookies' => array_values($cookies),
			'services' => array_values($services),
		);

		update_option(self::SCAN_OPTION, $scan, false);

		return $scan;
	}

	private static function extract_set_cookie_headers($response) {
		$headers = wp_remote_retrieve_headers($response);
		if (is_object($headers) && method_exists($headers, 'getValues')) {
			return (array) $headers->getValues('set-cookie');
		}

		$set_cookie = wp_remote_retrieve_header($response, 'set-cookie');
		if (! $set_cookie) {
			return array();
		}

		return is_array($set_cookie) ? $set_cookie : array($set_cookie);
	}

	private static function parse_set_cookie_header($header, $url) {
		$parts = array_map('trim', explode(';', (string) $header));
		$name_value = array_shift($parts);
		if (! $name_value || false === strpos($name_value, '=')) {
			return null;
		}

		list($name) = explode('=', $name_value, 2);
		$name = sanitize_key($name);
		if (! $name) {
			return null;
		}

		$attrs = array();
		foreach ($parts as $part) {
			if (false === strpos($part, '=')) {
				$attrs[strtolower($part)] = true;
				continue;
			}
			list($key, $value) = explode('=', $part, 2);
			$attrs[strtolower(trim($key))] = trim($value);
		}

		$classified = self::classify_cookie($name);
		$domain = $attrs['domain'] ?? wp_parse_url($url, PHP_URL_HOST);

		return array(
			'name' => $name,
			'provider' => self::provider_from_domain((string) $domain),
			'category' => $classified['category'],
			'purpose' => $classified['purpose'],
			'duration' => self::cookie_duration($attrs),
			'source' => esc_url_raw($url),
		);
	}

	private static function classify_cookie($name) {
		$lower = strtolower($name);
		if (self::COOKIE === $lower || 0 === strpos($lower, 'wordpress_') || 0 === strpos($lower, 'wp-') || 0 === strpos($lower, 'wp_')) {
			return array('category' => 'Necessari', 'purpose' => 'Funzionamento tecnico, sicurezza, amministrazione o consenso.');
		}
		if (0 === strpos($lower, 'comment_author') || 0 === strpos($lower, 'comment_author_email')) {
			return array('category' => 'Preferenze', 'purpose' => 'Memorizza dati inseriti volontariamente nei commenti.');
		}
		if (0 === strpos($lower, '_ga') || '_gid' === $lower || '_gat' === $lower) {
			return array('category' => 'Statistiche', 'purpose' => 'Misurazione statistica tramite Google Analytics.');
		}
		if ('_fbp' === $lower || '_fbc' === $lower) {
			return array('category' => 'Marketing', 'purpose' => 'Misurazione o profilazione pubblicitaria.');
		}
		if (false !== strpos($lower, 'recaptcha') || '_grecaptcha' === $lower) {
			return array('category' => 'Necessari', 'purpose' => 'Protezione anti-spam e sicurezza dei moduli.');
		}
		if (0 === strpos($lower, 'litespeed') || false !== strpos($lower, 'cache')) {
			return array('category' => 'Necessari', 'purpose' => 'Ottimizzazione tecnica e cache del sito.');
		}
		if ('pll_language' === $lower || false !== strpos($lower, 'language')) {
			return array('category' => 'Preferenze', 'purpose' => 'Memorizza la preferenza di lingua.');
		}

		return array('category' => 'Da verificare', 'purpose' => 'Cookie rilevato automaticamente: classificazione da confermare.');
	}

	private static function provider_from_domain($domain) {
		$domain = ltrim(strtolower($domain), '.');
		$host = wp_parse_url(home_url('/'), PHP_URL_HOST);
		if (! $domain || $domain === strtolower((string) $host)) {
			return 'Prima parte';
		}

		if (false !== strpos($domain, 'google')) {
			return 'Google';
		}
		if (false !== strpos($domain, 'facebook') || false !== strpos($domain, 'meta')) {
			return 'Meta/Facebook';
		}

		return $domain;
	}

	private static function cookie_duration($attrs) {
		if (! empty($attrs['max-age'])) {
			$seconds = absint($attrs['max-age']);
			if ($seconds < DAY_IN_SECONDS) {
				return $seconds .' secondi';
			}
			return ceil($seconds / DAY_IN_SECONDS) .' giorni';
		}

		if (! empty($attrs['expires'])) {
			$timestamp = strtotime($attrs['expires']);
			if ($timestamp) {
				return 'Fino al '. gmdate('Y-m-d', $timestamp);
			}
		}

		return 'Sessione';
	}

	private static function detect_services_from_html($html, $url) {
		$services = array();
		$map = array(
			'googletagmanager.com/gtag/js' => array('Google tag / GA4', 'Google', 'Statistiche', 'Misurazione statistiche e, se configurato, conversioni. Gestito tramite Consent Mode.', '_ga, _ga_*', 'Fino a 24 mesi'),
			'googletagmanager.com/gtm.js' => array('Google Tag Manager', 'Google', 'Gestore tag', 'Caricamento tag di analytics o marketing secondo configurazione del contenitore.', 'Dipende dai tag configurati', 'Dipende dai tag configurati'),
			'google-analytics.com' => array('Google Analytics', 'Google', 'Statistiche', 'Misurazione statistica del traffico.', '_ga, _gid, _gat', 'Da configurazione Google'),
			'google.com/recaptcha' => array('Google reCAPTCHA', 'Google', 'Necessari', 'Protezione anti-spam dei moduli e sicurezza.', '_GRECAPTCHA, NID', 'Da servizio Google'),
			'gstatic.com/recaptcha' => array('Google reCAPTCHA static', 'Google', 'Necessari', 'Risorse tecniche per protezione anti-spam.', '_GRECAPTCHA', 'Da servizio Google'),
			'fonts.googleapis.com' => array('Google Fonts CSS', 'Google', 'Servizio esterno senza cookie HTTP rilevato', 'Caricamento font da CDN Google; valutare localizzazione se richiesta dalla policy.', 'Nessun cookie rilevato dalla scansione HTTP', 'N/A'),
			'fonts.gstatic.com' => array('Google Fonts file', 'Google', 'Servizio esterno senza cookie HTTP rilevato', 'Download file font da CDN Google.', 'Nessun cookie rilevato dalla scansione HTTP', 'N/A'),
			'connect.facebook.net' => array('Facebook SDK / embed', 'Meta/Facebook', 'Marketing', 'Caricamento di contenuti social incorporati e possibili tracciamenti del fornitore.', '_fbp, _fbc', 'Da configurazione Meta'),
			'youtube.com/embed' => array('YouTube embed', 'Google/YouTube', 'Marketing/terze parti', 'Riproduzione contenuti video incorporati e possibili tracciamenti del fornitore.', 'Cookie YouTube', 'Da servizio YouTube'),
			'youtube-nocookie.com' => array('YouTube no-cookie embed', 'Google/YouTube', 'Terze parti minimizzate', 'Riproduzione contenuti video con modalita privacy avanzata.', 'Possibili storage/cookie dopo interazione', 'Da servizio YouTube'),
			'maps.googleapis.com' => array('Google Maps', 'Google', 'Terze parti', 'Visualizzazione mappe incorporate.', 'Cookie Google Maps', 'Da servizio Google'),
			'cdn.iubenda.com' => array('Iubenda', 'Iubenda', 'Privacy/CMP legacy', 'CMP esterna legacy rilevata: dovrebbe essere rimossa da WPSimpleCompliance.', 'Cookie Iubenda', 'Da servizio Iubenda'),
		);

		foreach ($map as $needle => $data) {
			if (false === stripos($html, $needle)) {
				continue;
			}
			$services[] = array(
				'name' => $data[0],
				'provider' => $data[1],
				'category' => $data[2],
				'purpose' => $data[3],
				'cookies' => $data[4],
				'duration' => $data[5],
				'source' => esc_url_raw($url),
			);
		}

		return $services;
	}

	private static function add_scan_cookie(&$cookies, $cookie) {
		$key = sanitize_key(($cookie['name'] ?? '') .'-'. ($cookie['provider'] ?? ''));
		if (! $key || isset($cookies[$key])) {
			return;
		}
		$cookies[$key] = $cookie;
	}

	private static function add_scan_service(&$services, $service) {
		$key = sanitize_key(($service['name'] ?? '') .'-'. ($service['provider'] ?? ''));
		if (! $key || isset($services[$key])) {
			return;
		}
		$services[$key] = $service;
	}

	public static function print_consent_mode_defaults() {
		$settings = self::settings();
		if ('1' !== $settings['google_consent_mode']) {
			return;
		}

		$preferences = self::consent_granted('preferences') ? 'granted' : 'denied';
		$statistics = self::consent_granted('statistics') ? 'granted' : 'denied';
		$marketing = self::consent_granted('marketing') ? 'granted' : 'denied';
		?>
		<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('consent', 'default', {
			'ad_storage': 'denied',
			'ad_user_data': 'denied',
			'ad_personalization': 'denied',
			'analytics_storage': 'denied',
			'functionality_storage': 'denied',
			'personalization_storage': 'denied',
			'security_storage': 'granted',
			'wait_for_update': 500
		});
		gtag('consent', 'update', {
			'ad_storage': '<?php echo esc_js($marketing); ?>',
			'ad_user_data': '<?php echo esc_js($marketing); ?>',
			'ad_personalization': '<?php echo esc_js($marketing); ?>',
			'analytics_storage': '<?php echo esc_js($statistics); ?>',
			'functionality_storage': '<?php echo esc_js($preferences); ?>',
			'personalization_storage': '<?php echo esc_js($preferences); ?>',
			'security_storage': 'granted'
		});
		(function(){
			var match = document.cookie.match(new RegExp('(?:^|; )<?php echo esc_js(rawurlencode(self::COOKIE)); ?>=([^;]*)'));
			if (!match) {
				return;
			}
			try {
				var consent = JSON.parse(decodeURIComponent(match[1]));
				var marketing = consent.marketing ? 'granted' : 'denied';
				var statistics = consent.statistics ? 'granted' : 'denied';
				var preferences = consent.preferences ? 'granted' : 'denied';
				gtag('consent', 'update', {
					'ad_storage': marketing,
					'ad_user_data': marketing,
					'ad_personalization': marketing,
					'analytics_storage': statistics,
					'functionality_storage': preferences,
					'personalization_storage': preferences,
					'security_storage': 'granted'
				});
			} catch (e) {}
		}());
		</script>
		<?php
	}

	public static function enqueue_assets() {
		if (is_admin()) {
			return;
		}

		$settings = self::settings();
		wp_enqueue_script(
			'simple-privacy-cookie-policy',
			plugins_url('assets/simple-privacy-cookie-policy.js', __FILE__),
			array(),
			self::VERSION,
			true
		);
		wp_localize_script('simple-privacy-cookie-policy', 'SimplePrivacyCookiePolicyConfig', array(
			'cookieName' => self::COOKIE,
			'version' => $settings['cookie_version'],
			'days' => absint($settings['consent_days']),
			'privacyUrl' => esc_url_raw(self::privacy_url($settings)),
			'cookiePolicyUrl' => esc_url_raw($settings['cookie_policy_url']),
			'accessibilityStatementUrl' => esc_url_raw($settings['accessibility_statement_url']),
			'hasConsent' => ! empty(self::read_consent_cookie()),
		));
	}

	public static function prepare_facebook_embeds($content) {
		if (is_admin() || ! is_string($content) || false === stripos($content, 'fb-page')) {
			return $content;
		}

		$content = preg_replace(
			'#<script\b[^>]*\bsrc=(["\'])https?://connect\.facebook\.net/[^"\']+/sdk\.js[^"\']*\1[^>]*>\s*</script>#is',
			'',
			$content
		);
		$content = preg_replace(
			'#<div\b[^>]*\bid=(["\'])fb-root\1[^>]*>\s*</div>#is',
			'',
			$content
		);

		if (false !== stripos($content, 'data-lde-facebook-embed')) {
			return $content;
		}

		return preg_replace_callback(
			'#<div\b(?=[^>]*\bclass\s*=\s*(["\'])[^"\']*\bfb-page\b[^"\']*\1)[^>]*>.*?</div>#is',
			array(__CLASS__, 'wrap_facebook_embed'),
			$content
		);
	}

	private static function wrap_facebook_embed($matches) {
		$markup = $matches[0];
		$href = self::facebook_embed_attribute($markup, 'data-href');
		if (! $href) {
			$href = self::facebook_embed_attribute($markup, 'cite');
		}
		if (! $href) {
			$href = self::facebook_link_from_markup($markup);
		}

		$href = esc_url_raw($href);
		if (! $href) {
			return $markup;
		}

		$title = self::facebook_title_from_markup($markup);
		$markup = self::sanitize_facebook_embed_markup($markup);

		return sprintf(
			'<div class="lde-social-embed lde-social-embed--facebook" data-lde-facebook-embed data-lde-facebook-page="%1$s" data-lde-facebook-title="%2$s"><div class="lde-social-embed__placeholder" data-lde-facebook-placeholder><p class="lde-social-embed__kicker">Facebook</p><p class="lde-social-embed__title">%3$s</p><p class="lde-social-embed__message" data-lde-facebook-message>Per visualizzare il contenuto Facebook serve il consenso marketing.</p><div class="lde-social-embed__actions"><button type="button" class="lde-social-embed__button" data-lde-cookie-open>Gestisci consenso</button><a class="lde-social-embed__link" href="%1$s" target="_blank" rel="noopener noreferrer">Apri su Facebook</a></div></div><div class="lde-social-embed__content" data-lde-facebook-content hidden>%4$s</div></div>',
			esc_url($href),
			esc_attr($title),
			esc_html($title),
			$markup
		);
	}

	private static function facebook_embed_attribute($markup, $attribute) {
		if (preg_match('/\s'. preg_quote($attribute, '/') .'\s*=\s*(["\'])(.*?)\1/is', $markup, $match)) {
			return html_entity_decode($match[2], ENT_QUOTES, get_bloginfo('charset'));
		}

		return '';
	}

	private static function facebook_link_from_markup($markup) {
		if (preg_match('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/is', $markup, $match)) {
			return html_entity_decode($match[2], ENT_QUOTES, get_bloginfo('charset'));
		}

		return '';
	}

	private static function facebook_title_from_markup($markup) {
		if (preg_match('/<a\b[^>]*>(.*?)<\/a>/is', $markup, $match)) {
			$title = trim(wp_strip_all_tags($match[1]));
			if ('' !== $title) {
				return html_entity_decode($title, ENT_QUOTES, get_bloginfo('charset'));
			}
		}

		return 'Pagina Facebook';
	}

	private static function sanitize_facebook_embed_markup($markup) {
		return wp_kses($markup, array(
			'div' => array(
				'class' => true,
				'data-href' => true,
				'data-tabs' => true,
				'data-width' => true,
				'data-height' => true,
				'data-small-header' => true,
				'data-adapt-container-width' => true,
				'data-hide-cover' => true,
				'data-show-facepile' => true,
			),
			'blockquote' => array(
				'cite' => true,
				'class' => true,
			),
			'a' => array(
				'href' => true,
				'target' => true,
				'rel' => true,
			),
		));
	}

	public static function render_accessibility_footer_link() {
		if (is_admin()) {
			return;
		}

		$settings = self::settings();
		if ('1' !== $settings['show_accessibility_footer_link'] || empty($settings['accessibility_statement_url'])) {
			return;
		}

		?>
		<div class="lde-accessibility-footer-link">
			<?php self::render_document_link($settings['accessibility_statement_url'], 'Dichiarazione di accessibilita', 'accessibility', self::should_open_document_popup($settings['accessibility_statement_url'])); ?>
		</div>
		<?php
	}

	private static function print_inline_css() {
		static $printed = false;
		if ($printed) {
			return;
		}

		$css_path = plugin_dir_path(__FILE__) .'assets/simple-privacy-cookie-policy.css';
		if (! is_readable($css_path)) {
			return;
		}

		$printed = true;
		printf(
			'<style id="simple-privacy-cookie-policy-inline-css">%s</style>' . "\n",
			wp_strip_all_tags((string) file_get_contents($css_path))
		);
	}

	public static function render_consent_ui() {
		if (is_admin()) {
			return;
		}

		$settings = self::settings();
		$privacy_url = self::privacy_url($settings);
		$privacy_popup = 'external' === ($settings['privacy_policy_mode'] ?? 'generated') ? false : self::should_open_document_popup($privacy_url);
		$cookie_popup = self::should_open_document_popup($settings['cookie_policy_url'] ?? '');
		$accessibility_popup = self::should_open_document_popup($settings['accessibility_statement_url'] ?? '');
		$has_consent = ! empty(self::read_consent_cookie());
		$template = sanitize_key($settings['visual_template'] ?? 'glass');
		if (! isset(self::visual_templates()[$template])) {
			$template = 'glass';
		}
		self::print_inline_css();
		?>
		<div id="cmplz-manage-consent-container" class="lde-cookie lde-cookie--<?php echo esc_attr($template); ?>" data-lde-cookie-root style="<?php echo esc_attr(self::visual_style_attribute($settings)); ?>">
			<div class="lde-cookie__banner" role="region" aria-label="Preferenze privacy e cookie" aria-live="polite" <?php echo $has_consent ? 'hidden' : ''; ?>>
				<button type="button" class="lde-cookie__banner-close" data-lde-cookie-reject aria-label="Chiudi banner e mantieni solo i cookie necessari">x</button>
				<div class="lde-cookie__content">
					<p class="lde-cookie__title">Privacy e cookie</p>
					<p>Usiamo cookie tecnici necessari. Con il tuo consenso possiamo usare anche preferenze, statistiche e marketing. Puoi accettare, rifiutare o scegliere per finalita.</p>
					<p class="lde-cookie__links">
						<?php self::render_document_link($privacy_url, 'Privacy policy', 'privacy', $privacy_popup); ?>
						<?php self::render_document_link($settings['cookie_policy_url'], 'Cookie policy', 'cookie-policy', $cookie_popup); ?>
						<?php self::render_document_link($settings['accessibility_statement_url'], 'Accessibilita', 'accessibility', $accessibility_popup); ?>
					</p>
				</div>
				<div class="lde-cookie__actions">
					<button type="button" class="lde-cookie__button lde-cookie__button--ghost" data-lde-cookie-reject>Rifiuta non essenziali</button>
					<button type="button" class="lde-cookie__button lde-cookie__button--ghost" data-lde-cookie-open>Personalizza</button>
					<button type="button" class="lde-cookie__button" data-lde-cookie-accept>Accetta tutto</button>
				</div>
			</div>

			<button type="button" class="lde-cookie__reopen cc-cookie-link" data-lde-cookie-open data-cc-open-consent aria-label="Gestisci preferenze cookie">Preferenze cookie</button>

			<div class="lde-cookie__modal" data-lde-cookie-modal hidden>
				<div class="lde-cookie__backdrop" data-lde-cookie-close></div>
				<div class="lde-cookie__dialog" role="dialog" aria-modal="true" aria-labelledby="lde-cookie-title" aria-describedby="lde-cookie-description" tabindex="-1">
					<div class="lde-cookie__dialog-head">
						<h2 id="lde-cookie-title">Preferenze cookie</h2>
						<button type="button" class="lde-cookie__close" data-lde-cookie-close aria-label="Chiudi preferenze cookie">x</button>
					</div>
					<p id="lde-cookie-description">Puoi modificare il consenso in qualsiasi momento. I cookie necessari restano sempre attivi per sicurezza e funzionamento del sito.</p>
					<div class="lde-cookie__choices">
						<?php self::render_choice('necessary', 'Necessari', 'Sempre attivi. Servono per sicurezza, consenso, sessione e funzioni richieste.', true); ?>
						<?php self::render_choice('preferences', 'Preferenze', 'Memorizzano scelte non essenziali, come impostazioni di visualizzazione o preferenze locali.', false); ?>
						<?php self::render_choice('statistics', 'Statistiche', 'Aiutano a capire come viene usato il sito, solo dopo consenso.', false); ?>
						<?php self::render_choice('marketing', 'Marketing', 'Permettono contenuti o misurazioni pubblicitarie e tracciamenti di terze parti, solo dopo consenso.', false); ?>
					</div>
					<p class="lde-cookie__policy-links">
						<span>Documenti:</span>
						<?php self::render_document_link($privacy_url, 'Privacy policy', 'privacy', $privacy_popup); ?>
						<?php self::render_document_link($settings['cookie_policy_url'], 'Cookie policy', 'cookie-policy', $cookie_popup); ?>
						<?php self::render_document_link($settings['accessibility_statement_url'], 'Accessibilita', 'accessibility', $accessibility_popup); ?>
					</p>
					<div class="lde-cookie__actions lde-cookie__actions--dialog">
						<button type="button" class="lde-cookie__button lde-cookie__button--ghost" data-lde-cookie-reject>Rifiuta non essenziali</button>
						<button type="button" class="lde-cookie__button lde-cookie__button--ghost" data-lde-cookie-save>Salva scelte</button>
						<button type="button" class="lde-cookie__button" data-lde-cookie-accept>Accetta tutto</button>
					</div>
				</div>
			</div>

			<?php if ($privacy_popup) : ?>
				<div class="lde-cookie__modal" data-lde-privacy-modal hidden>
					<div class="lde-cookie__backdrop" data-lde-privacy-close></div>
					<div class="lde-cookie__dialog lde-cookie__dialog--policy" role="dialog" aria-modal="true" aria-labelledby="lde-privacy-title" aria-describedby="lde-privacy-description" tabindex="-1">
						<div class="lde-cookie__dialog-head">
							<h2 id="lde-privacy-title">Privacy policy</h2>
							<button type="button" class="lde-cookie__close" data-lde-privacy-close aria-label="Chiudi privacy policy">x</button>
						</div>
						<div id="lde-privacy-description" class="lde-cookie__policy-body" data-lde-privacy-body></div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($cookie_popup) : ?>
				<div class="lde-cookie__modal" data-lde-cookie-policy-modal hidden>
					<div class="lde-cookie__backdrop" data-lde-cookie-policy-close></div>
					<div class="lde-cookie__dialog lde-cookie__dialog--policy" role="dialog" aria-modal="true" aria-labelledby="lde-cookie-policy-title" aria-describedby="lde-cookie-policy-description" tabindex="-1">
						<div class="lde-cookie__dialog-head">
							<h2 id="lde-cookie-policy-title">Cookie policy</h2>
							<button type="button" class="lde-cookie__close" data-lde-cookie-policy-close aria-label="Chiudi cookie policy">x</button>
						</div>
						<div id="lde-cookie-policy-description" class="lde-cookie__policy-body" data-lde-cookie-policy-body></div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($accessibility_popup) : ?>
				<div class="lde-cookie__modal" data-lde-accessibility-modal hidden>
					<div class="lde-cookie__backdrop" data-lde-accessibility-close></div>
					<div class="lde-cookie__dialog lde-cookie__dialog--policy" role="dialog" aria-modal="true" aria-labelledby="lde-accessibility-title" aria-describedby="lde-accessibility-description" tabindex="-1">
						<div class="lde-cookie__dialog-head">
							<h2 id="lde-accessibility-title">Dichiarazione di accessibilita</h2>
							<button type="button" class="lde-cookie__close" data-lde-accessibility-close aria-label="Chiudi dichiarazione di accessibilita">x</button>
						</div>
						<div id="lde-accessibility-description" class="lde-cookie__policy-body" data-lde-accessibility-body></div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ($privacy_popup) : ?>
				<template data-lde-privacy-template>
					<?php echo self::privacy_policy_markup(false); ?>
				</template>
			<?php endif; ?>

			<?php if ($cookie_popup) : ?>
				<template data-lde-cookie-policy-template>
					<?php echo self::cookie_policy_markup(false, false); ?>
				</template>
			<?php endif; ?>

			<?php if ($accessibility_popup) : ?>
				<template data-lde-accessibility-template>
					<?php echo self::accessibility_statement_markup(false); ?>
				</template>
			<?php endif; ?>
		</div>
		<?php
	}

	private static function render_document_link($url, $label, $document, $open_popup) {
		$url = esc_url($url);
		$label = esc_html($label);
		$attrs = '';
		if ($open_popup) {
			if ('privacy' === $document) {
				$attrs = ' data-lde-privacy-open aria-haspopup="dialog"';
			} elseif ('cookie-policy' === $document) {
				$attrs = ' data-lde-cookie-policy-open aria-haspopup="dialog"';
			} elseif ('accessibility' === $document) {
				$attrs = ' data-lde-accessibility-open aria-haspopup="dialog"';
			}
		}

		echo '<a href="'. $url .'"'. $attrs .'>'. $label .'</a>';
	}

	private static function render_choice($key, $label, $description, $required) {
		$checked = $required || self::consent_granted($key);
		?>
		<label class="lde-cookie__choice">
			<span>
				<strong><?php echo esc_html($label); ?></strong>
				<small><?php echo esc_html($description); ?></small>
			</span>
			<input type="checkbox" data-lde-cookie-category="<?php echo esc_attr($key); ?>" <?php checked($checked); ?> <?php disabled($required); ?>>
		</label>
		<?php
	}

	public static function start_iubenda_strip_buffer() {
		$settings = self::settings();
		if (is_admin() || wp_doing_ajax() || '1' !== $settings['strip_iubenda']) {
			return;
		}

		ob_start(array(__CLASS__, 'strip_iubenda_markup'));
	}

	public static function strip_iubenda_markup($html) {
		if (! is_string($html) || false === stripos($html, 'iubenda')) {
			return $html;
		}

		$replacement = '<button type="button" class="lde-cookie-settings-link" data-lde-cookie-open data-cc-open-consent>Preferenze cookie</button>';
		$html = preg_replace('#<a\b[^>]*href=["\']https?://www\.iubenda\.com/privacy-policy/[^"\']*/cookie-policy["\'][^>]*>.*?</a>#is', $replacement, $html);
		$html = preg_replace('#<script\b[^>]*>\s*\(function\s*\(w,d\).*?cdn\.iubenda\.com/iubenda\.js.*?</script>#is', '', $html);
		$html = preg_replace('#<script\b[^>]*>\s*var\s+_iub\s*=.*?_iub\.csConfiguration\s*=.*?</script>#is', '', $html);
		$html = preg_replace('#<script\b[^>]+src=["\'](?:https?:)?//cdn\.iubenda\.com/[^"\']+["\'][^>]*>\s*</script>#is', '', $html);

		return $html;
	}

	public static function settings_shortcode() {
		return '<button type="button" class="lde-cookie-settings-link" data-lde-cookie-open data-cc-open-consent>Preferenze cookie</button>';
	}

	public static function cookie_policy_shortcode() {
		return self::cookie_policy_markup(true, true);
	}

	private static function cookie_policy_markup($include_title = true, $include_settings_link = true) {
		$settings = self::settings();
		return self::document_markup_from_url(
			$settings['cookie_policy_url'] ?? '',
			'cookie',
			self::generated_cookie_policy_markup($include_title, $include_settings_link),
			$include_title
		);
	}

	private static function generated_cookie_policy_markup($include_title = true, $include_settings_link = true) {
		$settings = self::settings();
		$scan = self::get_scan_data();
		$cookies = ! empty($scan['cookies']) ? $scan['cookies'] : self::fallback_policy_cookies();
		$services = ! empty($scan['services']) ? $scan['services'] : array();

		return SPCP_Legal_Generator::cookie_policy_markup(
			$settings,
			$scan,
			$cookies,
			$services,
			$include_title,
			$include_settings_link,
			self::settings_shortcode()
		);
	}

	private static function fallback_policy_cookies() {
		return array(
			array(
				'name' => self::COOKIE,
				'provider' => 'Prima parte',
				'category' => 'Necessari',
				'purpose' => 'Memorizza le preferenze di consenso cookie dell\'utente.',
				'duration' => absint(self::settings()['consent_days']) .' giorni',
				'source' => 'WPSimpleCompliance',
			),
		);
	}

	private static function format_scan_date($date) {
		$timestamp = strtotime((string) $date);
		if (! $timestamp) {
			return (string) $date;
		}

		return date_i18n('d/m/Y H:i', $timestamp);
	}

	public static function privacy_policy_shortcode() {
		return self::privacy_policy_markup(true);
	}

	private static function privacy_policy_markup($include_title = true) {
		$settings = self::settings();
		return self::document_markup_from_url(
			self::privacy_url($settings),
			'privacy',
			self::generated_privacy_policy_markup($include_title),
			$include_title
		);
	}

	private static function generated_privacy_policy_markup($include_title = true) {
		return SPCP_Legal_Generator::privacy_policy_markup(self::settings(), self::settings_shortcode(), $include_title);
	}

	public static function accessibility_statement_shortcode() {
		return self::accessibility_statement_markup(true);
	}

	private static function accessibility_statement_markup($include_title = true) {
		$settings = self::settings();
		return self::document_markup_from_url(
			$settings['accessibility_statement_url'] ?? '',
			'accessibility',
			self::generated_accessibility_statement_markup($include_title),
			$include_title
		);
	}

	private static function generated_accessibility_statement_markup($include_title = true) {
		$settings = self::settings();
		$email = is_email($settings['accessibility_contact_email']) ? $settings['accessibility_contact_email'] : self::default_contact_email();
		$name = $settings['controller_legal_name'] ?: $settings['controller_name'];
		ob_start();
		?>
		<section class="simple-policy simple-policy--accessibility lde-policy">
			<?php if ($include_title) : ?><h2>Dichiarazione di accessibilita</h2><?php endif; ?>
			<p><?php echo esc_html($name); ?> si impegna a rendere questo sito accessibile e usabile secondo i requisiti tecnici applicabili ai servizi digitali.</p>
			<p>Stato: parzialmente conforme in attesa di verifica manuale completa su contenuti redazionali, documenti caricati, contrasti, navigazione da tastiera e tecnologie assistive.</p>
			<p>Il banner cookie, il pannello preferenze e la cookie policy sono progettati con dialog accessibili, gestione tastiera, focus visibile e preferenze modificabili in qualsiasi momento.</p>
			<?php if ($email) : ?>
				<p>Per segnalazioni su barriere di accessibilita puoi scrivere a <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>.</p>
			<?php endif; ?>
			<p>Ultimo aggiornamento tecnico: <?php echo esc_html(date_i18n('d/m/Y')); ?>.</p>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function document_markup_from_url($url, $type, $fallback_markup, $include_title = true) {
		$page_id = absint(url_to_postid((string) $url));
		if (! $page_id) {
			return $fallback_markup;
		}

		$page = get_post($page_id);
		if (! $page || 'page' !== $page->post_type || 'publish' !== get_post_status($page)) {
			return $fallback_markup;
		}

		if (self::contains_policy_shortcode($page)) {
			return $fallback_markup;
		}

		$content = self::render_page_content($page);
		if (! trim(wp_strip_all_tags($content))) {
			return $fallback_markup;
		}

		$type = sanitize_html_class($type);
		ob_start();
		?>
		<section class="simple-policy simple-policy--<?php echo esc_attr($type); ?> simple-policy--custom">
			<?php if ($include_title) : ?><h2><?php echo esc_html(get_the_title($page)); ?></h2><?php endif; ?>
			<div class="simple-policy__custom-content">
				<?php echo $content; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function render_page_content($page) {
		global $post;

		$previous_post = $post;
		$post = $page;
		setup_postdata($post);
		$content = apply_filters('the_content', $page->post_content);
		wp_reset_postdata();
		$post = $previous_post;

		return (string) $content;
	}
}

register_activation_hook(__FILE__, array('Simple_Privacy_Cookie_Policy', 'activate'));
register_deactivation_hook(__FILE__, array('Simple_Privacy_Cookie_Policy', 'deactivate'));
Simple_Privacy_Cookie_Policy::init();
