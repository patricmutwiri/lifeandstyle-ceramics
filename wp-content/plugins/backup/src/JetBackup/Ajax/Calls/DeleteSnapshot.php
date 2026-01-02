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

class DeleteSnapshot extends aAjax {

	/**
	 * @throws AjaxException
	 */
	private function _getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }


	/**
	 * @throws IOException
	 * @throws AjaxException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 */
	public function execute(): void {

		if(!$this->_getId()) throw new AjaxException("No backup id provided");

		$snap = new Snapshot($this->_getId());
		if(!$snap->getId()) throw new AjaxException("Invalid backup id provided");
		if($snap->isLocked()) throw new AjaxException("Snapshot is locked");

		$snap->setDeleted($snap->getDeleted() ? 0 : 1); // Opposite status
		$snap->save();

		$this->setResponseMessage($snap->getDeleted() ? 'Backup snapshot marked for deletion' : 'Backup snapshot deletion canceled');

	}
}