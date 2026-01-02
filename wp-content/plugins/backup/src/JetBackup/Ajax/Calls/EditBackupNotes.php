<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Snapshot\Snapshot;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class EditBackupNotes extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getNotes():string { return $this->getUserInput(Snapshot::NOTES, '', UserInput::STRING); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {

		if(!$this->_getId()) throw new AjaxException("No backup id provided");

		$snap = new Snapshot($this->_getId());
		if(!$snap->getId()) throw new AjaxException("Invalid backup id provided");

		if($this->isset(Snapshot::NOTES)) $snap->setNotes($this->_getNotes());
		$snap->save();
		
		$this->setResponseMessage("Backup notes modified successfully! Reloading...");
		$this->setResponseData($this->isCLI() ? $snap->getDisplayCLI() : $snap->getDisplay());
	}
}