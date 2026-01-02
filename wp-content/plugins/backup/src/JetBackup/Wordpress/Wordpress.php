<?php

namespace JetBackup\Wordpress;

use JetBackup\Entities\Util;
use JetBackup\Factory;
use JetBackup\JetBackup;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Wordpress {

	private function __construct() {}

	const WP_CONTENT = 'wp-content';
	const WP_PLUGINS = 'plugins';

	public static function getLocale(): string {
		return self::getLangCookieValue() ?? JetBackup::DEFAULT_LANGUAGE;
	}

	public static function getAuthSalt(): string {
		return defined('AUTH_SALT') ? AUTH_SALT : Factory::getConfig()->getEncryptionKey();
	}

	public static function getAuthKey(): string {
		return defined('AUTH_KEY') ? AUTH_KEY : Factory::getConfig()->getEncryptionKey();
	}

	public static function strContains(string $haystack, string $needle): bool {
		if (function_exists('str_contains')) return str_contains($haystack, $needle);
		return ( '' === $needle || false !== strpos( $haystack, $needle ) );
	}

	public static function getAlternateContentDir(): ?string {
		return defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR :  null;
	}

	public static function getRemoteGet(string $url, array $args=[]) {
		return wp_remote_get($url, $args);
	}

	public static function getCurrentScreen():?\WP_Screen {
		if (function_exists('get_current_screen')) return get_current_screen();
		return null;
	}

	public static function getBlogInfo($show):string {
		return get_bloginfo($show);
	}

	public static function getUnslash($value) {
		if (function_exists('wp_unslash')) return wp_unslash($value);
		if (is_array($value)) return array_map([__CLASS__, 'getUnslash'], $value);
		return is_string($value) ? stripslashes($value) : $value;
	}

	public static function getDateFormat() : string {
		return Wordpress::getOption('date_format') ?: 'd/m/Y';
	}

	public static function getTimeFormat() : string {
		return Wordpress::getOption('time_format') ?: 'H:i';
	}

	public static function getOption($option) {
		if (function_exists('get_option')) return get_option($option);
		return null;
	}

	public static function getPlugins(): array {

		if (!function_exists('get_plugins')) {
			$plugin_loader = Factory::getWPHelper()->getWordPressHomedir() . 'wp-admin' . JetBackup::SEP . 'includes' . JetBackup::SEP . 'plugin.php';
			if (file_exists($plugin_loader)) require_once($plugin_loader);
		}

		return get_plugins();
	}

	public static function isPluginActive($plugin):bool {
		return is_plugin_active($plugin);
	}
	
	public static function getTransient($transient) {
		return get_transient($transient);
	}

	public static function setTransient($transient, $value, $expiration=0) {
		set_transient($transient, $value, $expiration);
	}

	public static function isSuperAdmin($userid=false): bool {
		return function_exists('is_super_admin') && is_super_admin($userid);
	}

	public static function getCurrentUser():?\WP_User {
		if (function_exists('wp_get_current_user')) return wp_get_current_user();
		return null;
	}
	
	public static function getUploadDir(): ?array {
		if (function_exists('wp_get_upload_dir')) return wp_get_upload_dir();
		return null;
	}

	public static function copyDir($source, $target) {
		if (function_exists('copy_dir')) return copy_dir($source, $target);
		return false;
	}

	public static function verifyNonce($nonce): bool {
		if (!function_exists('wp_verify_nonce')) {
			return false;
		}
		return wp_verify_nonce($nonce, 'jetbackup_nonce_' . (self::getNonceCookieValue() ?? ''));
	}

	public static function createNonce(): string {
		if (!function_exists('wp_create_nonce')) {
			return '';
		}
		return wp_create_nonce('jetbackup_nonce_' . (self::getNonceCookieValue() ?? ''));
	}


	public static function updateUserMeta ($user_id, $meta_key, $meta_value, $prev_value = '') {
		if (function_exists('update_user_meta')) return update_user_meta($user_id, $meta_key, $meta_value, $prev_value);
		return false;
	}

	public static function deleteUserMeta ($user_id, $meta_key): bool {
		if (function_exists('delete_user_meta')) return delete_user_meta($user_id, $meta_key);
		return false;
	}

	public static function getUserMeta ($user_id, $key, $single) {
		if (function_exists('get_user_meta')) return get_user_meta($user_id, $key, $single);
		return '';

	}

	public static function isDebugModeEnabled(): bool {
		return defined('WP_DEBUG') && WP_DEBUG;
	}


	public static function wpLogout() : void {wp_logout();}
	public static function wpRedirect($location) : void {wp_redirect($location);}
	public static function wpLoginURL($redirect = '', $force_reauth = false) : string {
		return wp_login_url($redirect, $force_reauth);
	}

	public static function text($text): string {
		return esc_html__($text,'jetbackup-plugin');
	}

	private static function getNonceCookieValue(): ?string {
		return isset($_COOKIE[JetBackup::NONCE_COOKIE_NAME])
			? self::getUnslash($_COOKIE[JetBackup::NONCE_COOKIE_NAME])
			: null;
	}

	public static function setNonceCookie() {
		if (!Helper::isCLI() && !self::getNonceCookieValue()) {
			setcookie(
				JetBackup::NONCE_COOKIE_NAME,
				Util::generateRandomString(),
				time() + 7200,
				COOKIEPATH,
				COOKIE_DOMAIN,
				WordPress::isSSL(),
				true
			);
		}
	}


	private static function getLangCookieValue(): ?string {
		return isset($_COOKIE[JetBackup::LANG_COOKIE_NAME])
			? self::getUnslash($_COOKIE[JetBackup::LANG_COOKIE_NAME])
			: null;
	}

	public static function setUserLanguageCookie() {
		if (
			!Helper::isCLI() &&
			(
				!self::getLangCookieValue() ||
				self::getLocale() !== self::getUserLocale()
			)
		) {
			setcookie(
				JetBackup::LANG_COOKIE_NAME,
				self::getUserLocale(),
				time() + 7200,
				COOKIEPATH,
				COOKIE_DOMAIN,
				WordPress::isSSL(),
				true
			);
		}
	}


	public static function getUserLocale(): string {
		if (function_exists('get_user_locale')) return get_user_locale();
		return JetBackup::DEFAULT_LANGUAGE;
	}

	public static function sendMail($to, $subject, $message, $headers = [], $attachments = '') {
		return wp_mail($to, $subject, $message, $headers, $attachments);
	}

	public static function sanitizeEmail($email): string {
		return sanitize_email($email);
	}

	public static function isSSL(): bool {
		if (function_exists('is_ssl')) return is_ssl();
		return false;
	}

	public static function isEmail($email): bool {
		if (function_exists('is_email')) return is_email($email);
		return filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	public static function sanitizeTextField($str): string {
		if (function_exists('sanitize_text_field')) return sanitize_text_field($str);

		// Fallback sanitization: basic cleanup
		$str = is_string($str) ? $str : (string) $str;
		$str = strip_tags($str);
		$str = trim($str);
		$str = preg_replace('/[\r\n\t]+/', ' ', $str);

		return $str;
	}


	public static function getVersion (): ?string {
		global $wp_version;
		return $wp_version;
	}

	public static function setOption($key, $value) {
		update_option($key, $value);
	}

	public static function deleteOption($key) {
		delete_option($key);
	}

	/**
	 * @return string
	 * Returns clean domain only (not http prefix)
	 * Example: mydomain.com
	 */
	public static function getSiteDomain():string {
		return preg_replace('#^http[s]?://#', '', self::getSiteURL());
	}


	/**
	 * @return string
	 * Returns full site url, including http prefix
	 * Example: https://www.mydomain.com
	 */
	public static function getSiteURL(): string {
		$site_url = function_exists('get_site_url') ? get_site_url() : null;
		if ($site_url) return $site_url;

		// Fallback
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
		$host = Wordpress::sanitizeTextField($host);

		$script_path = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? $_SERVER['REQUEST_URI'] ?? '';
		$script_path = Wordpress::sanitizeTextField($script_path);
		$script_dir = dirname($script_path);

		return rtrim("$protocol://$host$script_dir", '/');
	}

	public static function getAdminURL():string {

		$admin_url = function_exists('get_admin_url') ? get_admin_url() : null;
		if(!$admin_url) {
			$admin_url = self::getSiteURL() . '/wp-admin';
		}
		return $admin_url;
	}

	public static function getDB():MySQL {
		static $i;
		if(!$i) $i = new MySQL();
		return $i;
	}

	/**
	 * @return Blog[]
	 */
	public static function getMultisiteBlogs():array {
		$output = [];

		$db = self::getDB();
		$prefix = $db->getPrefix();
		$blogsTable = "{$prefix}blogs";

		// Check if the blogs table exists
		try {
			$sql = "SHOW TABLES LIKE '" . $db->escapeSql($blogsTable) . "'";
			$result = $db->query($sql, [], ARRAY_N);
			if (empty($result)) return $output; // Return empty if table doesn't exist
		} catch (\Exception $e) {
			return $output;
		}

		try {
			$sql = "SELECT blog_id AS id, domain FROM {$blogsTable}";
			$blogs = $db->query($sql, [], ARRAY_A);
		} catch (\Exception $e) {
			return $output;
		}

		foreach ($blogs as $blog_details) {
			if (!$blog_details['id'] || !$blog_details['domain']) continue;

			$blog = new Blog();
			$blog->setId($blog_details['id']);
			$blog->setDomain($blog_details['domain']);

			try {
				$sql = "SHOW TABLES LIKE '" . $db->escapeSql($prefix . (!$blog->isMain() ? $blog->getId() . '_' : '')) . "%'";
				$tables = $db->query($sql, [], ARRAY_N);
			} catch(\Exception $e) {
				$tables = [];
			}

			foreach ($tables as $table) {
				if (
					!isset($table[0]) ||
					// Filter out tables not specific to the main site
					($blog->isMain() && preg_match('/_\d+_/', $table[0]))
				) continue;

				$blog->addDatabaseTable($table[0]);
			}

			$output[] = $blog;
		}

		return $output;
	}
}
