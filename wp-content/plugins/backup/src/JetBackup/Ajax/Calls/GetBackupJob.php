<?php

namespace JetBackup\Ajax\Calls;

defined('__JETBACKUP__') or die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class GetBackupJob extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	public function getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {
		if(!$this->getId()) throw new AjaxException("No backup job id provided");
		$job = new BackupJob($this->getId());
		if(!$job->getId() || $job->isHidden()) throw new AjaxException("Invalid backup job id provided");
		$this->setResponseData($this->isCLI() ? $job->getDisplayCLI() : $job->getDisplay());
	}
}