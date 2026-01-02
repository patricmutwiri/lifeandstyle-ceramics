<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Config\Config;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\Queue\Queue;
use JetBackup\Settings\Security;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ManageSettingsSecurity extends aAjax {

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getMFAEnabled(): bool { return $this->getUserInput(Security::MFA_ENABLED, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getMFAAllowCLI(): bool { return $this->getUserInput(Security::MFA_ALLOW_CLI, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getValidateChecksums(): bool { return $this->getUserInput(Security::DAILY_CHECKSUM_CHECK, false, UserInput::BOOL); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getAlternateDataFolder(): string { return $this->getUserInput(Config::ALTERNATE_DATA_FOLDER, '', UserInput::STRING); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws FieldsValidationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function execute(): void {

		$settings = Factory::getSettingsSecurity();

		if($this->isset(Security::MFA_ENABLED)) $settings->setMFAEnabled($this->_getMFAEnabled());
		if($this->isset(Security::MFA_ALLOW_CLI)) $settings->setMFAAllowCLI($this->_getMFAAllowCLI());
		if($this->isset(Security::DAILY_CHECKSUM_CHECK)) $settings->setValidateChecksumsEnabled($this->_getValidateChecksums());

		$settings->validateFields();

		if($this->isset(Config::ALTERNATE_DATA_FOLDER)) {
			$config = Factory::getConfig();
			$config->setAlternateDataFolder($this->_getAlternateDataFolder());
			$config->validateAltDataDir();
			if (Queue::getTotalActiveItems() > 0) throw new fieldsValidationException("Cannot change data folder with active running queue items");
			$config->save();
			$settings->save();
			$config->moveDataDir();
		} else {
			$settings->save();
		}

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}