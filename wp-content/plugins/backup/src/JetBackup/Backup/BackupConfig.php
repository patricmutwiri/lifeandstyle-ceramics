<?php

namespace JetBackup\Backup;

use JetBackup\Alert\Alert;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Config\Config;
use JetBackup\Entities\Util;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Snapshot\SnapshotItem;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class BackupConfig extends Backup {

	const SKELETON_CONFIG_DIRNAME = 'config';
	const SKELETON_DATABASE_DIRNAME = 'database';
	
	public function execute():void {

		try {
			$this->getTask()->func([$this, '_createWorkspace']);
			$this->getTask()->func([$this, '_archiveFiles'], [$this->getSnapshotDirectory()]);
			$this->getTask()->func([$this, '_compressFiles']);
			$this->getTask()->func([$this, '_transferToDestination']);

			if($this->getQueueItem()->getStatus() < Queue::STATUS_DONE) {
				$backup_destinations = sizeof($this->getBackupJob()->getDestinations());
				$queue_destinations = sizeof($this->getQueueItemBackup()->getDestinations());

				if($backup_destinations == $queue_destinations) $this->getQueueItem()->updateStatus(Queue::STATUS_DONE);
				else $this->getQueueItem()->updateStatus(Queue::STATUS_PARTIALLY);
			}

			$this->getLogController()->logMessage('Completed!');

			$this->getQueueItem()->updateProgress('Backup Completed! ' . '['.$this->getBackupJob()->getName().']', QueueItem::PROGRESS_LAST_STEP);

			$this->getBackupJob()->calculateNextRun();
			$this->getBackupJob()->setLastRun(time());
			$this->getBackupJob()->save();

			/*
			 * Case #853, users are getting confused by this email, replacing with internal alert for now
			//Send notification
			Notification::message()
				->addParam('backup_domain', Wordpress::getSiteDomain())
	            ->addParam('job_name', $this->getBackupJob()->getName())
	            ->addParam('backup_date', Util::date('Y-m-d H:i:s', $this->getBackupJob()->getLastRun()))
	            ->addParam('backup_status', Queue::STATUS_NAMES[$this->getQueueItem()->getStatus()])
	            ->send('JetBackup Completed', 'job_completed');
			*/

			Alert::add('Internal Config Backup', "Internal config backup job is done", Alert::LEVEL_INFORMATION);

			//Add retention cleanup to the queue after job is done;
			//This has to run AFTER reindex because the data is based on reindex table
			$this->_retentionCleanup();

			//Add to queue backups that have 'After job is done'
			$this->_calculateAfterJobDone();

		} catch(\Exception $e) {
			$this->getQueueItem()->updateStatus(Queue::STATUS_FAILED);
			$this->getLogController()->logError($e->getMessage());
			$this->getLogController()->logMessage('Failed!');

			$progress = $this->getQueueItem()->getProgress();

			$progress->setMessage('Backup Failed!');
			$progress->setCurrentItem(7);
			$this->getQueueItem()->save();

			$this->getBackupJob()->calculateNextRun();
			$this->getBackupJob()->setLastRun(time());
			$this->getBackupJob()->save();
		}

		$this->getLogController()->logMessage('Total time: ' . $this->getTask()->getExecutionTimeElapsed());
	}

	public function _transferToDestination() {

		$this->getLogController()->logMessage('Execution time: ' . $this->getTask()->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getTask()->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_BACKUP_CONFIG_SEND_TO_DESTINATION);
		$this->getQueueItem()->updateProgress('Transferring backup to destinations');

		parent::_transferToDestination();
	}

	public function _compressFiles() {

		if(!Factory::getSettingsPerformance()->isGzipCompressArchive()) {
			$this->getLogController()->logMessage( 'GZIP Compression is disabled, skipping!' );
			return;
		}

		$this->getLogController()->logMessage('Execution time: ' . $this->getTask()->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getTask()->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_BACKUP_CONFIG_COMPRESSING);
		$this->getQueueItem()->updateProgress('Compressing backup');

		parent::_compressFiles();
	}

	public function _createWorkspace():void {
		$this->getLogController()->logMessage('Execution time: ' . $this->getTask()->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getTask()->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_PREPARING);
		$this->getQueueItem()->updateProgress('Prepare workspace');

		parent::_createWorkspace();
	}

	public function _archiveFiles($source): void {

		$this->getLogController()->logMessage('Execution time: ' . $this->getTask()->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getTask()->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_BACKUP_CONFIG_ARCHIVING);
		$this->getQueueItem()->updateProgress('Archiving data');

		Util::cp(Config::CONFIG_FILE, $source . JetBackup::SEP . Snapshot::SKELETON_CONFIG_DIRNAME . JetBackup::SEP . basename(Config::CONFIG_FILE));
		Util::cp(Factory::getLocations()->getDatabaseDir(), $source . JetBackup::SEP . Snapshot::SKELETON_DATABASE_DIRNAME);

		parent::_archiveFiles($source);
		
		Util::rm($source . JetBackup::SEP . Snapshot::SKELETON_CONFIG_DIRNAME);
		Util::rm($source . JetBackup::SEP . Snapshot::SKELETON_DATABASE_DIRNAME);
	}

	protected function getSnapshotItems(): array {
		
		$output = [];

		$path = self::SKELETON_DATABASE_DIRNAME . JetBackup::SEP . Snapshot::SKELETON_FILES_ARCHIVE_NAME . (Factory::getSettingsPerformance()->isGzipCompressArchive() ? '.gz' : '');
		$homedir = $this->getSnapshotDirectory() . JetBackup::SEP . $path;
		$size = file_exists($homedir) ? filesize($homedir) : 0;

		// Full Item
		$item = new SnapshotItem();
		$item->setBackupType(BackupJob::TYPE_CONFIG);
		$item->setBackupContains(BackupJob::BACKUP_CONFIG_CONTAINS_DATABASE);
		$item->setCreated(time());
		$item->setName('');
		$item->setSize($size);
		$item->setPath($path);
		$output[] = $item;

		$item = new SnapshotItem();
		$item->setBackupType(BackupJob::TYPE_CONFIG);
		$item->setBackupContains(BackupJob::BACKUP_CONFIG_CONTAINS_FULL);
		$item->setCreated(time());
		$item->setName('');
		$item->setSize(0);
		$item->setPath(BackupConfig::SKELETON_CONFIG_DIRNAME . JetBackup::SEP . Snapshot::SKELETON_FILES_ARCHIVE_NAME . (Factory::getSettingsPerformance()->isGzipCompressArchive() ? '.gz' : ''));
		$output[] = $item;

		return $output;
	}
}