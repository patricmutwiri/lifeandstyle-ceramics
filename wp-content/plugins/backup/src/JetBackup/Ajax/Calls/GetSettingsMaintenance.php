<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Factory;

class GetSettingsMaintenance extends aAjax {

	/**
	 * @return void
	 */
	public function execute(): void {
		$settings = Factory::getSettingsMaintenance();
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}