<?php

namespace JetBackup\Config;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Alert\Alert;
use JetBackup\Data\ReflectionObject;
use JetBackup\Encryption\Crypt;
use JetBackup\Entities\Util;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\LicenseException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\License\License;
use JetBackup\License\LicenseLocalKey;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;

class Config extends ReflectionObject {

	const CONFIG_FILE = JetBackup::CONFIG_PATH . JetBackup::SEP . 'config.php';
	const MAIN_DATA_FOLDER = 'MAIN_DATA_FOLDER';
	const DB_MAIN_FOLDER = 'DB_MAIN_FOLDER';
	const BACKUPS_TEMP_FOLDER = 'BACKUPS_TEMP_FOLDER';
	const CRON_TOKEN = 'CRON_TOKEN';
	const LOGS_FOLDER = 'LOGS_FOLDER';
	const BACKUPS_FOLDER = 'BACKUPS_FOLDER';
	const DOWNLOADS_FOLDER = 'DOWNLOADS_FOLDER';
	const SYSTEM_CRON_DAILY_LAST_RUN = 'SYSTEM_CRON_DAILY_LAST_RUN';
	const SYSTEM_CRON_HOURLY_LAST_RUN = 'SYSTEM_CRON_HOURLY_LAST_RUN';
	const ENCRYPTION_KEY = 'ENCRYPTION_KEY';
	const UNIQUE_ID = 'UNIQUE_ID';
	const SGB_LEGACY_CONVERTED = 'SGB_LEGACY_CONVERTED';
	const CURRENT_VERSION = 'CURRENT_VERSION';
	const ALTERNATE_DATA_FOLDER = 'ALTERNATE_DATA_FOLDER';
	const LICENSE_KEY                   = 'LICENSE_KEY';
	const LICENSE_LOCAL_KEY             = 'LICENSE_LOCAL_KEY';
	const LICENSE_NEXT_CHECK            = 'LICENSE_NEXT_CHECK';
	const LICENSE_LAST_CHECK            = 'LICENSE_LAST_CHECK';
	const LICENSE_NOTIFY_DATE           = 'LICENSE_NOTIFY_DATE';
	const SUPPORT_USERNAME = 'SUPPORT_USERNAME';
	const WP_DB_CONFIG_PREFIX = 'jetbackup_config';

	public function __construct() {
		parent::__construct(self::CONFIG_FILE, 'JetConfigDefines');

		if(!$this->getDataDirectory()) $this->setDataDirectory( 'jetbackup-' . Util::generateRandomString());
		if(!$this->getBackupsDirectory()) $this->setBackupsDirectory( 'backups-' . Util::generateRandomString());
		if(!$this->getTempDirectory()) $this->setTempDirectory( 'temp-' . Util::generateRandomString());
		if(!$this->getDatabaseDirectory()) $this->setDatabaseDirectory( 'db-' . Util::generateRandomString());
		if(!$this->getLogsDirectory()) $this->setLogsDirectory( 'logs-' . Util::generateRandomString());
		if(!$this->getDownloadsDirectory()) $this->setDownloadsDirectory( 'downloads-' . Util::generateRandomString());
		if(!$this->getEncryptionKey()) $this->setEncryptionKey(Util::generateRandomString());
		if(!$this->getUniqueID()) $this->setUniqueID(Util::generateRandomString());
		if(!$this->getCronToken()) $this->setCronToken(Util::generateRandomString());

		if($this->getDiff()) $this->save();
	}

	public function set($key, $value) {
		if(is_string($value)) $value = htmlspecialchars($value);
		parent::set($key, $value);
	}

