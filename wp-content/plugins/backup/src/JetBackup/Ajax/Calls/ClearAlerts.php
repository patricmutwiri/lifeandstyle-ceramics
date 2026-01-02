<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Alert\Alert;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ClearAlerts extends aAjax {

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {

		Alert::clearAlerts();
		$this->setResponseMessage("Alerts cleared successfully!");

	}
}