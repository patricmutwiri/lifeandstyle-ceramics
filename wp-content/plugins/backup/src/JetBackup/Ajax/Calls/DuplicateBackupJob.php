<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class DuplicateBackupJob extends aAjax {

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
	
		if(!$this->getId()) throw new AjaxException("No job id provided");
		
		$backup = new BackupJob($this->getId());
		
		if(!$backup->getId()) throw new AjaxException("Invalid job id provided");

		$new_backup = $backup->duplicate();

		$this->setResponseMessage("Job Duplicated successfully! Reloading...");
		$this->setResponseData($this->isCLI() ? $new_backup->getDisplayCLI() : $new_backup->getDisplay());
	}
}