<?php

namespace JetBackup\Backup;

use Exception;
use JetBackup\Alert\Alert;
use JetBackup\Archive\Archive;
use JetBackup\Archive\Gzip;
use JetBackup\Archive\Header\Header;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Cron\Task\Task;
use JetBackup\Data\Engine;
use JetBackup\Destination\Destination;
use JetBackup\Destination\Tree;
use JetBackup\Destination\Vendors\Local\Local;
use JetBackup\DirIterator\DirIterator;
use JetBackup\Entities\Util;
use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\BackupException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\DirIteratorFileVanishedException;
use JetBackup\Exception\IOVanishedException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\QueueException;
use JetBackup\Exception\ScheduleException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemBackup;
use JetBackup\Schedule\Schedule;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

abstract class Backup {


	const WP_CONFIG_FILE = "%swp-config.php";
	const HTACCESS_FILE = "%s.htaccess";

	private Task $_task;
	private QueueItemBackup $_queue_item_backup;
	private BackupJob $_backup_job;

	public function __construct(Task $task) {
		$this->_task = $task;

		$this->_queue_item_backup = $this->getQueueItem()->getItemData();
		$this->_backup_job        = new BackupJob($this->getQueueItemBackup()->getJobId());
	}
	
	public function getTask():Task { return $this->_task; }
	public function getLogController():LogController { return $this->getTask()->getLogController(); }
	public function getQueueItem(): QueueItem { return $this->getTask()->getQueueItem(); }
	public function getQueueItemBackup(): QueueItemBackup { return $this->_queue_item_backup; }
	public function getBackupJob(): BackupJob { return $this->_backup_job; }
	public function getSnapshotDirectory(): string { return $this->getQueueItem()->getWorkspace() . JetBackup::SEP . $this->getQueueItemBackup()->getSnapshotName(); }

	abstract public function execute():void;

	protected function _archiveFiles($source): void {
		
		$archive_file = $this->getSnapshotDirectory() . JetBackup::SEP . Snapshot::SKELETON_FILES_DIRNAME . JetBackup::SEP . Snapshot::SKELETON_FILES_ARCHIVE_NAME;
		$files_list_file = $this->getSnapshotDirectory() . JetBackup::SEP . Snapshot::SKELETON_META_DIRNAME . JetBackup::SEP . Snapshot::SKELETON_FILES_LIST_FILENAME;

		$this->getLogController()->logDebug('[_archiveFiles] Archive file: ' . $archive_file);
		$this->getLogController()->logDebug('[_archiveFiles] Tree file: ' . $files_list_file);

		try {

			$archive = new Archive($archive_file, false, Archive::OPT_SPARSE, 0, $this->getSnapshotDirectory() . JetBackup::SEP . Snapshot::SKELETON_TEMP_DIRNAME);
			$archive->setLogController($this->getLogController());

			$list_fd = fopen($files_list_file, 'a');

			$archive->setCreateFileCallback(function(Header $header) use ($list_fd) {
				fwrite($list_fd, "{$header->getSize(false)} {$header->getMtime(false)} {$header->getFilename()}\n");
			});

			$this->getTask()->scan($source, function(DirIterator $scan, $data) use ($archive, $source) {
				//$this->getLogController()->logDebug('[_archiveFiles] Data: ' . print_r($data, true));
				$this->getLogController()->logDebug('[_archiveFiles] Source: ' .$source);

				if (!$data->total_size) throw new ArchiveException('[_archiveFiles] Invalid total tree size');

				$archive->setAppend(!($data->total_size == $data->current_pos));
				
				if(!$archive->isAppend()) {
					$this->getQueueItem()->getProgress()->setMessage("Archiving...");
					$this->getQueueItem()->save();

					$this->getLogController()->logMessage('Inside Archive Manager First run');
					$this->getLogController()->logMessage('Total tree size: ' . $data->total_size);
					$this->getLogController()->logMessage('Current tree POS: ' . $data->current_pos);
					$this->getLogController()->logMessage('Source: ' . $scan->getSource());
					$this->getLogController()->logMessage('Excludes: ');
					$this->getLogController()->logMessage(print_r($scan->getExcludes(), true));
				}

				try {
					$fd = $archive->getFileFD();
					$current_file = $scan->next($fd ? $fd->tell() : 0);
				} catch (DirIteratorFileVanishedException $e) {
					$this->getLogController()->logMessage('[ WARNING ] File Vanished : ' . $e->getMessage());
					return;
				}

				if($scan->getSource() == $current_file->getName()) return;

				$progress = $this->getQueueItem()->getProgress();
				$progress->setSubMessage("Archiving: {$current_file->getName()}");
				$progress->setTotalSubItems($data->total_size);
				$progress->setCurrentSubItem($data->total_size - $data->current_pos);

				$this->getQueueItem()->save();

				$this->getLogController()->logMessage("[". ($data->total_size - $data->current_pos)."/$data->total_size] Archiving: {$current_file->getName()}");

				try {

					$file = substr($current_file->getName(), strlen($source)+1);
					$archive->appendFileChunked($current_file, $file, function() use ($data) {

						// We should return true end exit later, however I want to try to exit here to see if this makes issues
						$this->getTask()->checkExecutionTime(function() use ($data) {

							$progress = $this->getQueueItem()->getProgress();
							$progress->setSubMessage("Waiting for next cron iteration");
							$progress->setTotalSubItems($data->total_size);
							$progress->setCurrentSubItem($data->total_size - $data->current_pos);
							$this->getQueueItem()->save();
						});

						return false;

					}, Factory::getSettingsPerformance()->getReadChunkSizeBytes());
				} catch( Exception|ArchiveException $e) {
					//this will throw exception if the file has been changed more than 3 times
					$this->getLogController()->logError('[Backup] Error while trying to archive: ' . $e->getMessage());
				}

			}, $this->getBackupJob()->getAllExcludes());

			$archive->save();
			fclose($list_fd);
			$this->getLogController()->logMessage('Archive Done');
			//touch($directory_tree_file_done);

		} catch ( Exception $e) {

			// Handle the exception (e.g., log the error, display a message, etc.)
			Alert::add('Error', 'Error during archive creation:' . $e->getMessage(), Alert::LEVEL_CRITICAL);

			$this->getQueueItem()->updateStatus(Queue::STATUS_FAILED);
			$this->getQueueItem()->updateProgress('Error occurred');

			throw $e;
		}
	}

