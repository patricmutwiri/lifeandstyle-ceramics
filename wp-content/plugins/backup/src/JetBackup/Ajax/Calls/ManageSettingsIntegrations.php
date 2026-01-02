<?php

namespace JetBackup\Ajax\Calls;

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Factory;
use JetBackup\Settings\Integrations;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ManageSettingsIntegrations extends aAjax {

	/**
	 * @throws AjaxException
	 */
	private function _getIntegrationValues(): array { return $this->getUserInput(Integrations::INTEGRATIONS, [], UserInput::ARRAY, UserInput::STRING); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {

		$settings = Factory::getSettingsIntegrations();
		if($this->isset(Integrations::INTEGRATIONS)) $settings->setIntegrations($this->_getIntegrationValues());
		$settings->validateFields();
		$settings->save();

		$this->setResponseMessage('Saved Successfully');
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}

