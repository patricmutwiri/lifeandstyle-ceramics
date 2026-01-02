<?php

namespace JetBackup\Cron\Task;

use JetBackup\Data\Engine;
use JetBackup\Destination\Destination;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DownloaderException;
use JetBackup\Exception\ExtractException;
use JetBackup\Exception\SGBExtractorException;
use JetBackup\Exception\TaskException;
use JetBackup\JetBackup;
use JetBackup\License\License;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemExtract;
use JetBackup\SGB\Extractor;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Snapshot\SnapshotDownload;
use JetBackup\Snapshot\SnapshotItem;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Extract extends Task {

	const LOG_FILENAME = 'extract';

	private Snapshot $_snapshot;
	private string $_target;

	public function __construct() {
		parent::__construct(self::LOG_FILENAME);
	}

	/**
	 * @return void
	 * @throws DBException
	 * @throws TaskException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function execute():void {
		parent::execute();

		/** @var QueueItemExtract $queue_item */
		$queue_item = $this->getQueueItem()->getItemData();

		$this->_snapshot = new Snapshot($queue_item->getSnapshotId());

		$destination = new Destination($this->_snapshot->getDestinationId());

		if(!License::isValid() && !in_array($destination->getType(), Destination::LICENSE_EXCLUDED)) {
			$this->getLogController()->logError("You can't extract backups from {$destination->getType()} destination without a license");
			$this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
			$this->getQueueItem()->updateProgress('Extract Aborted!', QueueItem::PROGRESS_LAST_STEP);
			return;
		}

		$this->_target = $queue_item->getExtractPath() ?: $this->getQueueItem()->getWorkspace();

		$this->getLogController()->logDebug('getExtractPath: ' . $queue_item->getExtractPath());
		$this->getLogController()->logDebug('getWorkspace: ' . $this->getQueueItem()->getWorkspace());

		if($this->getQueueItem()->getStatus() == Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Starting Extract Task');

			$this->getQueueItem()->getProgress()->setTotalItems(count(Queue::STATUS_EXTRACT_NAMES)+3);
			$this->getQueueItem()->save();

			$this->getQueueItem()->updateProgress('Starting Extract Task');
		} elseif($this->getQueueItem()->getStatus() > Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Resumed Extract Task');
		}

		try {
			$this->func([$this, '_download']);
			$this->func([$this, '_extract']);

			$queue_item->setExtractPath($this->_target);
			$this->getQueueItem()->save();

			if($this->getQueueItem()->getStatus() < Queue::STATUS_DONE && !$this->getQueueItem()->getErrors()) $this->getQueueItem()->updateStatus(Queue::STATUS_DONE);
			else $this->getQueueItem()->updateStatus(Queue::STATUS_PARTIALLY);
			$this->getLogController()->logMessage('Completed!');
		} catch(ExtractException $e) {
			$this->getQueueItem()->updateStatus(Queue::STATUS_FAILED);
			$this->getLogController()->logError($e->getMessage());
			$this->getLogController()->logMessage('Failed!');
		}

		$this->getQueueItem()->updateProgress(
			$this->getQueueItem()->getStatus() == Queue::STATUS_DONE
				? 'Extract Completed!'
				: ($this->getQueueItem()->getStatus() == Queue::STATUS_PARTIALLY
				? 'Completed with errors (see logs)'
				: 'Extract Failed!'),
			QueueItem::PROGRESS_LAST_STEP
		);

		$this->getLogController()->logMessage('Total time: ' . $this->getExecutionTimeElapsed());
	}

	/**
	 * @return array
	 * @throws ExtractException
	 */
	public function _getItems():array {
		
		$items = [];

		try {
			$list = SnapshotItem::query()
				->where([SnapshotItem::PARENT_ID, '=', $this->_snapshot->getId()])
				->getQuery()
				->fetch();
		} catch(\Exception $e) {
			throw new ExtractException($e->getMessage());
		}

		foreach($list as $item_details) $items[] = $item_details[JetBackup::ID_FIELD];

		return $items;
	}

	/**
	 * @return void
	 * @throws ExtractException
	 */
	public function _download() {

		$queue_item = $this->getQueueItem();

		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$queue_item->updateStatus(Queue::STATUS_EXTRACT_DOWNLOAD);
		$queue_item->updateProgress('Downloading backup files');
		$this->getLogController()->logMessage('Downloading backup files');
		
		try {
			$download = new SnapshotDownload($this->_snapshot, $this->_target);
			$download->setLogController($this->getLogController());
			$download->setQueueItem($this->getQueueItem());
			$download->setTask($this);
			$download->downloadAll();
		} catch(\Exception $e) {
			throw new ExtractException($e->getMessage());
		}

		// done downloading, reset sub process bar
		$queue_item->getProgress()->resetSub();
		$queue_item->save();
	}

	/**
	 * @return void
	 * @throws ExtractException
	 */
	public function _extract():void {

		$queue_item = $this->getQueueItem();
		
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$queue_item->updateStatus(Queue::STATUS_EXTRACT_EXTRACT);
		$queue_item->updateProgress('Extracting data');
		$this->getLogController()->logMessage('Extracting backup data to ' . $this->_target);

		if($this->_snapshot->getEngine() == Engine::ENGINE_SGB) {

			// SGB snapshot has only 1 item
			$item = $this->_snapshot->getItems()[0];

			$path = $this->getQueueItem()->getWorkspace() . JetBackup::SEP . $item->getPath();

			try {
				$extractor = new Extractor($path, $this->getQueueItem()->getWorkspace());
				$extractor->setLogController($this->getLogController());
				$extractor->extract(function() {
					$this->checkExecutionTime();
				});
			} catch(SGBExtractorException $e) {
				throw new DownloaderException($e->getMessage());
			}

			unlink($path);



		} else {

			$this->foreachCallable([$this, '_getItems'], [], function ($i, $item_id) {
				$item = new SnapshotItem($item_id);

				$callback = function (string $type, string $action, int $total, int  $read) {
					//sleep(1);
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

				};

				$item->extract($this->_target, $this->getLogController(), $callback);
			});

		}
	}
}