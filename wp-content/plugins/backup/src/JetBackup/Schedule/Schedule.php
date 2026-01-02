<?php

namespace JetBackup\Schedule;

use DateTime;
use DateTimeZone;
use Exception;
use JetBackup\Alert\Alert;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Data\DBObject;
use JetBackup\Data\SleekStore;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\ScheduleException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;
use SleekDB\QueryBuilder;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

/**
 * The Schedules class is responsible for handling scheduling operations.
 * It provides functionality to add new schedules and retrieve all schedules.
 */
class Schedule extends DBObject {

	const COLLECTION = 'schedules';
	
	const UNIQUE_ID = 'unique_id';
	const NAME = 'name';
	const TYPE = 'type';
	const TYPE_NAME = 'type_name';
	const INTERVALS = 'intervals';
	const BACKUP_ID = 'backup_id';
	const JOB_COUNT = 'job_count';
	const JOB_ASSIGNED = 'jobs_assigned';
	const JOB_NAMES = 'job_names';
	const HIDDEN = 'hidden';
	const DEFAULT = 'default';

	const TYPE_HOURLY           = 1;
	const TYPE_DAILY            = 2;
	const TYPE_WEEKLY           = 3;
	const TYPE_MONTHLY          = 4;
	const TYPE_MANUALLY         = 5;
	const TYPE_AFTER_JOB_DONE   = 6;

	const DEFAULT_SCHEDULE_TYPES = [self::TYPE_HOURLY,self::TYPE_DAILY,self::TYPE_WEEKLY,self::TYPE_MONTHLY];
	const DEFAULT_SCHEDULE_INTERVALS = [
		self::TYPE_HOURLY        => 1,
		self::TYPE_DAILY        => [1,2,3,4,5,6,7],
		self::TYPE_WEEKLY       => 1,
		self::TYPE_MONTHLY      => [1]
	];

	const ALLOWED_INTERVALS = [
		self::TYPE_DAILY        => [1,2,3,4,5,6,7],
		self::TYPE_MONTHLY      => [1,7,14,21,28]
	];

	const TYPE_NAMES = [
		self::TYPE_HOURLY           => 'Hourly',
		self::TYPE_DAILY            => 'Daily',
		self::TYPE_WEEKLY           => 'Weekly',
		self::TYPE_MONTHLY          => 'Monthly',
		self::TYPE_MANUALLY         => 'Manually',
		self::TYPE_AFTER_JOB_DONE   => 'After Job Done'
	];

	const TYPE_ALLOWED = [
		self::TYPE_HOURLY,
		self::TYPE_DAILY,
		self::TYPE_WEEKLY,
		self::TYPE_MONTHLY,
		self::TYPE_AFTER_JOB_DONE
	];

	const DEFAULT_CONFIG_SCHEDULE_NAME = 'Default Daily Config';

	public function __construct($_id=null) {
		parent::__construct(self::COLLECTION);
		if($_id) $this->_loadById((int) $_id);
    }
	public function setDefault(bool $default) { $this->set(self::DEFAULT, $default); }
	public function isDefault():bool { return $this->get(self::DEFAULT, false); }
	public function setUniqueId($id) { $this->set(self::UNIQUE_ID, $id); }
	public function getUniqueId():string { return $this->get(self::UNIQUE_ID); }

	public function setName(string $name) { $this->set(self::NAME, $name); }
	public function getName():string { return $this->get(self::NAME); }

	public function setHidden(bool $hidden) { $this->set(self::HIDDEN, $hidden); }
	public function isHidden():bool { return !!$this->get(self::HIDDEN, false); }

	public function setType(int $type) { $this->set(self::TYPE, $type); }
	public function getType():int {return (int) $this->get(self::TYPE, 0);}
	public function setIntervals($intervals) { $this->set(self::INTERVALS, $intervals); }
	public function getIntervals() {
		return $this->get(self::INTERVALS, 0);
	}

	public function setBackupId(int $id) { $this->set(self::BACKUP_ID, $id); }
	public function getBackupId():int { return (int) $this->get(self::BACKUP_ID, 0); }

	public function setJobsCount(int $count):void { $this->set(self::JOB_COUNT, $count); }

	public function addJobsCount():void { $this->setJobsCount($this->getJobsCount() + 1); }

	public function reduceJobsCount():void {
		$count = $this->getJobsCount();
		$this->setJobsCount($count > 0 ? $count - 1 : 0);
	}

