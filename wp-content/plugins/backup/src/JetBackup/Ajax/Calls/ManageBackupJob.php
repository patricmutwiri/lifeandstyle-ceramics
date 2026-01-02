<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\ScheduleException;
use JetBackup\JetBackup;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ManageBackupJob extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getName():string { return ($this->getUserInput(BackupJob::NAME, '', UserInput::STRING)); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getType():int { return $this->getUserInput(BackupJob::TYPE, 0, UserInput::UINT); }

	/**
	 * @return array
	 * @throws AjaxException
	 */
	private function _getDestinations():array { return $this->getUserInput(BackupJob::DESTINATIONS, [], UserInput::ARRAY, UserInput::UINT); }

	/**
	 * @return array
	 * @throws AjaxException
	 */
	private function _getExcludes():array { return $this->getUserInput(BackupJob::EXCLUDES, [], UserInput::ARRAY, UserInput::STRING); }


	/**
	 * @return array
	 * @throws AjaxException
	 */
	private function _getExcludeDatabases():array { return $this->getUserInput(BackupJob::EXCLUDE_DATABASES, [], UserInput::ARRAY, UserInput::STRING); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getContains():int { return $this->getUserInput(BackupJob::CONTAINS, 0, UserInput::UINT); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getMonitor():int { return $this->getUserInput(BackupJob::JOB_MONITOR, 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getScheduleTime():string { return $this->getUserInput(BackupJob::SCHEDULE_TIME, '', UserInput::STRING); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getDefault():bool { return $this->getUserInput(BackupJob::DEFAULT, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _isFilesExcluded():bool { return $this->getUserInput(BackupJob::IS_FILES_EXCLUDED, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _isTablesExcluded():bool { return $this->getUserInput(BackupJob::IS_TABLES_EXCLUDED, false, UserInput::BOOL); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _isEnabled():bool { return $this->getUserInput(BackupJob::ENABLED, false, UserInput::BOOL); }

	/**
	 * @return array
	 * @throws AjaxException
	 */
	private function _getSchedules():array { return $this->getUserInput(BackupJob::SCHEDULES, [], UserInput::ARRAY, UserInput::MIXED); }

	/**
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws FieldsValidationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws ScheduleException
	 */
	public function execute(): void {

		if($this->_getId()) {
			$job = new BackupJob($this->_getId());
			if(!$job->getId() || $job->isHidden()) throw new AjaxException("Invalid job id \"%s\" provided", [$this->_getId()]);
		} else {
			$job = new BackupJob();
		}

		$schedulesUpdate = [];

		if($this->isset(BackupJob::NAME)) $job->setName($this->_getName());
		if($this->isset(BackupJob::TYPE)) $job->setType($this->_getType());
		if($this->isset(BackupJob::DESTINATIONS)) $job->setDestinations($this->_getDestinations());

		if($this->isset(BackupJob::EXCLUDES)) $job->setExcludes($this->_getExcludes());

		if($this->isset(BackupJob::EXCLUDE_DATABASES)) $job->setExcludeDatabases($this->_getExcludeDatabases());

		if($this->isset(BackupJob::JOB_MONITOR)) $job->setMonitor($this->_getMonitor());
		if($this->isset(BackupJob::ENABLED)) $job->setEnabled($this->_isEnabled());
		if($this->isset(BackupJob::CONTAINS)) $job->setContains($this->_getContains());
		// setScheduleTime Must be executed BEFORE updateMultiSchedules
		if($this->isset(BackupJob::SCHEDULE_TIME)) $job->setScheduleTime($this->_getScheduleTime());
		$job->setDefault($job->isDefault());
		$job->validateFields();
		try {
			if($this->isset(BackupJob::SCHEDULES)) $schedulesUpdate = $job->updateMultiSchedules($this->_getSchedules());
		} catch(\JetBackup\Exception\IOException $e) {
			throw new AjaxException($e->getMessage());
		}

		$job->setHidden(false);
		$job->save();

		// Update schedules only after we save the job
		if(sizeof($schedulesUpdate)) foreach($schedulesUpdate as $schedule) $schedule->save();

		$this->setResponseMessage('Backup job ' . ($this->_getId() ? 'modified' : 'created') . ' successfully');
		$this->setResponseData($this->isCLI() ? $job->getDisplayCLI() : $job->getDisplay());
	}
}