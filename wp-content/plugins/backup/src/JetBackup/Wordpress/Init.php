<?php

namespace JetBackup\Wordpress;

use JetBackup\BackupJob\BackupJob;
use JetBackup\CLI\CLI;
use JetBackup\Cron\Cron;
use JetBackup\Destination\Destination;
use JetBackup\Download\Download;
use JetBackup\Downloader\Downloader;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\NotificationException;
use JetBackup\Exception\QueueException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Queue\QueueItem;
use JetBackup\Schedule\Schedule;
use JetBackup\Settings\Updates;
use JetBackup\SGB\Migration;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Init {

	private function __construct() { } // only static method

	/**
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException|DestinationException
	 * @throws DBException
	 * @throws IOException
	 * @throws QueueException
	 * @throws JBException
	 */
	public static function actionInit() {
		if (!function_exists('current_user_can') || !current_user_can('manage_options')) return;

		// Cookie Env
		add_action('wp_loaded',                          ['\JetBackup\Wordpress\Wordpress', 'setNonceCookie']);
		add_action('wp_loaded',                          ['\JetBackup\Wordpress\Wordpress', 'setUserLanguageCookie']);

        if(Helper::isMultisite()) {

			if(!Helper::isMainSite() || !Helper::isNetworkAdminUser()) return;
			if(Helper::isNetworkAdminInterface()) {
				// This will add menu item also in the network admin page
				add_action('network_admin_menu',                    ['\JetBackup\Wordpress\UI', 'main']);
			}

		}

		add_action('admin_menu',                    ['\JetBackup\Wordpress\UI', 'main']);
		add_action('wp_ajax_jetbackup_api',         ['\JetBackup\Ajax\Ajax',    'main']);

        // Load translation files from the 'languages' folder inside the plugin directory.
        load_plugin_textdomain('jetbackup', false,  JetBackup::PLUGIN_NAME.DIRECTORY_SEPARATOR.'languages');


		if (Factory::getSettingsAutomation()->isHeartbeatEnabled()) {
			add_action('admin_footer',                  ['\JetBackup\Wordpress\UI', 'heartbeat']);
			add_action('wp_ajax_jetbackup_heartbeat', ['\JetBackup\Ajax\Ajax', 'heartbeat' ]);
		}

		self::_createWorkingSpace();
		self::_validateWorkingSpace();

		// Migration has to run after workspace created
		(new Migration())->migrate();
		Destination::createDefaultDestination();
		Schedule::createDefaultSchedule();
		
		// Will create the job if not exists
		BackupJob::getDefaultJob();
		BackupJob::getDefaultConfigJob(); // Also create default config export schedule (hidden)
		
		self::_download();
	}

	private static function _download():void {
		try {
			$userInput = new UserInput();
			$userInput->setData($_REQUEST);

			if($download_id = $userInput->getValidated('download_id', 0, UserInput::UINT)) {
				$download = new Download($download_id);
				if(!$download->getId()) throw new JBException('The provided download id not found');
				$download->download();
			}

			if($queue_item_id = $userInput->getValidated('queue_item_id', 0, UserInput::UINT)) {
				$queue_item = new QueueItem($queue_item_id);
				if(!$queue_item->getId()) throw new JBException('The provided queue item id not found');
				$downloader = new Downloader($queue_item->getLogFile());
				$downloader->download();
			}
		} catch(JBException $e) {
			die('Error:' . $e->getMessage());
		}
	}

	private static function _getWorkingSpaceLockFile(): string
	{
		return Factory::getLocations()->getDataDir()
		       . JetBackup::SEP
		       . Factory::getConfig()->getUniqueID()
		       . '.lock';
	}

	private static function _getWorkingSpaceLockFileValue(): string
	{
		$lockFile = self::_getWorkingSpaceLockFile();
		if (!file_exists($lockFile)) return '';
		$content = @file_get_contents($lockFile);
		if ($content === false) return ''; // Treat as corrupted; let validator handle it
		return trim($content);
	}

	private static function _getInstallFingerprint(): string
	{
		global $wpdb;

		$dbName  = $wpdb->dbname ?? '';
		$prefix  = $wpdb->prefix ?? '';

		$secret  = defined('AUTH_KEY')
			? AUTH_KEY
			: Factory::getConfig()->getEncryptionKey();

		return sha1($dbName . '|' . $prefix . '|' . $secret);
	}

	private static function _updateWorkingSpaceLockFile(): void
	{
		$lockFile     = self::_getWorkingSpaceLockFile();
		$fingerprint  = self::_getInstallFingerprint();

		if (file_put_contents($lockFile, $fingerprint, LOCK_EX) !== false) {
			@chmod($lockFile, 0400);
		}
	}

	private static function _validateWorkingSpace(): void
	{
		// Only lock the folder if we are using an alternate datadir
		// Regular setup is inside wp-content which is per-install
		if (empty(Factory::getConfig()->getAlternateDataFolder())) return;

		$lockFile = self::_getWorkingSpaceLockFile();

		// First run: create lock file and exit
		if (!file_exists($lockFile)) {
			self::_updateWorkingSpaceLockFile();
			return;
		}

		$storedFingerprint  = self::_getWorkingSpaceLockFileValue();
		$currentFingerprint = self::_getInstallFingerprint();

		if (!hash_equals($storedFingerprint, $currentFingerprint)) {
			error_log('JetBackup: Alternate data folder reset due to mismatched installation fingerprint.');
			Factory::getConfig()->setAlternateDataFolder('');
			Factory::getConfig()->save();
		}
	}


	private static function _createWorkingSpace() {

		$folders = [
			Factory::getLocations()->getDataDir(),
			Factory::getLocations()->getTempDir(),
			Factory::getLocations()->getDatabaseDir(),
			Factory::getLocations()->getDownloadsDir(),
			Factory::getLocations()->getBackupsDir(),
			Factory::getLocations()->getLogsDir(),
		];

		foreach ($folders as $folder) Util::secureFolder($folder);

	}

	public static function filterAdminBodyClass($classes) {
		$screen = Helper::getCurrentScreen();
		if ($screen && strpos($screen, 'jetbackup') !== false) $classes .= ' jetbackup';
		return $classes;
	}

	public static function actionCLI() {
		if (defined('WP_CLI') && WP_CLI) CLI::init();
	}

}
