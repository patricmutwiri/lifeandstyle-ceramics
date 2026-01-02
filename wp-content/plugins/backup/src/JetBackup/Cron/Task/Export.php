<?php

namespace JetBackup\Cron\Task;

use JetBackup\BackupJob\BackupJob;
use JetBackup\Data\Engine;
use JetBackup\Destination\Destination;
use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DownloaderException;
use JetBackup\Exception\ExportException;
use JetBackup\Exception\GzipException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\SGBExtractorException;
use JetBackup\Exception\TaskException;
use JetBackup\JetBackup;
use JetBackup\License\License;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemExport;
use JetBackup\SGB\Extractor;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Snapshot\SnapshotDownload;
use JetBackup\Snapshot\SnapshotItem;
use JetBackup\Download\Download;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Export extends Task {

	const LOG_FILENAME = 'export';

	private Snapshot $_snapshot;
	
	private QueueItemExport $_queue_item_export;

	public function __construct() {
		parent::__construct(self::LOG_FILENAME);
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws TaskException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function execute():void {
		parent::execute();

		$this->_queue_item_export = $this->getQueueItem()->getItemData();
		$this->_snapshot = new Snapshot($this->_queue_item_export->getSnapshotId());

		if($this->_snapshot->getEngine() == Engine::ENGINE_SGB) {
			$this->getLogController()->logError("You can't export legacy backups");
			$this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
			$this->getQueueItem()->updateProgress('Export Aborted!', QueueItem::PROGRESS_LAST_STEP);
			return;
		}

		$destination = new Destination($this->_snapshot->getDestinationId());

		if(!License::isValid() && !in_array($destination->getType(), Destination::LICENSE_EXCLUDED)) {
			$this->getLogController()->logError("You can't export backups from {$destination->getType()} destination without a license");
			$this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
			$this->getQueueItem()->updateProgress('Export Aborted!', QueueItem::PROGRESS_LAST_STEP);
			return;
		}

		if($this->getQueueItem()->getStatus() == Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Starting Export Task');

			$this->getQueueItem()->getProgress()->setTotalItems(count(Queue::STATUS_EXPORT_NAMES)+3);
			$this->getQueueItem()->save();

			$this->getQueueItem()->updateProgress('Starting Export Task');
		} elseif($this->getQueueItem()->getStatus() > Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Resumed Export Task');
		}

		try {
			$this->func([$this, '_download']);
			$this->func([$this, '_extract']);
			$this->func([$this, '_build']);
			if($this->getQueueItem()->getStatus() < Queue::STATUS_DONE  && !$this->getQueueItem()->getErrors()) $this->getQueueItem()->updateStatus(Queue::STATUS_DONE);
			else $this->getQueueItem()->updateStatus(Queue::STATUS_PARTIALLY);
			$this->getLogController()->logMessage('Completed!');
		} catch(ExportException $e) {
			$this->getQueueItem()->updateStatus(Queue::STATUS_FAILED);
			$this->getLogController()->logError($e->getMessage());
			$this->getLogController()->logMessage('Failed!');
		}

		$this->getQueueItem()->updateProgress(
			$this->getQueueItem()->getStatus() == Queue::STATUS_DONE
				? 'Export Completed!'
				: ($this->getQueueItem()->getStatus() == Queue::STATUS_PARTIALLY
				? 'Completed with errors (see logs)'
				: 'Export Failed!'),
			QueueItem::PROGRESS_LAST_STEP
		);

		$this->getLogController()->logMessage('Total time: ' . $this->getExecutionTimeElapsed());
	}

	/**
	 * @return void
	 * @throws ExportException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function _download() {

		$queue_item = $this->getQueueItem();

		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$queue_item->updateStatus(Queue::STATUS_EXPORT_DOWNLOAD);
		$queue_item->updateProgress('Getting files for extraction');
		$this->getLogController()->logMessage('Downloading backup files');

		try {
			$download = new SnapshotDownload($this->_snapshot, $this->getQueueItem()->getWorkspace());
			$download->setLogController($this->getLogController());
			$download->setQueueItem($this->getQueueItem());
			$download->setTask($this);
			$download->downloadAll();
		} catch (\Exception $e) {
			throw new ExportException($e->getMessage());
		}

		// done downloading, reset sub process bar
		$queue_item->getProgress()->resetSub();
		$queue_item->save();
	}

	/**
	 * @return void
	 * @throws ExportException
	 */
	public function _extract():void {

		$queue_item = $this->getQueueItem();

		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$queue_item->updateStatus(Queue::STATUS_EXPORT_EXTRACT);
		$queue_item->updateProgress('Extracting data');
		$this->getLogController()->logMessage('Extracting backup data to ' . $this->getQueueItem()->getWorkspace());

		$this->foreachCallable([$this, '_getItems'], [], function($i, $item_id) {
			$item = new SnapshotItem($item_id);
			try {
				$item->extract($this->getQueueItem()->getWorkspace(), $this->getLogController(), function(string $type, string $action, int $total, int  $read) {

					$progress = $this->getQueueItem()->getProgress();
					$progress->setMessage($type); // gzip / archive
					$progress->setSubMessage($action); // decompress / extract
					$progress->setTotalSubItems($total);
					$progress->setCurrentSubItem($read);
					$this->getQueueItem()->save();

					// Call checkExecutionTime, passing the desired variables
					$this->checkExecutionTime(function () use ($type, $action, $total, $read) {
						$progress = $this->getQueueItem()->getProgress();
						$progress->setMessage($type);
						$progress->setSubMessage('Waiting for next cron');
						$progress->setTotalSubItems($total);
						$progress->setCurrentSubItem($read);
						$this->getQueueItem()->save();
					});
				});
			} catch(ArchiveException|GzipException $e) {
				throw new ExportException($e->getMessage());
			}
		}, 'extract_items');
	}

	/**
	 * @return void
	 * @throws ExportException
	 */
	public function _build() {

		$queue_item = $this->getQueueItem();

		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$queue_item->updateStatus(Queue::STATUS_EXPORT_BUILD);
		$queue_item->updateProgress('Extracting data');
		$this->getLogController()->logMessage('Extracting backup data to ' . $this->getQueueItem()->getWorkspace());

		$type = $this->_queue_item_export->getType();
		$workspace = $this->getQueueItem()->getWorkspace();
		
		$homedir = $workspace . JetBackup::SEP . Snapshot::SKELETON_FILES_DIRNAME;
		$destination = $workspace . JetBackup::SEP . 'skeleton';
		$database_tables = [];

		try {

			$list = SnapshotItem::query()
                ->where([SnapshotItem::PARENT_ID, '=', $this->_snapshot->getId()])
                ->where([SnapshotItem::BACKUP_CONTAINS, '=', BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE])
                ->getQuery()
                ->fetch();
		} catch(\Exception $e) {
			throw new ExportException($e->getMessage());
		}

		foreach($list as $item_details) {
			$database_tables[] = $workspace . JetBackup::SEP . Snapshot::SKELETON_DATABASE_DIRNAME . JetBackup::SEP . $item_details[SnapshotItem::NAME] . '.sql';
		}

		if(!is_dir($destination)) mkdir($destination, 0700);
		
		$export = new \JetBackup\Export\Export($this);
		
		try {
			$file = $export->build($type, $homedir, $database_tables, $destination);
		} catch(IOException $e) {
			throw new ExportException($e->getMessage());
		}
		
		$this->func([$this, '_moveDownload'], [$file]);
	}

	/**
	 * @param $file
	 *
	 * @return void
	 * @throws ExportException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function _moveDownload($file) {
		
		try {
			$this->getLogController()->logDebug("[_moveDownload]] File: $file " );
			$download = Download::create($file);
		} catch(\Exception $e) {
			throw new ExportException($e->getMessage());
		}
		
		$this->_queue_item_export->setDownloadId($download->getId());
		$this->getQueueItem()->save();
		
		$this->getLogController()->logMessage("Download Id: " . $download->getId());
	}

	/**
	 * @return array
	 * @throws ExportException
	 */
	public function _getItems():array {

		$items = [];
		
		try {
			$list = SnapshotItem::query()
				->where([SnapshotItem::PARENT_ID, '=', $this->_snapshot->getId()])
				->getQuery()
				->fetch();
		} catch(\Exception $e) {
			throw new ExportException($e->getMessage());
		}

		foreach($list as $item_details) $items[] = $item_details[JetBackup::ID_FIELD];

		return $items;
	}
}