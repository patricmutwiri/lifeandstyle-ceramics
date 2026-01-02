<?php

namespace JetBackup\Settings;

use Exception;
use JetBackup\Config\Config;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\JetBackupLinux\JetBackupLinux;
use JetBackup\License\License;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class General extends Settings {

	const SECTION = 'general';

	const TIMEZONE = 'TIMEZONE';
	const JETBACKUP_INTEGRATION = 'JETBACKUP_INTEGRATION';
	const JETBACKUP_SOCKET_API_STATUS = 'JETBACKUP_SOCKET_API_STATUS';
	const JETBACKUP_SOCKET_API_ERROR_MESSAGE = 'JETBACKUP_SOCKET_API_ERROR_MESSAGE';
	const ADMIN_TOP_MENU_INTEGRATION = 'ADMIN_TOP_MENU_INTEGRATION';
	const COMMUNITY_LANGUAGES = 'COMMUNITY_LANGUAGES';
	const MANUAL_BACKUPS_RETENTION = 'MANUAL_BACKUPS_RETENTION';
	const PHP_CLI_LOCATION = 'PHP_CLI_LOCATION';
	const ALTERNATE_WP_CONFIG_LOCATION = 'ALTERNATE_WP_CONFIG_LOCATION';
	const MYSQL_DEFAULT_PORT = 'MYSQL_DEFAULT_PORT';
	const PHP_DEFAULT_BINARY = 'php';
	const DEFAULT_TIMEZONE = 'UTC';
	const WORDPRESS_TIMEZONE = 'WORDPRESS_TIMEZONE';
	const LICENSE_STATUS = 'LICENSE_STATUS';

	private ?bool $socketApiStatus = null;
	private ?string $socketApiErrMsg = null;
	/**
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws IOException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
	}

	/**
	 * @return string
	 */
	public function getTimeZone():string {
		$timezone = $this->get(self::TIMEZONE, self::WORDPRESS_TIMEZONE);
		if ($timezone == self::WORDPRESS_TIMEZONE) return Wordpress::getOption('timezone_string') ?: self::DEFAULT_TIMEZONE;
		return $timezone;
	}

	private function getTimeZoneDisplay() {return $this->get(self::TIMEZONE, self::WORDPRESS_TIMEZONE);}
	/**
	 * @param $value
	 *
	 * @return void
	 */
	public function setTimeZone($value):void { $this->set(self::TIMEZONE, $value); }

	/**
	 * @return bool
	 */
	public function isJBIntegrationEnabled():bool { return (bool) $this->get(self::JETBACKUP_INTEGRATION, false); }
	private function _getSocketApiStatus():bool {

		if ($this->socketApiStatus === null) {
			try {
				JetBackupLinux::checkRequirements();
				JetBackupLinux::getAccountInfo();
				$this->socketApiStatus = true;
			} catch (\Exception $e) {
				$this->socketApiErrMsg = $e->getMessage();
				$this->socketApiStatus = false;
			}
		}
		return $this->socketApiStatus;

	}

	private function _getSocketApiErrorMessage():?string {
		return $this->socketApiErrMsg ?? null;
	}

	public function isAdminTopMenuIntegrationEnabled():bool { return (bool) $this->get(self::ADMIN_TOP_MENU_INTEGRATION, false); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setJBIntegrationEnabled(bool $value):void { $this->set(self::JETBACKUP_INTEGRATION, $value); }
	public function setAdminTopMenuIntegration(bool $value):void { $this->set(self::ADMIN_TOP_MENU_INTEGRATION, $value); }

	/**
	 * @return bool
	 */
	public function isCommunityLanguages():bool { return (bool) $this->get(self::COMMUNITY_LANGUAGES, true); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setCommunityLanguages(bool $value):void { $this->set(self::COMMUNITY_LANGUAGES, $value); }

	/**
	 * @return int
	 */
	public function getManualBackupsRetention():int { return (int) $this->get(self::MANUAL_BACKUPS_RETENTION, 0); }

	/**
	 * @param int $value
	 *
	 * @return void
	 */
	public function setManualBackupsRetention(int $value):void { $this->set(self::MANUAL_BACKUPS_RETENTION, $value); }

	/**
	 * @return string
	 */
	public function getPHPCLILocation():string { return $this->get(self::PHP_CLI_LOCATION, self::PHP_DEFAULT_BINARY); }
	public function getAlternateWpConfigLocation():string {
		$config = $this->get(trim(self::ALTERNATE_WP_CONFIG_LOCATION, ''));
		if($config == "" || !$config) $config = JetBackup::WP_ROOT_PATH . JetBackup::SEP . 'wp-config.php'; // we cannot put this is the default response if I add to db and then remove
		return $config;
	}

	public function getMysqlDefaultPort():int { return $this->get(self::MYSQL_DEFAULT_PORT, 3306); }

	/**
	 * @param $value
	 *
	 * @return void
	 */
	public function setPHPCLILocation($value):void { $this->set(self::PHP_CLI_LOCATION, $value); }
	public function setAlternateWpConfigLocation($value):void { $this->set(self::ALTERNATE_WP_CONFIG_LOCATION, $value); }
	public function setMysqlDefaultPort($value):void { $this->set(self::MYSQL_DEFAULT_PORT, $value); }

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getDisplay():array {

		return [
			Config::LICENSE_KEY                 => str_repeat('*', max(0, strlen(Factory::getConfig()->getLicenseKey()) - 6)) . substr(Factory::getConfig()->getLicenseKey(), -4),
			self::LICENSE_STATUS                => License::getLicenseStatus(),
			self::TIMEZONE                      => $this->getTimeZoneDisplay(),
			self::COMMUNITY_LANGUAGES           => $this->isCommunityLanguages() ? 1 : 0,
			self::JETBACKUP_INTEGRATION         => $this->_getSocketApiStatus() ? ($this->isJBIntegrationEnabled() ? 1 : 0) : 0,
			self::JETBACKUP_SOCKET_API_STATUS   => $this->_getSocketApiStatus() ? 1 : 0,
			self::JETBACKUP_SOCKET_API_ERROR_MESSAGE   => $this->_getSocketApiErrorMessage(),
			self::ADMIN_TOP_MENU_INTEGRATION    => $this->isAdminTopMenuIntegrationEnabled() ? 1 : 0,
			self::MANUAL_BACKUPS_RETENTION      => $this->getManualBackupsRetention(),
			self::PHP_CLI_LOCATION              => $this->getPHPCLILocation(),
			self::ALTERNATE_WP_CONFIG_LOCATION  => $this->getAlternateWpConfigLocation(),
			self::MYSQL_DEFAULT_PORT            => $this->getMySQLDefaultPort(),
		];
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getDisplayCLI():array {

		return [
			'License Key'                   => str_repeat('*', max(0, strlen(Factory::getConfig()->getLicenseKey()) - 6)) . substr(Factory::getConfig()->getLicenseKey(), -4),
			'License Status'                => License::getLicenseStatus(),
			'Timezone'                      => $this->getTimeZoneDisplay(),
			'Community Languages'           => $this->isCommunityLanguages() ? "Yes" : "No",
			'JetBackup Integration'         => $this->_getSocketApiStatus() ? ($this->isJBIntegrationEnabled() ? "Yes" : "No") : "No",
			'JetBackup Socket API Enabled'  => $this->_getSocketApiStatus() ? "Yes" : "No",
			'JetBackup Socket API Error'    => $this->_getSocketApiErrorMessage(),
			'Admin bar top menu integration' => $this->isAdminTopMenuIntegrationEnabled() ? "Yes" : "No",
			'Manual Backup Retain'          => $this->getManualBackupsRetention(),
			'PHP CLI Location'              => $this->getPHPCLILocation(),
			'Alternate wp-config.php Location'  => $this->getAlternateWpConfigLocation(),
			'MySQL Default Port'              => $this->getMysqlDefaultPort(),
		];
	}

	/**
	 * @return void
	 * @throws FieldsValidationException
	 * @throws Exception
	 */
	public function validateFields():void {

		if($this->getAlternateWpConfigLocation()) {

			$alternateLocation = JetBackup::SEP. trim($this->getAlternateWpConfigLocation(), JetBackup::SEP);
			$homedir = JetBackup::SEP . trim(Helper::getUserHomedir() ?? dirname(ABSPATH), JetBackup::SEP) . JetBackup::SEP;

			if(!str_starts_with($alternateLocation, $homedir)) throw new FieldsValidationException("Alternate location '$alternateLocation' cannot be outside of " . $homedir);
			if(!is_file($alternateLocation)) throw new FieldsValidationException("Alternate location '$alternateLocation' is not a file or does not exist");
			if(!is_readable($alternateLocation)) throw new FieldsValidationException("Alternate location '$alternateLocation' is not readable or does not exist");

			try {

				$config = Factory::getWPHelper()::parseWpConfig();

				if(!isset($config->db_name)) throw new FieldsValidationException("Couldn't find database name in configuration");
				if(!isset($config->db_user)) throw new FieldsValidationException("Couldn't find database user in configuration");
				if(!isset($config->db_password)) throw new FieldsValidationException("Couldn't find database password in configuration");
				if(!isset($config->db_host)) throw new FieldsValidationException("Couldn't find database host in configuration");
				if(!isset($config->db_port)) throw new FieldsValidationException("Couldn't find database port in configuration");
				if(!isset($config->table_prefix)) throw new FieldsValidationException("Couldn't find database table prefix in configuration");


				if($config->db_name != DB_NAME) throw new FieldsValidationException("Database name from alternate config doesn't match runtime database name");
				if($config->db_user != DB_USER) throw new FieldsValidationException("Database user from alternate config doesn't match runtime database user");
				if($config->db_password != DB_PASSWORD) throw new FieldsValidationException("Database password from alternate config doesn't match runtime database password");

			} catch (Exception $e) {
				throw new FieldsValidationException($e->getMessage());
			}


		}

		if((!$this->getTimeZone() || $this->getTimeZone() != (self::DEFAULT_TIMEZONE || self::WORDPRESS_TIMEZONE)) && !isset(Util::generateTimeZoneList()[$this->getTimeZone()]))
			throw new FieldsValidationException("Timezone " . $this->getTimeZone(). " is not valid");

		if((!$this->getPHPCLILocation() || strtolower($this->getPHPCLILocation()) != self::PHP_DEFAULT_BINARY) && !is_executable(trim($this->getPHPCLILocation()))) 
			throw new FieldsValidationException("PHP CLI location ".$this->getPHPCLILocation()." is not executable");
		
		if($this->isJBIntegrationEnabled()) {
			try {
				JetBackupLinux::checkRequirements();
			} catch(JetBackupLinuxException $e) {
				throw new FieldsValidationException($e->getMessage());
			}
		}
	}
}