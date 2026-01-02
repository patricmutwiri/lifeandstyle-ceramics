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

class EnableBackupJob extends aAjax {

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
		if($backup->isHidden()) throw new AjaxException("Cannot edit internal system job");
		$newStatus = !$backup->isEnabled();
		$backup->setEnabled($newStatus);
		$backup->save();

		$this->setResponseMessage("Job " . ($newStatus ? 'Enabled' : 'Disabled') . " successfully! Reloading...");
	}
}