<?php

namespace JetBackup;

use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class JetBackup {
	
	private function __construct() {}

	const VERSION = '3.1.16.1';
	const DEVELOPMENT = false;

	const DEFAULT_LANGUAGE = 'en_US';
	const NONCE_COOKIE_NAME = 'wp-jetbackup-nonce';
	const LANG_COOKIE_NAME = 'wp-jetbackup-user-language';

	const MINIMUM_PHP_VERSION = '7.4';
	const MINIMUM_WP_VERSION = '6.0';
	const TESTED_ON_WP_VERSION = '6.8.0';
	const PLUGIN_CONFLICTS = [
		'backup-guard-gold' . self::SEP . 'BackupGuard.php',
		'backup-guard-platinum' . self::SEP . 'BackupGuard.php',
		'backup-guard-silver' . self::SEP . 'BackupGuard.php',
	];

	const SEP = DIRECTORY_SEPARATOR;

	const WP_ROOT_PATH = WP_ROOT;
	const ROOT_PATH = JB_ROOT;

	const PLUGIN_NAME = 'backup';
	const PLUGIN_EXT_NAME = 'JetBackup';
	const PLUGIN_SLUG = self::PLUGIN_NAME . self::SEP . self::PLUGIN_NAME . '.php';
	const CRON_PUBLIC_URL = '/' . Wordpress::WP_CONTENT . '/' . Wordpress::WP_PLUGINS . '/' . JetBackup::PLUGIN_NAME . '/public/cron';

	const ID_FIELD = '_id';
	
	const SRC_PATH = self::ROOT_PATH . self::SEP . 'src' . self::SEP . self::PLUGIN_EXT_NAME;
	const TRDPARTY_PATH = self::SRC_PATH . self::SEP . '3rdparty';
	const PUBLIC_PATH = self::ROOT_PATH . self::SEP . 'public';
	const CRON_PATH = self::PUBLIC_PATH . self::SEP . 'cron';
	const CONFIG_PATH = self::ROOT_PATH . self::SEP . 'config';
	const TEMPLATES_PATH = self::ROOT_PATH . self::SEP . 'templates';

}
