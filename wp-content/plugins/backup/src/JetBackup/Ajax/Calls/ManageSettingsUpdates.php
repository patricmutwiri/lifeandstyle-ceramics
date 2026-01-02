<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Factory;
use JetBackup\Settings\Updates;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ManageSettingsUpdates extends aAjax {

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getUpdateTier(): string { return $this->getUserInput(Updates::UPDATE_TIER, '', UserInput::STRING); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws FieldsValidationException
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public function execute(): void {

		$settings = Factory::getSettingsUpdates();

		if($this->isset(Updates::UPDATE_TIER)) $settings->setUpdateTier($this->_getUpdateTier());

		$settings->validateFields();
		$settings->save();

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}

