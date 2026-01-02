<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Factory;
use JetBackup\Settings\Restore;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ManageSettingsRestore extends aAjax {

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getRestoreCompatibilityCheck(): bool { return $this->getUserInput(Restore::RESTORE_COMPATIBILITY_CHECK, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getRestoreAllowCrossDomain(): bool { return $this->getUserInput(Restore::RESTORE_ALLOW_CROSS_DOMAIN, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getRestoreAlternatePath(): bool { return $this->getUserInput(Restore::RESTORE_ALTERNATE_PATH, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getRestoreWpContentOnly(): bool { return $this->getUserInput(Restore::RESTORE_WP_CONTENT_ONLY, false, UserInput::BOOL); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public function execute(): void {

		$settings = Factory::getSettingsRestore();
		if($this->isset(Restore::RESTORE_COMPATIBILITY_CHECK)) $settings->setRestoreCompatibilityCheck($this->_getRestoreCompatibilityCheck());
		if($this->isset(Restore::RESTORE_ALLOW_CROSS_DOMAIN)) $settings->setRestoreAllowCrossDomain($this->_getRestoreAllowCrossDomain());
		if($this->isset(Restore::RESTORE_ALTERNATE_PATH)) $settings->setRestoreAlternatePath($this->_getRestoreAlternatePath());
		if($this->isset(Restore::RESTORE_WP_CONTENT_ONLY)) $settings->setRestoreWpContentOnly($this->_getRestoreWpContentOnly());

		$settings->validateFields();
		$settings->save();

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}