	public function getDataDirectory():string { return $this->get(self::MAIN_DATA_FOLDER); }
	public function setDataDirectory($value):void { $this->set(self::MAIN_DATA_FOLDER, $value); }
	public function getDatabaseDirectory():string { return $this->get(self::DB_MAIN_FOLDER); }
	public function setDatabaseDirectory($value):void { $this->set(self::DB_MAIN_FOLDER, $value); }
	public function getLogsDirectory():string { return $this->get(self::LOGS_FOLDER); }
	public function setLogsDirectory($value):void { $this->set(self::LOGS_FOLDER, $value); }
	public function getBackupsDirectory():string { return $this->get(self::BACKUPS_FOLDER); }
	public function setBackupsDirectory($value):void { $this->set(self::BACKUPS_FOLDER, $value); }
	public function getDownloadsDirectory():string { return $this->get(self::DOWNLOADS_FOLDER); }
	public function setDownloadsDirectory($value):void { $this->set(self::DOWNLOADS_FOLDER, $value); }
	public function getTempDirectory():string { return $this->get(self::BACKUPS_TEMP_FOLDER); }
	public function setTempDirectory($value):void { $this->set(self::BACKUPS_TEMP_FOLDER, $value); }
	public function getSystemCronDailyLastRun():int { return (int) $this->get(self::SYSTEM_CRON_DAILY_LAST_RUN, 0); }
	public function setSystemCronDailyLastRun(?int $value=null):void { $this->set(self::SYSTEM_CRON_DAILY_LAST_RUN, $value ?? time()); }
	public function getSystemCronHourlyLastRun():int { return (int) $this->get(self::SYSTEM_CRON_HOURLY_LAST_RUN, 0); }
	public function setSystemCronHourlyLastRun(?int $value=null):void { $this->set(self::SYSTEM_CRON_HOURLY_LAST_RUN, $value ?? time()); }
	public function getEncryptionKey():string { return $this->get(self::ENCRYPTION_KEY); }
	public function setEncryptionKey($value):void { $this->set(self::ENCRYPTION_KEY, $value); }
	public function getUniqueID():string { return $this->get(self::UNIQUE_ID); }
	public function setUniqueID($value):void { $this->set(self::UNIQUE_ID, $value); }
	public function getCronToken():string { return $this->get(self::CRON_TOKEN); }
	public function setCronToken($value):void { $this->set(self::CRON_TOKEN, $value); }
	public function isSGBLegacyConverted():bool { return !!$this->get(self::SGB_LEGACY_CONVERTED, false); }
	public function setSGBLegacyConverted(bool $value):void { $this->set(self::SGB_LEGACY_CONVERTED, !!$value); }
	public function getCurrentVersion():string { return $this->get(self::CURRENT_VERSION); }
	public function setCurrentVersion($value):void { $this->set(self::CURRENT_VERSION, $value); }

	/**
	 * @return string
	 */
	public function getAlternateDataFolder():string { return $this->get(self::ALTERNATE_DATA_FOLDER); }

	/**
	 * @param $value
	 *
	 * @return void
	 */
	public function setAlternateDataFolder($value):void { $this->set(self::ALTERNATE_DATA_FOLDER, $value); }

	/**
	 * @return string
	 */
	public function getLicenseKey():string { return $this->get(self::LICENSE_KEY); }

	/**
	 * @param $value
	 *
	 * @return void
	 */
	public function setLicenseKey($value):void { $this->set(self::LICENSE_KEY, $value); }

	/**
	 * @return string
	 */
	public function getLicenseLocalKey():string { return $this->get(self::LICENSE_LOCAL_KEY); }

	/**
	 * @param $value
	 *
	 * @return void
	 */
	public function setLicenseLocalKey($value):void { $this->set(self::LICENSE_LOCAL_KEY, $value); }

	/**
	 * @param int $next_check
	 *
	 * @return void
	 */
	public function setLicenseNextCheck(int $next_check=0):void { $this->set(self::LICENSE_NEXT_CHECK, $next_check); }

	/**
	 * @return int
	 */
	public function getLicenseNextCheck():int { return (int) $this->get(self::LICENSE_NEXT_CHECK, 0); }

	/**
	 * @param int $last_check
	 *
	 * @return void
	 */
	public function setLicenseLastCheck(int $last_check=0):void { $this->set(self::LICENSE_LAST_CHECK, $last_check); }

