<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use Exception;
use JetBackup\Ajax\aAjax;
use JetBackup\Factory;

class GetSettingsGeneral extends aAjax {

	/**
	 * @return void
	 * @throws Exception
	 */
	public function execute(): void {
		$settings = Factory::getSettingsGeneral();
		$this->setResponseData($this->isCLI() ? $settings->getDisplayCLI() : $settings->getDisplay());
	}
}