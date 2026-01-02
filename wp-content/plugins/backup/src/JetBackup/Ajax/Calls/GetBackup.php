<?php

namespace JetBackup\Ajax\Calls;

if ( ! defined( '__JETBACKUP__' ) ) {
	die( 'Direct access is not allowed' );
}

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Snapshot\Snapshot;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class GetBackup extends aAjax {

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

		if(!$this->getId()) throw new AjaxException("No backup id provided");
		$snapshot = new Snapshot($this->getId());
		if(!$snapshot->getId() || $snapshot->isDeleted()) throw new AjaxException("Invalid backup id provided");

		$this->setResponseData($this->isCLI() ? $snapshot->getDisplayCLI() : $snapshot->getDisplay());
	}
}