	/**
	 * @return int
	 */
	public function getLicenseLastCheck():int { return (int) $this->get(self::LICENSE_LAST_CHECK, 0); }

	public function getSupportUsername():string { return  $this->get(self::SUPPORT_USERNAME, ''); }
	public function setSupportUsername($username):void { $this->set(self::SUPPORT_USERNAME, $username); }

	/**
	 * @param int $notify_date
	 *
	 * @return void
	 */
	public function setLicenseNotifyDate(int $notify_date=0):void { $this->set(self::LICENSE_NOTIFY_DATE, $notify_date); }

	/**
	 * @return int
	 */
	public function getLicenseNotifyDate():int { return (int) $this->get(self::LICENSE_NOTIFY_DATE, 0); }

	/**
	 * @param string $message
	 * @param LicenseLocalKey $localKey
	 *
	 * @return void
	 */
	public function setLocalKeyInvalid(string $message, LicenseLocalKey $localKey):void {
		$this->setLicenseLocalKey("{$localKey->getSigned()}|{$localKey->getSignedStatus()}|Invalid|$message");
	}

	/**
	 * @return void
	 */
	public function loadFromDatabase():void {
		parent::loadFromDatabase();
		$jetbackup_config = Wordpress::getOption(self::WP_DB_CONFIG_PREFIX);
		$data = $jetbackup_config ? unserialize(Crypt::decrypt($jetbackup_config, DB_PASSWORD)) : [];
		$new_data = [];
		
		foreach($data as $key => $value) {
			if(str_starts_with($key, 'JET_') && $key != 'JET_2FA_ENABLED') $key = substr($key, 4);
			$new_data[$key] = $value;
		}

		$this->setData($new_data);

		// in case we migrate wordpress and there are config left overs in the db, the alternate folder might
		// be in non-accessible location, we need to reset it
		if($this->getAlternateDataFolder() && !is_writable($this->getAlternateDataFolder())) {
			$this->setAlternateDataFolder('');
		}

	}

	/**
	 * @return void
	 */
	private function _saveToDatabase():void {
		Wordpress::setOption(self::WP_DB_CONFIG_PREFIX, Crypt::encrypt(serialize($this->getData()), DB_PASSWORD));
	}

	public function save():void {
		$this->_saveToDatabase();
		parent::save();
	}

	/**
	 * @return void
	 * @throws LicenseException
	 * @throws IOException
	 */
	public function validateLicense():void {
		License::retrieveLocalKey($this->getLicenseKey());
	}

	/**
	 * @return void
	 * @throws FieldsValidationException
	 */
	public function validateAltDataDir():void {
		$alternate_folder = trim($this->getAlternateDataFolder()) ?? null;
		if($alternate_folder) {
			$alternate_folder = rtrim(trim($alternate_folder), JetBackup::SEP);
			if (!System::isAlternateFolderSecured($alternate_folder)) {
				$homedir = Helper::getUserHomedir() ?? dirname(ABSPATH);
				throw new FieldsValidationException( "Data folder $alternate_folder cannot be outside of " . $homedir);
			}
			if(!@is_writable(dirname($alternate_folder))) throw new FieldsValidationException(sprintf("Data folder location '%s' is not writable.", dirname($alternate_folder)));
		}
	}

	/**
	 * @throws FieldsValidationException
	 */
	public function moveDataDir() : void {
		if (Factory::getLocations()->getDataDir() == self::getAlternateDataFolder()) return;
		if (file_exists(self::getAlternateDataFolder())) throw new FieldsValidationException("Target folder " . self::getAlternateDataFolder() . " already exists.");
		if (!@rename(Factory::getLocations()->getDataDir(), self::getAlternateDataFolder())) {
			throw new FieldsValidationException("Failed moving " . Factory::getLocations()->getDataDir() . " to " . self::getAlternateDataFolder());
		}

	}

}