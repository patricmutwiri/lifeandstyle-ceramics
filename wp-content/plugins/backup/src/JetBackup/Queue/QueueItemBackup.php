<?php

namespace JetBackup\Queue;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class QueueItemBackup extends aQueueItem {
	
	const JOB_ID = 'job_id';
	const SNAPSHOT_NAME = 'snapshot_name';
	const DESTINATIONS = 'destinations';
	const MANUALLY = 'manually';
	const AFTER_JOB_DONE = 'after_job_done';
	const TYPE = 'type';

	public function setType(int $type):void { $this->set(self::TYPE, $type); }
	public function getType():int { return (int) $this->get(self::TYPE, 0); }
	public function setJobId(int $_id):void { $this->set(self::JOB_ID, $_id); }
	public function getJobId():int { return (int) $this->get(self::JOB_ID, 0); }

	public function setSnapshotName(string $name):void { $this->set(self::SNAPSHOT_NAME, $name); }
	public function getSnapshotName():string { return $this->get(self::SNAPSHOT_NAME); }

	public function setDestinations(array $destinations):void {$this->set(self::DESTINATIONS, $destinations);}
	public function getDestinations():array {return $this->get(self::DESTINATIONS, []);}

	public function setManually(bool $manually):void { $this->set(self::MANUALLY, $manually); }
	public function isManually():bool { return !!$this->get(self::MANUALLY, false); }

	public function setAfterJobDone(bool $job_done):void { $this->set(self::AFTER_JOB_DONE, $job_done); }
	public function isAfterJobDone():bool { return !!$this->get(self::AFTER_JOB_DONE, false); }

	public function getDisplay():array {
		return [
			self::JOB_ID                => $this->getJobId(),
			self::SNAPSHOT_NAME         => $this->getSnapshotName(),
			self::DESTINATIONS          => $this->getDestinations(),
			self::MANUALLY              => $this->isManually(),
			self::AFTER_JOB_DONE        => $this->isAfterJobDone(),
		];
	}
}
