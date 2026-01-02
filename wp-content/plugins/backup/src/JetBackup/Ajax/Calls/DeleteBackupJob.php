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

class DeleteBackupJob extends aAjax {

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
		if(!$backup->getId() || $backup->isHidden()) throw new AjaxException("Invalid job id provided");
		if($backup->isDefault()) throw new AjaxException("Cannot delete default job");
		$backup->delete();
		$this->setResponseMessage("Job Deleted successfully!");

	}
}