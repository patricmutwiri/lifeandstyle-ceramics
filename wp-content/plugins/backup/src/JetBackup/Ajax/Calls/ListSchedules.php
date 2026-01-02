<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\ListRecord;
use JetBackup\Exception\DBException;
use JetBackup\JetBackup;
use JetBackup\Schedule\Schedule;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

class ListSchedules extends ListRecord {

	/**
	 * @return void
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute(): void {
		
		$query = Schedule::query()->where([Schedule::HIDDEN, '=', false]);

		if($filter = $this->getFilter()) {
			$fields = [Schedule::NAME];
			$query->search($fields, $filter);
		}

		$output = $this->isCLI() ? [] : [ 'schedules' => [], 'total' => count($query->getQuery()->fetch()) ];

		if($this->getLimit()) $query->limit($this->getLimit());
		if($this->getSkip()) $query->skip($this->getSkip());
		if($this->getSort()) $query->orderBy($this->getSort());

		$list = $query->getQuery()->fetch();
		
		foreach($list as $schedule_details) {
			$schedule = new Schedule($schedule_details[JetBackup::ID_FIELD]);
			if($this->isCLI()) $output[] = $schedule->getDisplayCLI();
			else $output['schedules'][] = $schedule->getDisplay();
		}
		
		$this->setResponseData($output);
	}
}