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

class LockSnapshot extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }


	/**
	 * @return void
	 * @throws AjaxException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 */
	public function execute(): void {

		if(!$this->_getId()) throw new AjaxException("No backup id provided");

		$snap = new Snapshot($this->_getId());
		if(!$snap->getId()) throw new AjaxException("Invalid backup id provided");
		if($snap->getDeleted()) throw new AjaxException("Snapshot is marked for deletion");
		$snap->setLocked( ! $snap->isLocked() ); // Opposite status
		$snap->save();

		$this->setResponseMessage('Backup snapshot ' . ($snap->isLocked() ? 'Locked' : 'Unlocked'));
		$this->setResponseData($this->isCLI() ? $snap->getDisplayCLI() : $snap->getDisplay());
	}
}