<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Factory;
use JetBackup\Settings\Logging;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');


class ManageSettingsLogging extends aAjax {

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getDebug(): bool { return $this->getUserInput(Logging::DEBUG, false, UserInput::BOOL); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getLogRotate(): int { return $this->getUserInput(Logging::LOG_ROTATE, 0, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws FieldsValidationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {

		$settings = Factory::getSettingsLogging();

		if($this->isset(Logging::DEBUG)) $settings->setDebugEnabled($this->_getDebug());
		if($this->isset(Logging::LOG_ROTATE)) $settings->setLogRotate($this->_getLogRotate());

		$settings->validateFields();
		$settings->save();

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}

