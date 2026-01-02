<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Factory;
use JetBackup\Settings\Notifications;
use JetBackup\UserInput\UserInput;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ManageSettingsNotifications extends aAjax {

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getEmails(): bool { return $this->getUserInput(Notifications::EMAILS, false, UserInput::BOOL); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getAlternateEmail(): string { return Wordpress::sanitizeEmail($this->getUserInput(Notifications::ALTERNATE_EMAIL, '', UserInput::STRING)); }

	/**
	 * @return array
	 * @throws AjaxException
	 */
	private function _getAlertLevelsFrequency(): array { return $this->getUserInput(Notifications::NOTIFICATION_LEVELS_FREQUENCY, Notifications::NOTIFICATION_FREQUENCY_DEFAULTS, UserInput::ARRAY, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws FieldsValidationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {

		$settings = Factory::getSettingsNotifications();

		if($this->isset(Notifications::EMAILS)) $settings->setEmailsEnabled($this->_getEmails());
		if($this->isset(Notifications::ALTERNATE_EMAIL)) $settings->setAlternateEmail($this->_getAlternateEmail());
		if($this->isset(Notifications::NOTIFICATION_LEVELS_FREQUENCY)) $settings->setAlertLevelFrequency($this->_getAlertLevelsFrequency());

		$settings->validateFields();
		$settings->save();

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}