	public function getJobsCount():int { return $this->get(self::JOB_COUNT, 0); }

	public static function db():SleekStore {
		return new SleekStore(self::COLLECTION);
	}

	public static function query():QueryBuilder {
		return self::db()->createQueryBuilder();
	}

	public function save():void {
		if(!$this->getUniqueId()) $this->setUniqueId(Util::generateUniqueId());
		if ($this->getType() == Schedule::TYPE_AFTER_JOB_DONE) $this->setIntervals(null);

		parent::save();

		// Update backup next run only after updating the schedule itself
		$list = BackupJob::query()
		                 ->select([JetBackup::ID_FIELD])
		                 ->where([ BackupJob::SCHEDULES, 'contains', $this->getId()])
		                 ->getQuery()
		                 ->fetch();

		foreach($list as $details) {
			$config = new BackupJob( $details[ JetBackup::ID_FIELD]);
			$config->calculateNextRun();
			$config->save();
		}
	}

	public function delete():void {

		if($details = BackupJob::query()
		                       ->select([ BackupJob::NAME])
		                       ->where([ BackupJob::SCHEDULES, 'contains', $this->getId()])
		                       ->getQuery()
		                       ->first()) throw new ScheduleException('Schedule is assigned to a job: ' . $details[ BackupJob::NAME]);

		// if we want to remove the schedule from jobs on delete
		/*
		$list = BackupConfig::query()
	        ->createQueryBuilder()
			->select([JetBackup::ID_FIELD])
			->where([BackupConfig::SCHEDULES, 'contains', $this->getId()])
	        ->getQuery()
	        ->fetch();
		
		foreach($list as $details) {
			$config = new BackupConfig($details[JetBackup::ID_FIELD]);
			$config->removeSchedule($this->getId());
			$config->calculateNextRun();
			$config->save();
		}
		*/

		parent::delete();
	}

