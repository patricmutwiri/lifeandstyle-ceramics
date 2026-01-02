<?php

namespace JetBackup\Ajax\Calls;

if ( ! defined( '__JETBACKUP__' ) ) {
	die( 'Direct access is not allowed' );
}

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Schedule\Schedule;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;


class GetSchedule extends aAjax {

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

		if(!$this->getId()) throw new AjaxException("No schedule id provided");

		$schedule = new Schedule($this->getId());

		if(!$schedule->getId() || $schedule->isHidden()) throw new AjaxException("Invalid schedule id provided");

		$this->setResponseData($this->isCLI() ? $schedule->getDisplayCLI() : $schedule->getDisplay());
	}
}