<?php

namespace JetBackup\Config;

use DateTime;
use DateTimeZone;
use JetBackup\Cron\Cron;
use JetBackup\Entities\Util;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\IO\Execute;
use JetBackup\JetBackup;
use JetBackup\JetBackupLinux\JetBackupLinux;
use JetBackup\Wordpress\Wordpress;
use JetBackup\Wordpress\Helper;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class System {

	const NO_VALUE_STRING = "Cannot get value";
	const PHP_MIN_POST_MAX_SIZE = (8 * 1024 * 1024);

	public static function isWindowsOS(): bool {
		// Check if PHP_OS_FAMILY is available (PHP 7.2+)
		if(defined('PHP_OS_FAMILY')) return PHP_OS_FAMILY === 'Windows';

		// Fallback to using PHP_OS for older versions
		return str_starts_with(strtoupper(PHP_OS), 'WIN');
	}

	public static function getLastCron (): int {
		$cron_last = Factory::getLocations()->getDataDir() . JetBackup::SEP . Cron::LAST_FILE;
		if(!file_exists($cron_last)) return 0;
		$_start_time = filemtime($cron_last);
		$timeDifference = microtime(true) - $_start_time;
		return (int) $timeDifference;
	}

	public static function getRecommendSecurePath(): ?string {
		// Check if the open_basedir directive is enabled
		if (ini_get('open_basedir')) {
			return '(Open basedir is enabled, cannot set secure path)';
		}

		// Attempt to fetch the user's home directory
		$userHomeDir = getenv('HOME') ?: getenv('HOMEDRIVE') . getenv('HOMEPATH');

		// Validate the user's home directory
		if ($userHomeDir && is_dir($userHomeDir)) {
			// Construct the secure path within the user's home directory
			return $userHomeDir . JetBackup::SEP . Factory::getConfig()->getDataDirectory();
		}
		
		// If home directory is not valid, fallback to the previous solution
		$_base_path = dirname(Factory::getWPHelper()->getWordPressHomedir());

		// Check if this is nested inside another WordPress installation
		if (file_exists($_base_path . JetBackup::SEP . 'wp-config.php')) {
			return null;
		}

		// Return the secure path based on the parent directory of the WordPress home directory
		return $_base_path . JetBackup::SEP . Factory::getConfig()->getDataDirectory();
	}
	
	private static function getFreeDiskSpace(): string {
		try {
			return function_exists('disk_free_space') ? Util::bytesToHumanReadable(disk_free_space( Factory::getWPHelper()->getWordPressHomedir())) : self::NO_VALUE_STRING;
		} catch (\Exception $e) {
			throw new IOException($e->getMessage());
		}
	}

	private static function getOpenFilesLimit (): ?string {
		return function_exists('posix_getrlimit') ? posix_getrlimit()['hard openfiles'] : self::NO_VALUE_STRING;
	}

	private static function getPHPCliVersion () {
		try {

			if(!Execute::run((Factory::getSettingsGeneral()->getPHPCLILocation() ?: 'php') . " -r 'print_r(phpversion());'", $output))
				return $output[0];

			return self::NO_VALUE_STRING;

		} catch (\Exception $e) {
			throw new IOException($e->getMessage());
		}
	}

	private static function getPHPVersion (): string {
		return defined('PHP_VERSION') ? PHP_VERSION : self::NO_VALUE_STRING;
	}

	/**
	 * @throws IOException
	 */
	public static function isPHPVersionCompatible() {

		if (($php_web_version = self::getPHPVersion()) && ($php_cli_version = self::getPHPCliVersion()) != self::NO_VALUE_STRING) {
			return version_compare($php_web_version, $php_cli_version, '=');
		}

		return true;
	}

	public static function isDataDirSecured(): bool {
		$homedir = Factory::getWPHelper()->getWordPressHomedir();
		$datadir = Factory::getLocations()->getDataDir();
		return !str_starts_with($datadir, $homedir);
	}

	public static function isAlternateFolderSecured(?string $datadir): bool {
		if (!$datadir) return false;
		$homedir = Helper::getUserHomedir();
		if(!$homedir) $homedir = dirname(ABSPATH);
		return str_starts_with($datadir, $homedir);
	}

	private static function getAvailableCli (): string {
		if (!($list = Execute::getAvailable())) return self::NO_VALUE_STRING;
		return implode(',', array_values($list));
	}

	public static function getSystemInfo(): array {

		$dateTime = new DateTime('now', new DateTimeZone(Factory::getSettingsGeneral()->getTimeZone()));

		return [
			'timezone'              => Factory::getSettingsGeneral()->getTimeZone(),
			'show_time'             => $dateTime->format('H:i:s'),
			'loaded_language'       => Wordpress::getLocale(),
			'open_files_limit'      => self::getOpenFilesLimit(),
			'memory_limit'          => ini_get('memory_limit'),
			'max_execution_time'    => ini_get('max_execution_time'),
			'post_max_size'         => ini_get('post_max_size'),
			'upload_max_filesize'   => ini_get('upload_max_filesize'),
			'wordpress_path'        => Factory::getWPHelper()->getWordPressHomedir(),
			'jetbackup_data_dir'    => Factory::getLocations()->getDataDir(),
			'free_disk_space'       => self::getFreeDiskSpace(),
			'php_version'           => self::getPHPVersion(),
			'php_cli'               => self::getPHPCliVersion(),
			'available_cli'         => self::getAvailableCli(),
		];
	}

	/**
	 * @throws IOException
	 */
	public static function getTotalAlerts(): int {
		$alerts = 0;
		if(!self::isDataDirSecured()) $alerts++;
		if(ini_get('post_max_size') !== false && Util::humanReadableToBytes(ini_get('post_max_size')) < self::PHP_MIN_POST_MAX_SIZE) $alerts++;
		if(!self::isPHPVersionCompatible()) $alerts++;
		if(self::getLastCron() > 600) $alerts++;
		if(!Factory::getSettingsAutomation()->isHeartbeatEnabled()) $alerts++;
		if(!Factory::getSettingsAutomation()->isCronsEnabled()) $alerts++;
		if(!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) $alerts++;
		if(JetBackupLinux::isInstalled() && !Factory::getSettingsGeneral()->isJBIntegrationEnabled()) $alerts++;
		return $alerts;
	}

	public static function getServerExecutionTime() {
		return ini_get('max_execution_time') ?: 60;
	}
}