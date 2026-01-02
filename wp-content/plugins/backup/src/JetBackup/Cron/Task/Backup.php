<?php

namespace JetBackup\Cron\Task;

use JetBackup\Backup\BackupAccount;
use JetBackup\Backup\BackupConfig;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Destination\Destination;
use JetBackup\Entities\Util;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\TaskException;
use JetBackup\Factory;
use JetBackup\License\License;
use JetBackup\Queue\Queue;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Backup extends Task {

	const LOG_FILENAME = 'backup';

	public function __construct() {
		parent::__construct(self::LOG_FILENAME);
	}

	/**
	 * @return void
	 * @throws TaskException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DestinationException|JBException
	 */
	public function execute():void {
		parent::execute();



		$backup_config = new BackupJob($this->getQueueItem()->getItemData()->getJobId());

		if(!License::isValid()) {
			$this->getLogController()->logMessage('Checking destinations');
			foreach($backup_config->getDestinations() as $destination_id) {
				$destination = new Destination($destination_id);
				if(in_array($destination->getType(), Destination::LICENSE_EXCLUDED)) continue;
				$this->getLogController()->logError("You can't backup to {$destination->getType()} destination without a license");
				$this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
				return;
			}
		}

		if($this->getQueueItem()->getStatus() == Queue::STATUS_PENDING) {

			$destinations = [];
			
			foreach($backup_config->getDestinations() as $destination_id) {
				$destination = new Destination($destination_id);

				$this->getLogController()->logMessage("Checking destination \"{$destination->getName()}\" ($destination_id)");
				
				if(
					!$this->_checkConnection($destination) ||
					!$this->_checkDiskUsage($destination)
				) continue;

				$destinations[] = $destination_id;
			}

			
			if(!$destinations) {

				$this->getLogController()->logError("No valid destinations found for backup, Aborting backup.");

				switch($backup_config->getType()) {
					case BackupJob::TYPE_ACCOUNT:
						$this->getQueueItem()->updateProgress('No valid destinations found for backup', -1);
					break;
					case BackupJob::TYPE_CONFIG:
						$this->getQueueItem()->updateProgress('No valid destinations found for backup', -1);
					break;
				}
				$this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
				return;
			}
			
			$this->getQueueItem()->getItemData()->setDestinations($destinations);
			$this->getQueueItem()->save();

			$this->getLogController()->logMessage('Starting ' . BackupJob::TYPE_NAMES[$backup_config->getType()] . ' Backup');

			$progress = $this->getQueueItem()->getProgress();

			switch($backup_config->getType()) {
				case BackupJob::TYPE_ACCOUNT:
					$total_items = count(Queue::STATUS_BACKUP_ACCOUNT_NAMES)+3;
					if (!($backup_config->getContains() & BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE)) $total_items -= 1; // Remove 1 step
					if (!($backup_config->getContains() & BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR)) $total_items -= 2; // Remove 2 steps - archive and compress
					elseif(!Factory::getSettingsPerformance()->isGzipCompressArchive()) $total_items -= 1; // Remove 1 step
				break;
				case BackupJob::TYPE_CONFIG:
					$total_items = count(Queue::STATUS_BACKUP_CONFIG_NAMES)+3;
					if(!Factory::getSettingsPerformance()->isGzipCompressArchive()) $total_items -= 1; // Remove 1 step
				break;
			}

			$progress->setTotalItems($total_items);
			$this->getQueueItem()->save();


			$this->getQueueItem()->updateProgress('Starting backup job');
		} elseif($this->getQueueItem()->getStatus() > Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Resumed ' . BackupJob::TYPE_NAMES[$backup_config->getType()] . ' Backup');
		}
		
		switch($backup_config->getType()) {
			case BackupJob::TYPE_ACCOUNT: $backup = new BackupAccount($this); break;
			case BackupJob::TYPE_CONFIG: $backup = new BackupConfig($this); break;
		}

		$backup->execute();
	}

	private function _checkConnection(Destination $destination):bool {

		try {

			$retries = 0;
			while(true) {
				$retries++;
				try {
					$destination->connect();
					break;
				} catch(ConnectionException $e) {
					if($retries >= 3) throw $e;
					sleep(3);
				}
			}
			
			$this->getLogController()->logMessage("\t- Connection to the destination is valid");
		} catch(ConnectionException $e) {
			$this->getLogController()->logError("\t- Failed connecting destination. Error: {$e->getMessage()}");
			return false;
		}

		return true;
	}

	/**
	 * @throws DestinationException
	 */
	private function _checkDiskUsage(Destination $destination):bool {

		if(!($limit = $destination->getFreeDisk())) {
			$this->getLogController()->logMessage("\t- Disk space usage is disabled, skipping check");
			return true;
		}

		$this->getLogController()->logMessage("\t- Disk space usage is set to $limit%");

		$info = $destination->getInstance()->getDiskInfo();
		$this->getCronLogController()->logDebug("\t- Disk info output from destination:\n" . print_r($info, true));
		if( !$info || !$info->getTotalSpace()) {
			$this->getLogController()->logMessage("\t- Cannot retrieved usage information from destination, skipping check");
			return true;
		}

		$usage = number_format(($info->getUsageSpace() / $info->getTotalSpace()) * 100, 2);
		$this->getLogController()->logMessage("\t- Disk Space Usage is $usage% (" . Util::bytesToHumanReadable($info->getUsageSpace()) . " out of " . Util::bytesToHumanReadable($info->getTotalSpace()) . ")");

		if($usage < $limit) return true;

		$this->getLogController()->logError("\t- Disk space usage reached to the limit of $limit%");
		return false;
	}
}