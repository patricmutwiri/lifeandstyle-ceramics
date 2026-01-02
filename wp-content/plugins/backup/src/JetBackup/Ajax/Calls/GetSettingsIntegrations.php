<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Factory;

class GetSettingsIntegrations extends aAjax {

	/**
	 * @return void
	 */
	public function execute(): void {
		$settings = Factory::getSettingsIntegrations();
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}