	protected function _createWorkspace():void {
		$directories = [
			$this->getSnapshotDirectory(),
			'%s' . Snapshot::SKELETON_DATABASE_DIRNAME,
			'%s' . Snapshot::SKELETON_FILES_DIRNAME,
			'%s' . Snapshot::SKELETON_CONFIG_DIRNAME,
			'%s' . Snapshot::SKELETON_TEMP_DIRNAME,
			'%s' . Snapshot::SKELETON_LOG_DIRNAME,
			'%s' . Snapshot::SKELETON_META_DIRNAME,
		];

		foreach($directories as $folder) {
			$folder = sprintf($folder, $this->getSnapshotDirectory() . JetBackup::SEP);
			$this->getLogController()->logDebug("Creating directory: $folder");
			Util::secureFolder($folder);
		}
	}

	protected function _compressFiles() {

		$file_backup_archive = $this->getSnapshotDirectory() . JetBackup::SEP . Snapshot::SKELETON_FILES_DIRNAME . JetBackup::SEP . Snapshot::SKELETON_FILES_ARCHIVE_NAME;

		$this->getLogController()->logMessage('Starting compression for: ' . $file_backup_archive);

		Gzip::compress(
			$file_backup_archive,
			Gzip::DEFAULT_COMPRESS_CHUNK_SIZE,
			Gzip::DEFAULT_COMPRESSION_LEVEL,
			function($byteRead, $totalSize) {

				$progress = $this->getQueueItem()->getProgress();
				$progress->setSubMessage('');
				$progress->setTotalSubItems($totalSize);
				$progress->setCurrentSubItem($byteRead);

				$this->getQueueItem()->save();

				$this->getTask()->checkExecutionTime(function() {
					$this->getQueueItem()->getProgress()->setMessage('[ Gzip ] Waiting for next cron iteration');
					$this->getQueueItem()->save();
				});
			}
		);

		$this->getLogController()->logMessage('GZIP Compression done!');
	}

	abstract protected function getSnapshotItems():array;

	/**
	 * @throws \JetBackup\Exception\IOException
	 * @throws JBException
	 */
	private function _createSnapshot():Snapshot {

		$this->getLogController()->logDebug("[_createSnapshot] Creating snapshot");
		$multisite = [];
		foreach(Wordpress::getMultisiteBlogs() as $blog) $multisite[] = $blog->getData();

		$snapshot = new Snapshot();
		$snapshot->setCreated(time());
		$snapshot->setName($this->getQueueItemBackup()->getSnapshotName());
		$snapshot->setBackupType($this->getBackupJob()->getType());
		$snapshot->setContains($this->getBackupJob()->getContains());
		$snapshot->setStructure(Factory::getSettingsPerformance()->isGzipCompressArchive() ? BackupJob::STRUCTURE_COMPRESSED : BackupJob::STRUCTURE_ARCHIVED);
		$snapshot->setJobIdentifier($this->getBackupJob()->getIdentifier());
		$snapshot->setDeleted(0);
		$snapshot->setLocked(false);
      	$snapshot->setEngine(Engine::ENGINE_WP);

		$snapshot->addParam(Snapshot::PARAM_MULTISITE, $multisite);
		$snapshot->addParam(Snapshot::PARAM_SITE_URL, Wordpress::getSiteURL());
		$snapshot->addParam(Snapshot::PARAM_DB_PREFIX, Wordpress::getDB()->getPrefix());

		$size = 0;
		$items = $this->getSnapshotItems();
		foreach ($items as $item) $size += $item->getSize();
		
		$snapshot->setItems($items);
		$snapshot->setSize($size);


		$schedules = $this->getBackupJob()->getRunningSchedules($this->getQueueItem()->getStarted());
		foreach ($schedules as $schedule) $snapshot->addScheduleByType($schedule->getType());
		if($this->getQueueItemBackup()->isManually()) $snapshot->addScheduleByType(Schedule::TYPE_MANUALLY);
		if($this->getQueueItemBackup()->isAfterJobDone()) $snapshot->addScheduleByType(Schedule::TYPE_AFTER_JOB_DONE);

		return $snapshot;
	}

