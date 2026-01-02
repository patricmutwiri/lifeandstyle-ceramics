<?php

namespace JetBackup\Cron\Task;

use JetBackup\Archive\Archive;
use JetBackup\Archive\Gzip;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Data\Engine;
use JetBackup\Destination\Destination;
use JetBackup\DirIterator\DirIterator;
use JetBackup\Entities\Util;
use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DirIteratorException;
use JetBackup\Exception\DirIteratorFileVanishedException;
use JetBackup\Exception\DownloaderException;
use JetBackup\Exception\SGBExtractorException;
use JetBackup\Exception\TaskException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\License\License;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemDownload;
use JetBackup\SGB\Extractor;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Snapshot\SnapshotDownload;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Download extends Task {

	const LOG_FILENAME = 'download';

	private Snapshot $_snapshot;
	private QueueItemDownload $_queue_item_download;
	private string $_target;

	public function __construct() {
		parent::__construct(self::LOG_FILENAME);
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws TaskException
	 */
	public function execute():void {
		parent::execute();

		$this->_queue_item_download = $this->getQueueItem()->getItemData();
		$this->_snapshot = new Snapshot($this->_queue_item_download->getSnapshotId());
		
		$destination = new Destination($this->_snapshot->getDestinationId());

		if(!License::isValid() && !in_array($destination->getType(), Destination::LICENSE_EXCLUDED)) {
			$this->getLogController()->logError("You can't download backups from {$destination->getType()} destination without a license");
			$this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
			$this->getQueueItem()->updateProgress('Download Aborted!', QueueItem::PROGRESS_LAST_STEP);
			return;
		}

		if($this->getQueueItem()->getStatus() == Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Starting Download Task');

			$this->getQueueItem()->getProgress()->setTotalItems(count(Queue::STATUS_DOWNLOAD_NAMES));
			$this->getQueueItem()->save();

			$this->getQueueItem()->updateProgress('Starting Download Task');
		} elseif($this->getQueueItem()->getStatus() > Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Resumed Download Task');
		}
		
		$this->_target = $this->getQueueItem()->getWorkspace() . JetBackup::SEP . $this->_snapshot->getName() . Archive::ARCHIVE_EXT;

		try {
			$this->func([$this, '_download']);
			$this->func([$this, '_extractLegacy']);
			$this->func([$this, '_archive']);
			// Not sure if we need this, all backup items are already compressed
			//$this->func([$this, '_compress']);
			$this->func([$this, '_moveDownload'], [$this->_target]);
			if($this->getQueueItem()->getStatus() < Queue::STATUS_DONE && !$this->getQueueItem()->getErrors()) $this->getQueueItem()->updateStatus(Queue::STATUS_DONE);
			else $this->getQueueItem()->updateStatus(Queue::STATUS_PARTIALLY);
			$this->getLogController()->logMessage('Completed!');
		} catch(\Exception $e) {
			$this->getQueueItem()->updateStatus(Queue::STATUS_FAILED);
			$this->getLogController()->logError($e->getMessage());
			$this->getLogController()->logMessage('Failed!');
		}

		$this->getQueueItem()->updateProgress(
			$this->getQueueItem()->getStatus() == Queue::STATUS_DONE
				? 'Download Completed!'
				: ($this->getQueueItem()->getStatus() == Queue::STATUS_PARTIALLY
				? 'Completed with errors (see logs)'
				: 'Download Failed!'),
			QueueItem::PROGRESS_LAST_STEP
		);

		$this->getLogController()->logMessage('Total time: ' . $this->getExecutionTimeElapsed());
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws \JetBackup\Exception\IOException
	 */
	public function _download() {

		$queue_item = $this->getQueueItem();

		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$queue_item->updateStatus(Queue::STATUS_DOWNLOAD_DOWNLOAD);
		$queue_item->updateProgress('Downloading backup files');
		$this->getLogController()->logMessage('Downloading backup files');

		$download = new SnapshotDownload($this->_snapshot, $this->getQueueItem()->getWorkspace());
		$download->setLogController($this->getLogController());
		$download->setQueueItem($this->getQueueItem());
		$download->setTask($this);
		$download->downloadAll();
		
		// done downloading, reset sub process bar
		$queue_item->getProgress()->resetSub();
		$queue_item->save();
	}
	
	public function _extractLegacy() {
		if($this->_snapshot->getEngine() != Engine::ENGINE_SGB) return;

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
	}

	/**
	 * @return void
	 * @throws ArchiveException
	 * @throws DirIteratorException
	 * @throws \JetBackup\Exception\IOException
	 */
	public function _archive() {

		$queue_item = $this->getQueueItem();

		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$queue_item->updateStatus(Queue::STATUS_DOWNLOAD_ARCHIVE);
		$queue_item->updateProgress('Archiving backup files');
		$this->getLogController()->logMessage('Archiving backup files');
		
		$archive = new Archive($this->_target, false, Archive::OPT_SPARSE, 0, $this->getQueueItem()->getWorkspace());
		$archive->setLogController($this->getLogController());

		$this->scan($this->getQueueItem()->getWorkspace(), function(DirIterator $scan, $data) use ($archive) {

			if (!$data->total_size) throw new ArchiveException('Invalid total tree size');

			$archive->setAppend(!($data->total_size == $data->current_pos));

			if(!$archive->isAppend()) {
				$this->getLogController()->logMessage('Inside Archive Manager First run');
				$this->getLogController()->logMessage('Total tree size: ' . $data->total_size);
				$this->getLogController()->logMessage('Current tree POS: ' . $data->current_pos);
			}

			try {
				$fd = $archive->getFileFD();
				$current_file = $scan->next($fd ? $fd->tell() : 0);
			} catch (DirIteratorFileVanishedException $e) {
				$this->getLogController()->logMessage('[ WARNING ] File Vanished : ' . $e->getMessage());
				return;
			}

			if($scan->getSource() == $current_file->getName()) return;
			$this->getLogController()->logMessage("[". ($data->total_size - $data->current_pos)."/$data->total_size] Archiving: {$current_file->getName()}");

			try {

				$file = substr($current_file->getName(), strlen($this->getQueueItem()->getWorkspace())+1);
				$archive->appendFileChunked($current_file, $file, function() use ($data) {

					$progress = $this->getQueueItem()->getProgress();
					$progress->setMessage("Archiving");
					$progress->setTotalSubItems($data->total_size);
					$progress->setCurrentSubItem($data->total_size - $data->current_pos);
					$this->getQueueItem()->save();

					// We should return true end exit later, however I want to try to exit here to see if this makes issues
					$this->checkExecutionTime(function() use ($data) {

						$progress = $this->getQueueItem()->getProgress();
						$progress->setSubMessage("Waiting for next cron iteration");
						$progress->setTotalSubItems($data->total_size);
						$progress->setCurrentSubItem($data->total_size - $data->current_pos);
						$this->getQueueItem()->save();
					});

					return false;

				}, Factory::getSettingsPerformance()->getReadChunkSizeBytes());
			} catch(\Exception|ArchiveException $e) {
				//this will throw exception if the file has been changed more than 3 times
				$this->getLogController()->logError('[Download] Error while trying to archive: ' . $e->getMessage());
			}

		}, ['*.resume', '*.scan']);

		$archive->save();
		$this->getLogController()->logMessage('[Download] Archive Done');
	}

	/**
	 * Retrieve the site URL from the snapshot to account for cases where the backup was created on a different domain and later imported.
	 * If the site URL is unavailable, fallback to the current site's domain.
	 * The site URL is included in the downloaded backup file to help users differentiate between backups.
	 *
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function _moveDownload($file) {

		$prefix = isset($this->_snapshot->getParams()['site_url']) && $this->_snapshot->getParams()['site_url']
			? str_replace('.', '_', parse_url($this->_snapshot->getParams()['site_url'], PHP_URL_HOST))
			: null;

		$download = \JetBackup\Download\Download::create($file, $prefix);
		$this->_queue_item_download->setDownloadId($download->getId());
		$this->getQueueItem()->save();

		$this->getLogController()->logMessage("Download Id: " . $download->getId());
	}
}