	public function calculateNextRun(string $time):int {

		try {

			list($hour, $minute) = explode(':', $time);

			switch ($this->getType()) {
				default: return 0;

				case self::TYPE_HOURLY:

					/*
					 *
					 * Moves the $Target time to the next interval hour while keeping the minutes (and seconds) consistent with your specified $time
					 *
					 * Splits the provided $time into hours and minutes.
					 * Sets $Target as a clone of the current time $Now.
					 * Modifies $Target by adding the interval hours to it.
					 * Sets the minutes (and resets seconds to 00) based on the provided $time,
					 * ensuring that $Target is aligned with the specific minute mark you want.
					 *
					 */

					$target = Util::getDateTime();
					$target->modify("+" . ((int) $this->getIntervals()) . " hours");
					$target->setTime($target->format('H'), $minute, 00);
					return $target->getTimestamp();


				case self::TYPE_DAILY:
					$now = Util::getDateTime();

					// Get the days when the schedule should run
					$runDays = array_values($this->getIntervals()); // Ensure it's an indexed array
					$target = null;
					$maxIterations = 14; // Max iterations for two weeks
					$dayIndex = 0;

					// Iterate through the days until the next run day is found
					while ($target === null && $maxIterations > 0) {

						$testDate = Util::getDateTime()->modify("+$dayIndex day");
						$testDate->setTime($hour, $minute); // Set to the specific time of day

						// Correct the target time for time zone offset if necessary
						$timezoneOffset = $testDate->getOffset() - $now->getOffset();
						if ($timezoneOffset != 0) $testDate->modify($timezoneOffset . ' seconds');

						$dayName = $testDate->format('w'); // 'l' returns the full textual representation of the day of the week

						if (in_array($dayName, $runDays) && $testDate > $now) $target = $testDate;

						$dayIndex++;
						$maxIterations--;
					}

					if ($maxIterations === 0) Alert::add('calcNextRun Error', 'Daily loop did not find a valid date', Alert::LEVEL_WARNING);


					return $target->getTimestamp();

				case self::TYPE_WEEKLY:

					$runDay = $this->getIntervals() ?? 0; // Expected weekday number (0-6 if using `w`, 1-7 if using `N`)
					$target = null;
					$maxIterations = 8; // Max iterations for 8 days
					$dayIndex = 0;

					$now = Util::getDateTime();
					$todayDayName = (int) $now->format('w'); // 0 (Sunday) to 6 (Saturday)


                    $scheduledTimeToday = (clone $now)->setTime($hour, $minute);


					// Check if today is the run day
					if ($todayDayName === (int) $runDay) {
						if ($scheduledTimeToday > $now) {
							// If the scheduled time today is still in the future
							return $scheduledTimeToday->getTimestamp();
						} else {
							// If the scheduled time today has already passed, start checking from the next day
							$dayIndex = 1;
						}
					}

					// Iterate through the next days to find the correct scheduled weekday
					while ($target === null && $maxIterations > 0) {
						$testDate = (clone $now)->modify("+$dayIndex day")->setTime($hour, $minute);

						$testDay = (int) $testDate->format('w'); // 0 (Sunday) to 6 (Saturday)

						if ($testDay === (int) $runDay) {
							$target = $testDate;
							break;
						}

						$dayIndex++;
						$maxIterations--;
					}

					if ($target === null) {
						Alert::add('calcNextRun Error', 'Weekly loop did not find a valid date', Alert::LEVEL_WARNING);
					}

					return $target ? $target->getTimestamp() : 0;


				case self::TYPE_MONTHLY:

					$now = Util::getDateTime();
					$runDays = $this->getIntervals(); // Days of the month to run
					if (!is_array($runDays) || empty($runDays)) {
						Alert::add('calcNextRun Error', 'No valid run days provided for monthly schedule', Alert::LEVEL_WARNING);
						return 0;
					}

					$target = null;
					$currentYear = (int)$now->format('Y');
					$currentMonth = (int)$now->format('m');
					$currentDay = (int)$now->format('d');
					$maxIterations = 12; // Max iterations to prevent endless loop

					// Check if today is a run day and the time has not yet passed
					$now = Util::getDateTime();
					if (in_array($currentDay, $runDays)) {
						$scheduledTimeToday = (clone $now)->setTime($hour, $minute);
						if ($scheduledTimeToday > $now) return $scheduledTimeToday->getTimestamp();
					}

					// Iterate through the months to find the next occurrence of the specified day
					while ($target === null && $maxIterations > 0) {
						foreach ($runDays as $day) {
							try {

								$testDate = (clone $now)->setDate($currentYear, $currentMonth, $day);
								$testDate->setTime($hour, $minute);

								// Correct the target time for time zone offset if necessary
								$timezoneOffset = $testDate->getOffset() - $now->getOffset();
								if ($timezoneOffset != 0) $testDate->modify($timezoneOffset . ' seconds');

								if ($testDate > $now) {
									$target = $testDate;
									break 2;
								}
							} catch (Exception $e) {
								continue; // Skip to the next day in $runDays
							}
						}

						// Increment the month and adjust the year if needed
						$currentMonth++;
						if ($currentMonth > 12) {
							$currentYear++;
							$currentMonth = 1;
						}

						$maxIterations--;
					}

					if ($maxIterations === 0) {
						Alert::add('calcNextRun Error', 'Monthly loop did not find a valid date', Alert::LEVEL_WARNING);
						return 0;
					}

					return $target->getTimestamp() ?? 0;
			}

		} catch (Exception $e) {
			Throw new ScheduleException($e->getMessage());
		}
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public static function createDefaultSchedule():void {

		foreach(self::DEFAULT_SCHEDULE_TYPES as $type) {

			$res = self::query()
			           ->select([JetBackup::ID_FIELD])
			           ->where([ self::TYPE, "=", $type ])
			           ->where([ self::HIDDEN, "=", false ])
			           ->where([ self::DEFAULT, "=", true ])
			           ->getQuery()
			           ->first();

			if (empty($res)) {
				$schedule = new Schedule();
				$schedule->setName(self::TYPE_NAMES[$type]);
				$schedule->setType($type);
				$schedule->setIntervals(self::DEFAULT_SCHEDULE_INTERVALS[$type]);
				$schedule->setHidden(false);
				$schedule->setDefault(true);
				$schedule->save();
			}

		}
	}

	/**
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public static function getDefaultConfigSchedule():Schedule {
		$result = self::query()
			->select([JetBackup::ID_FIELD])
			->where([ self::NAME, "=", self::DEFAULT_CONFIG_SCHEDULE_NAME ])
			->where([ self::HIDDEN, "=", true ])
			->where([ self::DEFAULT, "=", true ])
			->getQuery()
			->first();

		if($result) return new Schedule($result[JetBackup::ID_FIELD]);

		$schedule = new Schedule();
		$schedule->setName(Schedule::DEFAULT_CONFIG_SCHEDULE_NAME);
		$schedule->setType(Schedule::TYPE_DAILY);
		$schedule->setIntervals(Schedule::DEFAULT_SCHEDULE_INTERVALS[Schedule::TYPE_DAILY]);
		$schedule->setHidden(true);
		$schedule->setDefault(true);
		$schedule->save();
		
		return $schedule;
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public function getDisplay():array {

		$jobs = BackupJob::query()
             ->select([BackupJob::NAME, BackupJob::SCHEDULES])
             ->getQuery()
             ->fetch();

		$names = [];

		foreach ($jobs as $job) {
			if (!isset($job[BackupJob::SCHEDULES])) continue;
			foreach ($job[BackupJob::SCHEDULES] as $schedule) {
				if ($schedule['_id'] === $this->getId()) {
					$names[] = $job[BackupJob::NAME];
					break;
				}
			}
		}

		return [
			JetBackup::ID_FIELD => $this->getId(),
			self::UNIQUE_ID     => $this->getUniqueId(),
			self::NAME          => $this->getName(),
			self::TYPE          => $this->getType(),
			self::TYPE_NAME     => self::TYPE_NAMES[$this->getType()] ?? '',
			self::INTERVALS     => $this->getIntervals(),
			self::BACKUP_ID     => $this->getBackupId(),
			self::JOB_ASSIGNED  => count($names),
			self::JOB_NAMES     => $names,
			self::DEFAULT       => $this->isDefault(),
		];
	}

	public function getDisplayCLI():array {

		$jobs = BackupJob::query()
             ->select([BackupJob::NAME, BackupJob::SCHEDULES])
             ->getQuery()
             ->fetch();

		$names = 0;

		foreach ($jobs as $job) {
			if (!isset($job[BackupJob::SCHEDULES])) continue;
			foreach ($job[BackupJob::SCHEDULES] as $schedule) {
				if ($schedule['_id'] === $this->getId()) {
					$names++;
					break;
				}
			}
		}

		return [
			'ID' => $this->getId(),
			'Name'          => $this->getName(),
			'Type'          => $this->getType(),
			'Intervals'     => $this->getIntervals(),
			'Backup ID'     => $this->getBackupId(),
			'Default'        => $this->isDefault(),
			'Backup Jobs Assigned'  => $names,
		];
	}

	/**
	 * @return void
	 * @throws DBException
	 * @throws FieldsValidationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function validateFields():void {

		if(!$this->getName()) throw new FieldsValidationException("Schedule name must be set");
		if(!$this->getType()) throw new FieldsValidationException("Schedule type must be set");
		if(!in_array($this->getType(), self::TYPE_ALLOWED)) throw new FieldsValidationException("Invalid schedule type, allowed types are: " . implode(',', self::TYPE_ALLOWED));
		if($this->getIntervals() === null && $this->getType() != Schedule::TYPE_AFTER_JOB_DONE) throw new FieldsValidationException("Schedule intervals must be set");

		if(isset(Schedule::ALLOWED_INTERVALS[$this->getType()])) {
			if(!is_array($this->getIntervals())) throw new FieldsValidationException("Schedule types must be array for " . Schedule::TYPE_NAMES[$this->getType()]);
			if (array_diff($this->getIntervals(), Schedule::ALLOWED_INTERVALS[$this->getType()])) {
				throw new FieldsValidationException("Invalid intervals detected. Allowed intervals: " . implode(", ", Schedule::ALLOWED_INTERVALS[$this->getType()]));
			}
		}
		if ($this->getType() == Schedule::TYPE_WEEKLY) {
			$interval = $this->getIntervals(); // 0 = Sunday (using DateTime::format('w')

			if ($interval < 0 || $interval > 6) {
				throw new FieldsValidationException(
					"Schedule intervals must be a numeric value between 0 (Sunday) and 6 (Saturday) for " .
					Schedule::TYPE_NAMES[Schedule::TYPE_WEEKLY]
				);
			}
		}


		if ($this->getType() == Schedule::TYPE_AFTER_JOB_DONE) {
			if(!$this->getBackupId()) throw new FieldsValidationException("Backup ID must be set");
			$backup = new BackupJob($this->getBackupId());
			if (!$backup->getId() || $backup->isHidden()) throw new FieldsValidationException("Backup job not found");
			if (!$backup->isEnabled()) throw new FieldsValidationException("Backup job is disabled");

		}

	}

}