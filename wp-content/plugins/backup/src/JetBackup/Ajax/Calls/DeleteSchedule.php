<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Schedule\Schedule;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class DeleteSchedule extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	public function getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @param $schedule
	 *
	 * @return int
	 */
	private function _getScheduleCount($schedule):int {

		return sizeof($schedule::query()
		                  ->select([JetBackup::ID_FIELD])
		                  ->where([ Schedule::TYPE, "=", $schedule->getType() ])
		                  ->where([ Schedule::HIDDEN, "=", false ])
		                  ->getQuery()
		                  ->fetch());
	}

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
		if($schedule->getJobsCount() > 0) throw new AjaxException("Cannot delete a schedule with jobs assigned");
		if($schedule->isDefault()) throw new AjaxException("Cannot delete a system default schedule");

		$schedule->delete();
		$this->setResponseMessage("Schedule Deleted successfully! Reloading...");
	}
}