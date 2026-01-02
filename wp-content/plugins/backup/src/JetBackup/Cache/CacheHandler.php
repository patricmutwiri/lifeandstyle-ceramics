<?php

namespace JetBackup\Cache;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

/**
 * Always bypass Redis object cache and OPcache in the admin area for JetBackup specific plugin operations.
 */
class CacheHandler {

	private static bool $object_cache_enabled = false;

	/**
	 * Check if object cache is enabled
	 *
	 * @return bool
	 */
	public static function is_object_cache_enabled(): bool {
		global $wp_object_cache;
		return isset($wp_object_cache) && is_object($wp_object_cache);
	}

	/**
	 * Check if OPCache is enabled
	 *
	 * @return bool
	 */
	public static function is_opcache_enabled(): bool {
		return function_exists('opcache_get_status');
	}

	/**
	 * Disable object cache
	 */
	public static function disable_object_cache() {
		if (!self::is_object_cache_enabled()) return false;

		add_filter('pre_wp_cache_get', '__return_false');
		add_filter('pre_transient_*', '__return_false', 10, 2);
		add_filter('pre_site_transient_*', '__return_false', 10, 2);
		self::$object_cache_enabled = true;
	}

	/**
	 * Enable object cache
	 */
	public static function enable_object_cache() {
		if (!self::$object_cache_enabled) return;

		remove_filter('pre_wp_cache_get', '__return_false');
		remove_filter('pre_transient_*', '__return_false', 10, 2);
		remove_filter('pre_site_transient_*', '__return_false', 10, 2);
		self::$object_cache_enabled = false;
	}

	/**
	 * Disable OPCache
	 */
	public static function disable_opcache() {
		if (!self::is_opcache_enabled() || !function_exists('ini_set')) return;
		ini_set('opcache.enable', 0);
	}


	/**
	 * Pre-cache operations: disable caches if they are enabled
	 */
	public static function pre() {
		if (self::is_object_cache_enabled()) self::disable_object_cache();
		if (self::is_opcache_enabled()) self::disable_opcache();
	}

	/**
	 * Post-cache operations: re-enable caches if they were disabled
	 */
	public static function post() {
		self::enable_object_cache();
	}

}