	/**
	 * @throws IOVanishedException
	 * @throws DestinationException
	 * @throws BackupException
	 * @throws IOException
	 * @throws \JetBackup\Exception\IOException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	protected function _transferToDestination() {

		$this->getTask()->foreachCallable(function() {
			$destinations = [];

			// Move local destination to be last
			foreach($this->getQueueItemBackup()->getDestinations() as $destination_id) {
				$destination = new Destination($destination_id);
				if(!$destination->getId()) throw new BackupException("Invalid destination {$destination->getId()}");
				if($destination->getType() == Local::TYPE) $destinations[] = $destination_id;
				else array_unshift($destinations, $destination_id);
			}

			return $destinations;

		}, [], function($key, $destination_id) {

			$destination = new Destination($destination_id);
			$destination->setLogController($this->getLogController());

			$this->getLogController()->logMessage("Uploading backup to destination \"{$destination->getName()}\" (id: {$destination->getId()})");

			$progress = $this->getQueueItem()->getProgress();
			$progress->setSubMessage('Transferring to destination "' . $destination->getName() . '"');
			$this->getQueueItem()->save();

			// Create the snapshot object and dump it to the snapshot folder for upload
			// Don't save this object yet, Only after upload is done
			$snapshot = $this->_createSnapshot();
			$snapshot->setDestinationId($destination->getId());

			$this->getTask()->func(function() use ($snapshot, $destination, $progress) {

				// if needed, add more snapshot details above this line
				$snapshot->exportMeta($this->getSnapshotDirectory());

				if($destination->getType() == Local::TYPE) {

					$source = $this->getSnapshotDirectory();
					$target = rtrim($destination->getInstance()->getPath(), JetBackup::SEP) . JetBackup::SEP . $this->getBackupJob()->getIdentifier() . JetBackup::SEP . $this->getQueueItemBackup()->getSnapshotName();

					if (!file_exists(dirname($target))) {
						if (!mkdir(dirname($target), 0700, true)) {
							throw new BackupException("Failed to create directory: $target");
						}
					}

					// We don't need the real size, the `rename` will be very fast
					$progress->setTotalSubItems(1);
					$progress->setCurrentSubItem(0);
					$this->getQueueItem()->save();

					$this->getLogController()->logMessage("Moving snap data to local location: $source -> $target");
					if (!rename($source, $target)) {
						throw new BackupException("Failed to move $source -> $target");
					}

					$progress->setCurrentSubItem(1);
					$this->getQueueItem()->save();

				} else {

					(new Tree($destination, $this->getQueueItem(), $this->getSnapshotDirectory()))->process(function($file) use ($destination) {

						$this->getTask()->checkExecutionTime();

						$source = $this->getSnapshotDirectory() . $file;
						$target = $this->getBackupJob()->getIdentifier() . JetBackup::SEP . $this->getQueueItemBackup()->getSnapshotName() . $file;

						if (is_dir($source)) {
							$this->getLogController()->logMessage("Creating folder $target");
							$destination->createDir($target);
							return;
						}

						$destination->copyFileToRemote($source, $target, $this->getQueueItem(), $this->getTask());
					});
				}

				$progress->setTotalSubItems(0);
				$progress->setCurrentSubItem(0);
				$this->getQueueItem()->save();

			}, [], '_uploadMetaDestination' . $destination->getId());

			$this->getTask()->func(function() use ($destination) {

				$this->getLogController()->logMessage('Uploading log file to destination id ' . $destination->getId());
				$target = $this->getBackupJob()->getIdentifier() . JetBackup::SEP . $this->getQueueItemBackup()->getSnapshotName() . JetBackup::SEP . Snapshot::SKELETON_LOG_DIRNAME;
				$destination->createDir($target);

				$logfile = $this->getTask()->getLogFile();
				$logfile_tmp = $logfile .'_tmp';
				// We cannot upload the original log since we continue to write to it during upload
				// Some remote destination are doing hash calculations and will return error because of size mismatch
				$this->getLogController()->logMessage('Preparing log file for upload');
				$this->getLogController()->logMessage('### Data after this line will not be updated in the snapshot log file ###');
				Util::cp($logfile, $logfile_tmp, 0400);

				Gzip::compress(
					$logfile_tmp,
					Gzip::DEFAULT_COMPRESS_CHUNK_SIZE,
					Gzip::DEFAULT_COMPRESSION_LEVEL,
					function ($byteRead, $totalSize) {

						$progress = $this->getTask()->getQueueItem()->getProgress();
						$progress->setSubMessage('');
						$progress->setTotalSubItems($totalSize);
						$progress->setCurrentSubItem($byteRead);

						$this->getTask()->getQueueItem()->save();

						$this->getTask()->checkExecutionTime(function () {
							$this->getTask()->getQueueItem()->getProgress()->setMessage('[ Gzip ] Waiting for next cron iteration');
							$this->getTask()->getQueueItem()->save();
						});
					}
				);

				$logfile_tmp = $logfile_tmp . '.gz';
				$destination->copyFileToRemote($logfile_tmp, $target . JetBackup::SEP . Snapshot::SKELETON_LOG_FILENAME, $this->getQueueItem(), $this->getTask());
				$this->getLogController()->logDebug("Temporary log file uploaded [$logfile_tmp]");
				unlink($logfile_tmp);
				$this->getLogController()->logDebug("Temporary log file deleted [$logfile_tmp]");

			}, [], '_uploadLogDestination' . $destination->getId());

			// after upload is done, save the snapshot object each destination
			$snapshot->save();

		}, '_transferToAllDestinations');

		$this->getLogController()->logMessage('Sending backup to all destinations is complete');
		$this->getLogController()->logMessage('Removing temp folder ' . $this->getSnapshotDirectory());

		Util::rm($this->getSnapshotDirectory());
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws ScheduleException
	 */
	protected function _calculateAfterJobDone () {

		$schedule_details = Schedule::query()
			->select([JetBackup::ID_FIELD])
			->where([Schedule::BACKUP_ID, '=', $this->getBackupJob()->getId()])
			->getQuery()
			->first();

		if(!$schedule_details) return;
		
		$schedule_id = $schedule_details[JetBackup::ID_FIELD];

		$list = BackupJob::query()
			->select([JetBackup::ID_FIELD])
			->getQuery()
			->fetch();

		foreach($list as $config_details) {
			$backup_config = new BackupJob($config_details[JetBackup::ID_FIELD]);

			if(!($schedule = $backup_config->getScheduleById($schedule_id))) continue;

			$schedule->setNextRun(time());
			$backup_config->updateSchedule($schedule);
			$backup_config->save();
		}
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws JBException
	 */
	protected function _retentionCleanup() {
		$this->getLogController()->logMessage('Marking unneeded snapshots for delete');

		$addToQueue = false;

		foreach($this->getBackupJob()->getSchedules() as $schedule) {

			$snapshots = Snapshot::query()
				->where([Snapshot::DESTINATION_ID, 'in', $this->getQueueItemBackup()->getDestinations()])
				->where([Snapshot::JOB_IDENTIFIER, '=', $this->getBackupJob()->getIdentifier()])
				->where([Snapshot::SCHEDULES, 'contains', $schedule->getType()])
				->where([Snapshot::DELETED, '=', 0])
				->orderBy([Snapshot::NAME => 'desc'])
				->skip($schedule->getRetain()) // skip the needed snapshots
				->getQuery()
				->fetch();

			foreach($snapshots as $snapshot_details) {

				$snapshot = new Snapshot($snapshot_details[JetBackup::ID_FIELD]);
				$this->getLogController()->logDebug('Marked snap ' . $snapshot->getName() . ' for delete, Destination ' . $snapshot->getDestinationName() . '[ ID ' . $snapshot->getDestinationId() . ']' );

				$snapshot->removeSchedule($schedule->getType());

				// if there is no more schedules assigned for this snapshot we need to delete it
				if(!sizeof($snapshot->getSchedules())) {
					$snapshot->setDeleted(time());
					$addToQueue = true;
				}

				$snapshot->save();
			}
		}

		// There is nothing to delete, don't add cleanup to queue
		if(!$addToQueue) return;

		$this->getLogController()->logMessage('Adding retention cleanup to queue');

		try {
			$queue_item = QueueItem::prepare();
			$queue_item->setType(Queue::QUEUE_TYPE_RETENTION_CLEANUP);

			Queue::addToQueue($queue_item);
		} catch (QueueException $e) {
			$this->getLogController()->logMessage('[Backup] Adding retention cleanup failed: ' . $e->getMessage());
			// just logging without breaking
		}

	}
}