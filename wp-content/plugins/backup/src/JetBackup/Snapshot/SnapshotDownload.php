<?php

namespace JetBackup\Snapshot;

use Exception;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Cron\Task\Task;
use JetBackup\Data\Engine;
use JetBackup\Destination\Destination;
use JetBackup\Exception\DBException;
use JetBackup\Exception\IOException;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use JetBackup\Queue\QueueItem;
use JetBackup\ResumableTask\ResumableTask;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class SnapshotDownload {
	
	private string $_target;
	private Snapshot $_snapshot;
	private ?Destination $_destination=null;
	private LogController $_logController;
	private ?QueueItem $_queueItem=null;
	private ?Task $_task=null;
	private ?ResumableTask $_resumableTask=null;
	private ?array $_exclude_items=[];
	private ?array $_exclude_databases=[];
	private ?array $_include_databases=[];
	private bool $_all=false;

	/**
	 * @param Snapshot $snapshot
	 * @param string $target
	 *
	 * @throws IOException
	 */
	public function __construct(Snapshot $snapshot, string $target) {
		$this->_target = $target;
		$this->_snapshot = $snapshot;
		$this->_logController = new LogController();

		if($snapshot->getEngine() == Engine::ENGINE_JB) throw new IOException("You can't download JetBackup Linux snapshots");
	}

	/**
	 * @param LogController $logController
	 *
	 * @return void
	 */
	public function setLogController(LogController $logController) { $this->_logController = $logController; }

	/**
	 * @return LogController
	 */
	public function getLogController():LogController { return $this->_logController; }

	/**
	 * @param QueueItem $queue_item
	 *
	 * @return void
	 */
	public function setQueueItem(QueueItem $queue_item) { $this->_queueItem = $queue_item; }

	/**
	 * @return QueueItem|null
	 */
	public function getQueueItem():?QueueItem { return $this->_queueItem; }

	/**
	 * @param Task $task
	 *
	 * @return void
	 */
	public function setTask(Task $task) { $this->_task = $task; }

	/**
	 * @return Task|null
	 */
	public function getTask():?Task { return $this->_task; }

	/**
	 * @param array $exclude
	 *
	 * @return void
	 */
	public function setExcludedItems(array $exclude):void { $this->_exclude_items = $exclude; }

	/**
	 * @return array
	 */
	public function getExcludedItems():array { return $this->_exclude_items; }

	/**
	 * @param array $exclude
	 *
	 * @return void
	 */
	public function setExcludedDatabases(array $exclude):void { $this->_exclude_databases = $exclude; }

	/**
	 * @return array
	 */
	public function getExcludedDatabases():array { return $this->_exclude_databases; }

	/**
	 * @param array $include
	 *
	 * @return void
	 */
	public function setIncludedDatabases(array $include):void { $this->_include_databases = $include; }

	/**
	 * @return array
	 */
	public function getIncludedDatabases():array { return $this->_include_databases; }
	
	/**
	 * @return Destination
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function getDestination():Destination {
		if(!$this->_destination) {
			$this->_destination = new Destination($this->_snapshot->getDestinationId());
			$this->_destination->setLogController($this->getLogController());
		}
		return $this->_destination; 
	}

	/**
	 * @return ResumableTask
	 */
	public function getResumableTask():ResumableTask { 
		
		if(!$this->_resumableTask) {
			if($this->getQueueItem()) $this->_resumableTask = $this->getQueueItem()->getResumableTask();
			else $this->_resumableTask = new ResumableTask(sha1($this->_target));
			$this->_resumableTask->setLogController($this->getLogController());
		}

		return $this->_resumableTask; 
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function downloadMeta() {

		$this->getResumableTask()->func(function() {

			if(!file_exists($this->_target)) @mkdir($this->_target, 0700, true);

			if($this->_snapshot->getEngine() != Engine::ENGINE_WP) return;

			$source = sprintf(Snapshot::META_FILEPATH, $this->_snapshot->getJobIdentifier() . '/' . $this->_snapshot->getName());
			$meta_target = $this->_target . JetBackup::SEP . Snapshot::SKELETON_META_DIRNAME;

			if(!file_exists($meta_target)) @mkdir($meta_target, 0700, true);

			$meta_target .= JetBackup::SEP . Snapshot::SKELETON_META_FILENAME;

			$this->getLogController()->logMessage("Downloading meta file");
			$this->getLogController()->logDebug("Meta target: $meta_target");
			$this->getLogController()->logDebug("Meta source: $source");
			$this->getDestination()->copyFileToLocal($source, $meta_target, $this->getQueueItem(), $this->getTask());

		}, [], 'download_meta_snapshot_' . $this->_snapshot->getId());

		// delete the resumable task file only if created in this class
		if(!$this->_all && !$this->getQueueItem()) $this->getResumableTask()->delete();
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function downloadLog() {

		$this->getResumableTask()->func(function() {

			if(!file_exists($this->_target)) @mkdir($this->_target, 0700, true);
			if($this->_snapshot->getEngine() != Engine::ENGINE_WP) return;

			$basePath = implode(JetBackup::SEP, [
				$this->_snapshot->getJobIdentifier(),
				$this->_snapshot->getName(),
				Snapshot::SKELETON_LOG_DIRNAME
			]);

			// Example: job_1_6859229d0007378e00000000/snap_2025-06-23_094733_685922b5000206e700000000/log/task.log.gz
			$source = $basePath . JetBackup::SEP . Snapshot::SKELETON_LOG_FILENAME;

			// Backward compatibility
			if (!$this->getDestination()->fileExists($source)) $source = $basePath . JetBackup::SEP . 'task.log';

			if (!$this->getDestination()->fileExists($source)) {
				$this->getLogController()->logMessage("Log file $source not found, skipping");
				return;
			}

			$log_target = implode(JetBackup::SEP, [
				$this->_target,
				Snapshot::SKELETON_LOG_DIRNAME,
				basename($source)
			]);


			if(!file_exists(dirname($log_target))) @mkdir(dirname($log_target), 0700, true);
			$this->getLogController()->logMessage("Downloading log file");
			$this->getLogController()->logDebug("Log target: $log_target");
			$this->getLogController()->logDebug("Log source: $source");
            if($this->getDestination()->fileExists($source)) $this->getDestination()->copyFileToLocal($source, $log_target, $this->getQueueItem(), $this->getTask());

		}, [], 'download_log_snapshot_' . $this->_snapshot->getId());

		// delete the resumable task file only if created in this class
		if(!$this->_all && !$this->getQueueItem()) $this->getResumableTask()->delete();
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function downloadItems() {


		if(!($items = $this->_snapshot->getItems())) return;

		if(!file_exists($this->_target)) @mkdir($this->_target, 0700, true);

		$this->getResumableTask()->func(function() use ($items) {
			if(!$this->getQueueItem()) return;
			$total = 0;
			foreach ($items as $item) $total += $item->getSize();
			$this->getQueueItem()->getProgress()->setTotalSubItems($total);
			$this->getQueueItem()->save();

		}, [], 'download_snapshot_progress_' . $this->_snapshot->getId());

		$this->getResumableTask()->foreach($items, function ($i, SnapshotItem $item) {
				// Do not enter tables download if there is no db items (skip db restore)
				if (in_array($item->getBackupContains(), $this->getExcludedItems())) return;
				// Handle database-specific exclusions/inclusions
				if ($item->getBackupContains() == BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE) {
					$dbName = $item->getName();
					// Download only included tables
					if ($this->getIncludedDatabases() && !in_array($dbName, $this->getIncludedDatabases())) return;
					// Do not download excluded tables
					if ($this->getExcludedDatabases() && in_array($dbName, $this->getExcludedDatabases())) return;
				}
				// Download the item
				$item->download(
					$this->_target,
					$this->getLogController(),
					$this->_snapshot,
					$this->getDestination(),
					$this->getQueueItem(),
					$this->getTask()
				);

			}, 'download_snapshot_items_' . $this->_snapshot->getId()
		);

		// delete the resumable task file only if created in this class
		if(!$this->_all && !$this->getQueueItem()) $this->getResumableTask()->delete();
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function downloadAll() {
		
		$this->_all = true;
		$this->downloadMeta();
		$this->downloadLog();
		$this->downloadItems();
		$this->_all = false;

		// delete the resumable task file only if created in this class
		if(!$this->getQueueItem()) $this->getResumableTask()->delete();

	}
}