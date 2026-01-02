<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');


use JetBackup\Ajax\aAjax;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\JetBackup;
use JetBackup\Schedule\Schedule;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ManageSchedule extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getName():string { return ($this->getUserInput(Schedule::NAME, '', UserInput::STRING)); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getType():int { return($this->getUserInput(Schedule::TYPE, 0, UserInput::UINT)); }

	/**
	 * @return array|bool|float|int|mixed|object
	 * @throws AjaxException
	 */
	private function _getIntervals() { return($this->getUserInput(Schedule::INTERVALS, 0, UserInput::UINT|UserInput::ARRAY, UserInput::UINT)); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getBackupId():int { return($this->getUserInput(Schedule::BACKUP_ID, 0, UserInput::UINT)); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws FieldsValidationException
	 */
	public function execute(): void {

		if($this->_getId()) {
			$schedule = new Schedule($this->_getId());
			if(!$schedule->getId() || $schedule->isHidden()) throw new AjaxException("Invalid schedule id \"%s\" provided", [$this->_getId()]);
		} else {
			$schedule = new Schedule();
			$schedule->setHidden(false);
			$schedule->setDefault(false);
		}

		if($this->isset(Schedule::NAME)) $schedule->setName($this->_getName());
		if($this->isset(Schedule::TYPE)) $schedule->setType($this->_getType());
		if($this->isset(Schedule::INTERVALS)) $schedule->setIntervals($this->_getIntervals());
		if($this->isset(Schedule::BACKUP_ID)) $schedule->setBackupId($this->_getBackupId());

		$schedule->validateFields();
		$schedule->save();

		$this->setResponseMessage('Success');
		$this->setResponseData($this->isCLI() ? $schedule->getDisplayCLI() : $schedule->getDisplay());

	}
}