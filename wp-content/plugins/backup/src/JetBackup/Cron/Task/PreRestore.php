<?php

namespace JetBackup\Cron\Task;

use Exception;
use JetBackup\Archive\Archive;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Data\Engine;
use JetBackup\Destination\Destination;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DownloaderException;
use JetBackup\Exception\ExecutionTimeException;
use JetBackup\Exception\RestoreException;
use JetBackup\Exception\SGBExtractorException;
use JetBackup\Exception\SnapshotMetaException;
use JetBackup\Exception\TaskException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\License\License;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemRestore;
use JetBackup\ResumableTask\ResumableTask;
use JetBackup\SGB\Extractor;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Snapshot\SnapshotDownload;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class PreRestore extends Task {

	const LOG_FILENAME = 'pre_restore';

	const RESTORE_FILE_NAME = 'jetbackup.restore';

	private QueueItemRestore $_queue_item_restore;
	public ?Snapshot $_snapshot=null;

	public function __construct() {
		parent::__construct(self::LOG_FILENAME);
	}

	/**
	 * @return void
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws TaskException
	 */
	public function execute():void {
		parent::execute();

		if($this->getQueueItem()->getStatus() >= Queue::STATUS_RESTORE_WAITING_FOR_RESTORE) {

			// If external restore hasn't initiated within 24 hours we need to abort the restore process
			if($this->getQueueItem()->getStatusTime() < (time() - (60 * 60 * 24))) {
				$this->getLogController()->logError("External restore hasn't initiated within the last 24 hours, Aborting the restore");
				$this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
				$this->getQueueItem()->updateProgress('Restore Aborted!', QueueItem::PROGRESS_LAST_STEP);
			} else {
				$this->getLogController()->logMessage('Waiting for external restore');
			}

			return;
		}

		$this->_queue_item_restore = $this->getQueueItem()->getItemData();

		if($this->_queue_item_restore->getSnapshotId()) {

			$snapshot = new Snapshot($this->_queue_item_restore->getSnapshotId());
			$destination = new Destination($snapshot->getDestinationId());

			if(!License::isValid() &&
				!in_array($destination->getType(), Destination::LICENSE_EXCLUDED) &&
				$snapshot->getEngine() != Engine::ENGINE_JB) {

				$this->getLogController()->logError("You can't restore from {$destination->getType()} destination without a license");
				$this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
				$this->getQueueItem()->updateProgress('Restore Aborted!', QueueItem::PROGRESS_LAST_STEP);
				return;
			}
		}

		if($this->getQueueItem()->getStatus() == Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage("Starting restore");

			$this->getQueueItem()->getProgress()->setTotalItems( count(Queue::STATUS_PRE_RESTORE_NAMES) + 3);
			$this->getQueueItem()->save();

			$this->getQueueItem()->updateProgress('Starting restore');
		} else if($this->getQueueItem()->getStatus() > Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Resumed Restore');
		}

		try {

			if(!$this->_queue_item_restore->getSnapshotId() && !$this->_queue_item_restore->getSnapshotPath())
				throw new RestoreException("No snapshot id or path provided");
			$this->getLogController()->logDebug('Item data: ' . print_r($this->_queue_item_restore, 1));
			$this->_snapshot = $this->func([$this, '_download']);
			$this->func([$this, '_extract']);
			$this->func([$this, '_build_url']);

			if($this->getQueueItem()->getStatus() < Queue::STATUS_DONE) $this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_WAITING_FOR_RESTORE);
			$this->getLogController()->logMessage('Completed!');
		} catch(Exception $e) {
			$this->getQueueItem()->updateStatus(Queue::STATUS_FAILED);
			$this->getLogController()->logError($e->getMessage());
			$this->getLogController()->logMessage('Failed!');
        }

		$this->getQueueItem()->updateProgress($this->getQueueItem()->getStatus() == Queue::STATUS_RESTORE_WAITING_FOR_RESTORE ? 'Pre Restore Completed!' : 'Pre Restore Failed!', QueueItem::PROGRESS_LAST_STEP);
		$this->getLogController()->logMessage('Total time: ' . $this->getExecutionTimeElapsed());
	}

	public static function findPublicRestoreFiles() : array {
		$basePath = Factory::getWPHelper()->getRestoreFileLocation() . JetBackup::SEP . self::RESTORE_FILE_NAME . '.*';
		if (defined('GLOB_BRACE'))  return glob($basePath . '.{php,php.lock}', \GLOB_BRACE);
		$phpFiles = glob($basePath . '.php');
		$phpLockFiles = glob($basePath . '.php.lock');
		return array_merge($phpFiles ?: [], $phpLockFiles ?: []);
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws RestoreException
	 */
	public function _build_url() {

		$this->getLogController()->logMessage('[ _build_url ]');
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_BUILD_URL);
		$this->getQueueItem()->updateProgress('Building restore URL');

		$template_file = JetBackup::SRC_PATH . JetBackup::SEP . 'Restore' . JetBackup::SEP . 'restore.template.php';
		if(!file_exists($template_file)) throw new RestoreException("Restore template file not found");

		$public_dir = rtrim(Factory::getWPHelper()->getWordPressHomedir(), JetBackup::SEP);
		foreach (self::findPublicRestoreFiles() as $file) @unlink($file);

		$content = "<?php define('__JETBACKUP_RESTORE__', true); ?>\n";
		$content .= "<?php define('WP_ROOT', '$public_dir'); ?>\n";
		$content .= "<?php define('PUBLIC_PATH', '" . (Factory::getSettingsRestore()->isRestoreAlternatePathEnabled() ? str_repeat('../', substr_count(JetBackup::CRON_PUBLIC_URL, '/')) : '') . "'); ?>\n";
		$content .= file_get_contents($template_file);

		$restore_file_name =  self::RESTORE_FILE_NAME . '.'  . Util::generateRandomString(24) . '.php';
		$restore_file = Factory::getWPHelper()->getRestoreFileLocation() . JetBackup::SEP . $restore_file_name;

		if(!file_put_contents($restore_file, $content)) throw new RestoreException("Failed creating restore file $restore_file");

		// Dev Remark
		//symlink('wp-content/plugins/backup/src/JetBackup/Restore/restore.template.php', $restore_file);

		$alternate_path = Factory::getSettingsRestore()->isRestoreAlternatePathEnabled() ? JetBackup::CRON_PUBLIC_URL : '';

		$url = Wordpress::getSiteURL() . $alternate_path . '/' . $restore_file_name . '?id=' . $this->getQueueItem()->getUniqueId();

		$this->getLogController()->logMessage('Restore URL: ' . $url);

		$this->_queue_item_restore->setRestoreURL($url);
		$this->getQueueItem()->save();
	}

	/**
	 * @return void
	 * @throws DBException
	 * @throws DownloaderException
	 * @throws ExecutionTimeException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function _extract() {

		if($this->_snapshot->getEngine() == Engine::ENGINE_JB) return;

		$this->getLogController()->logMessage('[ _extract ]');
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_EXTRACT);
		$this->getQueueItem()->updateProgress('Extracting backup items');

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

			$this->func(function () {
				$this->_snapshot->extract($this->getQueueItem()->getWorkspace(), $this->getLogController(), function(string $type, string $action, int $total, int  $read) {

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

				}, $this->_queue_item_restore->getExcludes(), $this->_queue_item_restore->getIncludes());
			}, [], 'snapshot_extract');

			// done extract, reset sub process bar
			$this->getQueueItem()->getProgress()->resetSub();
			$this->getQueueItem()->save();

		}
	}

	/**
	 * @return void
	 * @throws RestoreException
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function _download():Snapshot {

		$this->getLogController()->logMessage('[ _download ]');
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_DOWNLOAD);
		if($this->_queue_item_restore->getSnapshotId()) {
			$this->getLogController()->logMessage('[ _download ] Downloading backup');
			$this->getQueueItem()->updateProgress('Downloading backup');

			$snapshot = new Snapshot($this->_queue_item_restore->getSnapshotId());

			$this->getLogController()->logDebug('[ _download ] getOptions bitwise value: ' . $this->_queue_item_restore->getOptions());
			$this->getLogController()->logDebug('[ _download ] getOptions decimal value: ' . decbin($this->_queue_item_restore->getOptions()));

			if($snapshot->getEngine() == Engine::ENGINE_JB) return $snapshot;

			$items_excluded = [];

			if (($this->_queue_item_restore->getOptions() & QueueItemRestore::OPTION_RESTORE_FILES_SKIP)) {
				$this->getLogController()->logDebug('[ _download ] Skipping Homedir');
				$items_excluded[] = BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR;
			}

			if (($this->_queue_item_restore->getOptions() & QueueItemRestore::OPTION_RESTORE_DATABASE_SKIP)) {
				$this->getLogController()->logDebug('[ _download ] Skipping Database');
				$items_excluded[] = BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE;
			}

			$this->getLogController()->logDebug('[ _download ] Excluded items: ' . print_r($items_excluded, true));

			$download = new SnapshotDownload($snapshot, $this->getQueueItem()->getWorkspace());
			$download->setLogController($this->getLogController());
			$download->setQueueItem($this->getQueueItem());
			$download->setTask($this);
			$download->setExcludedItems($items_excluded);
			$download->setExcludedDatabases($this->_queue_item_restore->getExcludedDatabases());
			$download->setIncludedDatabases($this->_queue_item_restore->getIncludedDatabases());
			$download->downloadAll();

			// done downloading, reset sub process bar
			$this->getQueueItem()->getProgress()->resetSub();
			$this->getQueueItem()->save();

		} elseif($this->_queue_item_restore->getSnapshotPath()) {
			$this->getLogController()->logMessage('[ _download ] Extracting backup from path');
			$this->getQueueItem()->updateProgress('Extracting backup from path');
			$snapshot = $this->_extractBackupFile();
		}

		return $snapshot;
	}

	/**
	 * @throws RestoreException
	 * @throws Exception
	 */
	private function _extractBackupFile():Snapshot {
		$path = $this->_queue_item_restore->getSnapshotPath();
		if(!file_exists($path)) throw new RestoreException("The provided backup path doesn't exists");

		if(Archive::isGzCompressed($path)) {
			$this->getLogController()->logMessage('[ _extractBackupFile ] Decompressing GZIP');
			$this->func(['\JetBackup\Archive\Gzip', 'decompress'], [$path, ResumableTask::PARAMS_EXECUTION_TIME]);
			$path = substr($path, 0, -3); // remove .gz from name
		}

		if(!Archive::isTar($path)) throw new RestoreException("Invalid backup file provided, Should be tar.gz/tar.gz file");

		$this->func(function($path) {
			$archive = new Archive($path);
			$archive->setExtractFileCallback(function($type,$action,$total,$read) {
				$progress = $this->getQueueItem()->getProgress();
				$progress->setMessage($type);
				$progress->setSubMessage($action);
				$progress->setTotalSubItems($total);
				$progress->setCurrentSubItem($read);
				$this->getQueueItem()->save();

				$this->checkExecutionTime(function () use ($type, $action, $total, $read) {
					$progress = $this->getQueueItem()->getProgress();
					$progress->setMessage($type);
					$progress->setSubMessage('Waiting for next cron');
					$progress->setTotalSubItems($total);
					$progress->setCurrentSubItem($read);
					$this->getQueueItem()->save();
				});

			});
			$archive->setExcludeCallback(function($path, $is_dir) {
				$excludes = $this->_queue_item_restore->getExcludes();
				foreach($excludes as $exclude) if(fnmatch($exclude, $path) || ($is_dir && str_ends_with($exclude, '/') && fnmatch(substr($exclude, 0, -1), $path))) return true;
				return false;
			});
			$archive->setLogController($this->getLogController());
			$archive->extract($this->getQueueItem()->getWorkspace());
			unlink($path);
		}, [$path], 'extract');

		// done extract, reset sub process bar
		$this->getQueueItem()->getProgress()->resetSub();
		$this->getQueueItem()->save();

		$meta_file = sprintf(Snapshot::META_FILEPATH, $this->getQueueItem()->getWorkspace());
		if(!file_exists($meta_file)) throw new RestoreException("The provided backup doesn't contains meta file");

		$this->getLogController()->logDebug("[ _extractBackupFile ] Importing meta file $meta_file");
		$snapshot = new Snapshot();


		try {
			$snapshot->importMeta($meta_file, true);
		} catch(SnapshotMetaException $e) {
			throw new RestoreException("Can't use the provided backup file. Error: " . $e->getMessage());
		}

		return $snapshot;
	}
}