<?php

namespace JetBackup\Ajax\Calls;

use Exception;
use JetBackup\Ajax\aAjax;
use JetBackup\Config\Config;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\Exception\LicenseException;
use JetBackup\Exception\QueueException;
use JetBackup\Factory;
use JetBackup\JetBackupLinux\JetBackupLinux;
use JetBackup\License\License;
use JetBackup\License\LicenseLocalKey;
use JetBackup\Settings\General;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;

class ManageSettingsGeneral extends aAjax {

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getLicenseKey(): string { return $this->getUserInput(Config::LICENSE_KEY, '', UserInput::STRING); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getTimeZone(): string { return $this->getUserInput(General::TIMEZONE, 'UTC', UserInput::STRING); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getJetBackupIntegration():bool { return $this->getUserInput(General::JETBACKUP_INTEGRATION, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getAdminTopMenuIntegration():bool { return $this->getUserInput(General::ADMIN_TOP_MENU_INTEGRATION, true, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getCommunityLanguages():bool { return $this->getUserInput(General::COMMUNITY_LANGUAGES, false, UserInput::BOOL); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getManualBackupsRetention(): int { return $this->getUserInput(General::MANUAL_BACKUPS_RETENTION, 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getPHPCLILocation(): string { return $this->getUserInput(General::PHP_CLI_LOCATION, 'php', UserInput::STRING); }
	private function _getAlternateWpConfigLocation(): string { return $this->getUserInput(General::ALTERNATE_WP_CONFIG_LOCATION, '', UserInput::STRING); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getMysqlDefaultPort(): int { return $this->getUserInput(General::MYSQL_DEFAULT_PORT, '3306', UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws FieldsValidationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws JetBackupLinuxException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws Exception
	 */
	public function execute(): void {

		$settings = Factory::getSettingsGeneral();
		$jb_integration_set = $this->isset(General::JETBACKUP_INTEGRATION);
		$jb_integration_old_value = $jb_integration_set ? $settings->isJBIntegrationEnabled() : null;

		if($this->isset(General::TIMEZONE)) $settings->setTimeZone($this->_getTimeZone());
		if($jb_integration_set) $settings->setJBIntegrationEnabled($this->_getJetBackupIntegration());
		if($this->isset(General::COMMUNITY_LANGUAGES)) $settings->setCommunityLanguages($this->_getCommunityLanguages());
		if($this->isset(General::ADMIN_TOP_MENU_INTEGRATION)) $settings->setAdminTopMenuIntegration($this->_getAdminTopMenuIntegration());
		if($this->isset(General::MANUAL_BACKUPS_RETENTION)) $settings->setManualBackupsRetention($this->_getManualBackupsRetention());
		if($this->isset(General::PHP_CLI_LOCATION)) $settings->setPHPCLILocation($this->_getPHPCLILocation());
		if($this->isset(General::ALTERNATE_WP_CONFIG_LOCATION)) $settings->setAlternateWpConfigLocation($this->_getAlternateWpConfigLocation());
		if($this->isset(General::MYSQL_DEFAULT_PORT)) $settings->setMysqlDefaultPort($this->_getMysqlDefaultPort());

		$settings->validateFields();

		if($this->isset(Config::LICENSE_KEY) && !str_starts_with($this->_getLicenseKey(), '*')) {
			$config = Factory::getConfig();
			
			$new_license = $this->_getLicenseKey();
			$old_license = $config->getLicenseKey();
			
			if($old_license != $new_license) {
				
				$config->setLicenseKey($new_license);
				
				if($new_license) {
					
					try {
						$config->validateLicense();
						$localKey = new LicenseLocalKey();
						if($localKey->getStatus() != License::STATUS_ACTIVE) throw new LicenseException($localKey->getDescription());
					} catch (LicenseException $e) {

						$config->setLicenseKey($old_license);
						$config->save();
						
						throw new FieldsValidationException($e->getMessage());
					}

				} else {
					$config->setLicenseLocalKey('');
				}

				$config->save();
			}
		}

		$settings->save();

		if($jb_integration_set) {
			if ($jb_integration_old_value != $settings->isJBIntegrationEnabled()) {
				if($settings->isJBIntegrationEnabled() == 0) JetBackupLinux::deleteSnapshots();
				if($settings->isJBIntegrationEnabled() == 1) JetBackupLinux::addToQueue();
			}
		}

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}