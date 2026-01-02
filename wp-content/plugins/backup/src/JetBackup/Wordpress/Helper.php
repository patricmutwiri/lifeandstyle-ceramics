<?php

namespace JetBackup\Wordpress;

use Exception;
use JetBackup\Alert\Alert;
use JetBackup\Entities\Util;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use WP_User;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Helper {

	private string $_homedir='';

	private const SUPPORT_USER_TTL = 30 * 24 * 60 * 60; // 30 days in seconds

	const MYSQL_AUTH_PATTERNS = [
		'db_name'      => "/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]((?:\\\\.|[^'\"])+)['\"]\s*\)\s*;/",
		'db_user'      => "/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]((?:\\\\.|[^'\"])+)['\"]\s*\)\s*;/",
		'db_password'  => "/define\s*\(\s*['\"]DB_PASSWORD['\"]\s*,\s*['\"]((?:\\\\.|[^'\"])+)['\"]\s*\)\s*;/",
		'db_host'      => "/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]((?:\\\\.|[^'\"])+)['\"]\s*\)\s*;/",
		'table_prefix' => "/^\s*\\\$table_prefix\s*=\s*['\"]((?:\\\\.|[^'\"])+)['\"]\s*;/",
	];

	public static function isMainSite(): bool {
		return (function_exists('is_main_site') && is_main_site());
	}

	public static function getMainSiteId(): int {
		$main_site_id = 1;
		if (function_exists('get_main_site_id')) return get_main_site_id();
		if (function_exists('get_site_option')) $main_site_id = (int) get_site_option('main_site_id');
		if ($main_site_id > 0) return $main_site_id;
		return $main_site_id;
	}

	public static function isMultisite(): bool {
		if (function_exists('is_multisite') && is_multisite()) return true;
		if (defined( 'MULTISITE')) return MULTISITE;
		if (defined( 'SUBDOMAIN_INSTALL') || defined( 'VHOST') || defined( 'SUNRISE')) return true;
		return false;
	}

	public static function isNetworkAdminUser(): bool {
		return function_exists('current_user_can') && current_user_can('manage_network') &&
		       function_exists('is_super_admin') && is_super_admin();
	}

	/**
	 * @throws Exception
	 */
	public static function getWordpressUser($username) : ?array {

		if(!function_exists('get_user_by')) throw new Exception("Function get_user_by() not defined");
		if(!function_exists('wp_set_password')) throw new Exception("Function wp_set_password() not defined");

		$user = get_user_by('login', $username);
		if(!$user) return null;


		$password = Util::generatePassword();
		wp_set_password($password, $user->ID);

		return [
			'username' => $user->user_login,
			'email' => $user->user_email,
			'password' => $password,
			'wordpress_admin_url' =>  Wordpress::getAdminURL(),

		];

	}

	/**
	 * @throws Exception
	 */
	public static function clearSupportUser() : void {

		$config = Factory::getConfig();
		if(!$username = $config->getSupportUsername()) return;
		if(!$user = get_user_by('login', $username)) return;
		$user_created_date = $user->data->user_registered ?? null;
		if(!$user_created_date) return;
		if(!$user_created_timestamp = strtotime($user_created_date)) return;

		if ((time() - $user_created_timestamp) > self::SUPPORT_USER_TTL) {

			if (self::isMultisite()) {
				if (!function_exists('wpmu_delete_user')) require_once WP_ROOT . JetBackup::SEP . 'wp-admin' . JetBackup::SEP . 'includes' . JetBackup::SEP . 'ms.php';
				revoke_super_admin($user->ID); // super admin user is protected by wp_delete user, so we need to revoke first
				wpmu_delete_user($user->ID);
			} else {
				if (!function_exists('wp_delete_user')) require_once WP_ROOT . JetBackup::SEP . 'wp-admin' . JetBackup::SEP . 'includes' . JetBackup::SEP . 'user.php';
				wp_delete_user($user->ID);
			}
			$config->setSupportUsername('');
			$config->save();
			Alert::add('System Cleanup', "Temporary support user '{$user->data->user_login}' created 30 days ago removed", Alert::LEVEL_INFORMATION);
		}

	}

	/**
	 * @throws IOException
	 * @throws Exception
	 */
	public static function createSupportUser() : array {

		$config = Factory::getConfig();
		$username = $config->getSupportUsername();
		if ($username && $output = self::getWordpressUser($username)) return $output;

		if(!function_exists('wp_create_user')) throw new Exception("Function wp_create_user() not defined");
		if(!function_exists('grant_super_admin')) throw new Exception("Function grant_super_admin() not defined");

		// Generate new user details
		$email = "support+" . Util::generateRandomString(5) . "@jetbackup.com";
		$password = Util::generatePassword();
		$username = "jetbackup_" . Util::generateRandomString(5);

		// Create new user
		$user_id = wp_create_user($username, $password, $email);
		if (is_wp_error($user_id)) throw new Exception($user_id->get_error_message());

		$user = new WP_User($user_id);
		$user->set_role('administrator');
		if (self::isMultisite()) grant_super_admin($user_id); // Grant network admin privileges

		$config->setSupportUsername($username);
		$config->save();

		return [
			'username' => $user->user_login,
			'email' => $email,
			'password' => $password,
			'wordpress_admin_url' =>  Wordpress::getAdminURL(),
		];

	}

	public static function isAdminUser(): bool {
		return function_exists('current_user_can') && current_user_can('manage_options') &&
		       function_exists('is_super_admin') && is_super_admin();
	}

	/**
	 * Returns true/false if I am in the network admin GUI interface (not if I am network admin USER)
	 * @return bool
	 */
	public static function isNetworkAdminInterface(): bool {
		if (function_exists('is_network_admin') && is_network_admin()) return true;
		if (isset($GLOBALS['current_screen'])) return $GLOBALS['current_screen']->in_admin( 'network' );
		elseif (defined('WP_NETWORK_ADMIN')) return WP_NETWORK_ADMIN;
		return false;
	}

	/**
	 * @param bool $public
	 * Example output: /home/user/public_html/
	 * public flag: public_html (no ending /)
	 * @return string
	 */
	public function getWordPressHomedir(bool $public = false): string {

		if (!$this->_homedir) {
			$homedir = defined('WP_ROOT') ? WP_ROOT : ABSPATH;
			$this->_homedir = rtrim($homedir, JetBackup::SEP) . JetBackup::SEP;
		}

		if ($public) return basename(rtrim($this->_homedir, JetBackup::SEP));
		return $this->_homedir;
	}

	/**
	 * @return string
	 * Returns public restore file location
	 * Example -
	 * Alternate path enabled: /home/user/public_html/wp-content/plugins/backup/public/cron
	 * Alternate path disabled: /home/user/public_html
	 */
	public function getRestoreFileLocation() : string {
		if (Factory::getSettingsRestore()->isRestoreAlternatePathEnabled()) return rtrim(JetBackup::CRON_PATH, JetBackup::SEP);
		return rtrim($this->getWordPressHomedir(), JetBackup::SEP);
	}

	/**
	 * @return string
	 * Return's WordPress public_dir relative to homedir, needed for nested sites (sites inside subfolders)
	 * Example
	 *  - getWordPressHomedir: /home/user/sites/www.mydomain.com/subfolder
	 *  - getUserHomedir: /home/user
	 *  - getWordPressRelativePublicDir: /sites/wp2.jetbackup.com/subfolder
	 */
	public function getWordPressRelativePublicDir() : string  {

		$public_dir = trim($this->getWordPressHomedir(), JetBackup::SEP);
		$getUserHomedir = trim($this->getUserHomedir(), JetBackup::SEP);
		$relative_path = $getUserHomedir;
		if ($public_dir != $getUserHomedir && str_starts_with($public_dir, $getUserHomedir)) {
			$relative_path = trim(substr($public_dir, strlen($getUserHomedir)), JetBackup::SEP);
		}

		return JetBackup::SEP . $relative_path;
	}

	/**
	 * @return string
	 * Example output: /home/user
	 */

	public static function getUserHomedir(): ?string {

		$user_details = Util::getpwuid(Util::geteuid());
		return $user_details['dir'] ?? null;

	}

	/**
	 * @throws Exception
	 */
	public static function parseWpConfig(): \stdClass {

		$config_file = Factory::getSettingsGeneral()->getAlternateWpConfigLocation();
		if(!file_exists($config_file)) throw new Exception("The wp-config.php file does not exist at the specified path. ($config_file)");

		$output = new \stdClass();

		if(!($f = fopen($config_file, 'r'))) throw new Exception("Unable to open the wp-config.php file.");

		$patterns = self::MYSQL_AUTH_PATTERNS;

		while (($line = fgets($f)) !== false) {
			$line = trim($line);
			// Skip commented lines
			if (strpos($line, '//') === 0 || strpos($line, '#') === 0) continue;

			foreach ($patterns as $key => $pattern) {
				if (!preg_match($pattern, $line, $matches)) continue;
				$value = stripcslashes($matches[1]);

				if ($key === 'db_host' && strpos($value, ':') !== false) {
					list($output->db_host, $output->db_port) = explode(':', $value, 2);
				} else {
					$output->{$key} = $value;
				}

				unset($patterns[$key]);
				break; // Break the foreach loop if a pattern matches
			}

			// Break the while loop if all patterns are found
			if (empty($patterns)) break;
		}

		fclose($f);

		if (empty($output)) throw new Exception("Unable to find the database credentials in the wp-config.php file.");

		// Set default port if not found
		if (!isset($output->db_port)) $output->db_port = Factory::getSettingsGeneral()->getMySQLDefaultPort(); // Default MySQL port

		return $output;

	}

	public function getUploadDir(): string {
		$_uploads = Wordpress::getUploadDir()['basedir'] ?? 'uploads';
		return Wordpress::WP_CONTENT . JetBackup::SEP . basename($_uploads);
	}

	public static function validateEmail($email): bool {
		// Ensure $email is an array
		$emails = is_array($email) ? $email : [$email];

		foreach ($emails as $singleEmail) {
			$sanitizedEmail = Wordpress::sanitizeEmail($singleEmail);
			if (!Wordpress::isEmail($sanitizedEmail)) return false; // Invalid email found
		}

		return true;
	}

	static function getCurrentScreen():?string {
		if($screen = Wordpress::getCurrentScreen()) return $screen->id;
		return null;
	}

	public static function getUserId():?int {
		if($user = Wordpress::getCurrentUser()) return $user->ID;
		return null;
	}

	public static function getUserEmail():?string {
		if($user = Wordpress::getCurrentUser()) return $user->user_email;
		return null;
	}

	public static function getUserIP(): ?string {
		$ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
		if ($ip === null) return null;
		//If forwarded IPs are present, take the first one
		if ( Wordpress::strContains( $ip, ',' ) ) {$ip = trim(explode(',', $ip)[0]);}
		$ip = Wordpress::sanitizeTextField($ip);
		if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
		return null;
	}



	public static function isWPCli(): bool {
		return (defined('WP_CLI') && WP_CLI);
	}


	public static function isCLI():bool {

		if (isset($_SERVER['HTTP_TE']) || isset($_SERVER['HTTP_COOKIE']) || isset($_SERVER['HTTP_ACCEPT'])) return false;
		if(self::isWPCli() || in_array( PHP_SAPI, ['cli', 'cli-server']) || defined('STDIN')) return true;
		return isset($_SERVER['argv']) && sizeof($_SERVER['argv']);

	}
}
