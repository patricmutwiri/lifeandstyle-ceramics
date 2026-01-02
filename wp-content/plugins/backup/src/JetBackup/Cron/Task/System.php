<?php

namespace JetBackup\Cron\Task;

use Exception;
use JetBackup\Alert\Alert;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Download\Download;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\Exception\LicenseException;
use JetBackup\Exception\NotificationException;
use JetBackup\Exception\QueueException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\JetBackupLinux\JetBackupLinux;
use JetBackup\License\License;
use JetBackup\Notification\Notification;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemSystem;
use JetBackup\Upload\Upload;
use JetBackup\Web\JetHttp;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class System extends Task {

	const LOG_FILENAME = 'system';

	const CHECKSUM_URL = "https://api.wordpress.org/core/checksums/1.0/?version=%s&locale=%s";

	const TYPE_HOURLY   = 1;
	const TYPE_DAILY    = 2;

	const TYPE_NAMES    = [
		self::TYPE_HOURLY   => 'Hourly',		
		self::TYPE_DAILY    => 'Daily',		
	];

	const HOURLY_INTERVAL = 14400; // 4 hours
	const DAILY_INTERVAL = 86400; // 24 hours
	
	const TEMP_FILES_TTL = 86400;
	const VALIDATE_CHECKSUMS_EXCLUDES = [ 'wp-content/themes/*', 'wp-content/plugins/*' ];
	private QueueItemSystem $_queue_item_system;

	public function __construct() {
		parent::__construct(self::LOG_FILENAME);
	}

	public function execute():void {
		parent::execute();

		$this->_queue_item_system = $this->getQueueItem()->getItemData();

		if($this->getQueueItem()->getStatus() == Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Starting ' . self::TYPE_NAMES[$this->_queue_item_system->getType()] . ' System Tasks');

			$this->getQueueItem()->getProgress()->setTotalItems($this->_queue_item_system->getType() == self::TYPE_DAILY ? 9 : 6);
			$this->getQueueItem()->save();

			$this->getQueueItem()->updateProgress('Starting ' . self::TYPE_NAMES[$this->_queue_item_system->getType()] . ' System Tasks');
		} else if($this->getQueueItem()->getStatus() > Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Resumed ' . self::TYPE_NAMES[$this->_queue_item_system->getType()] . ' System Tasks');
		}

		try {
			switch($this->_queue_item_system->getType()) {
				case self::TYPE_DAILY:
					$this->func([$this, '_checkLicense']);
					$this->func([$this, '_backupJobMonitor']);
					$this->func([$this, '_databaseCleanup']);
					$this->func([$this, '_retentionCleanup']);
					$this->func([$this, '_uploadCleanup']);
					$this->func([$this, '_systemCleanup']);
					$this->func([$this, '_logsCleanup']);
					$this->func([$this, '_validateChecksums']);
					$this->func([$this, '_processDailyAlerts']);
					$this->func([$this, '_indexJBBackups']);
				break;

				case self::TYPE_HOURLY:
					$this->func([$this, '_checkLicense']);
					$this->func([$this, '_databaseCleanup']);
					$this->func([$this, '_retentionCleanup']);
					$this->func([$this, '_downloadsCleanup']);
					$this->func([$this, '_indexJBBackups']);
				break;
			}

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
				? 'System Tasks Completed!'
				: ($this->getQueueItem()->getStatus() == Queue::STATUS_PARTIALLY
				? 'Completed with errors (see logs)'
				: 'System Tasks Failed!'),
			QueueItem::PROGRESS_LAST_STEP
		);

		$this->getLogController()->logMessage('Total time: ' . $this->getExecutionTimeElapsed());
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public static function addToQueue() {

		$system = new QueueItemSystem();

		$queue_item = QueueItem::prepare();
		$queue_item->setType(Queue::QUEUE_TYPE_SYSTEM);
		$daily_last_run = Factory::getConfig()->getSystemCronDailyLastRun();
		$hourly_last_run = Factory::getConfig()->getSystemCronHourlyLastRun();

		// Check if we need to run daily tasks
		if(!$daily_last_run || $daily_last_run < (time() - self::DAILY_INTERVAL)) {
			$system->setType(self::TYPE_DAILY);
			Factory::getConfig()->setSystemCronDailyLastRun();
		}
		// Only if we don't need to run daily tasks, check if we need to run hourly tasks
		elseif (!$hourly_last_run || $hourly_last_run < (time() - self::HOURLY_INTERVAL)) {
			$system->setType(self::TYPE_HOURLY);
		} else return;
		Factory::getConfig()->setSystemCronHourlyLastRun();

		$queue_item->setItemData($system);
		Queue::addToQueue($queue_item);

		Factory::getConfig()->save();
	}

	public function _uploadCleanup() {
		$this->getLogController()->logMessage("\t [_retentionCleanup]");
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_SYSTEM_UPLOAD_CLEANUP);
		$this->getQueueItem()->updateProgress('Uploads Cleanup');

		$list = Upload::query()
	         ->where([Upload::CREATED, '<', time() - (60 * 60 * 24)])
	         ->getQuery()
	         ->fetch();

		foreach($list as $upload_details) {
			$upload = new Upload($upload_details[JetBackup::ID_FIELD]);
			if(!$upload->getId()) continue;
			
			$location = dirname($upload->getFileLocation());
			
			if($location && is_dir($location)) Util::rm($location);
			$upload->delete();
		}
	}
	
	/**
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function _databaseCleanup() {

		$this->getLogController()->logMessage("\t [_databaseCleanup]");
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());
		$this->getQueueItem()->updateStatus(Queue::STATUS_SYSTEM_DB_CLEANUP);
		$this->getQueueItem()->updateProgress('Database Cleanup');

		if($ttl = Factory::getSettingsMaintenance()->getQueueItemsTTL()) {
			QueueItem::query()
				->where([QueueItem::STATUS, '>=', Queue::STATUS_DONE])
				->where([QueueItem::CREATED, '<', (time() - ($ttl * 3600))])
				->getQuery()
				->delete();
		}

		if($ttl = Factory::getSettingsMaintenance()->getAlertsTTL()) {
			Alert::query()
				->where([Alert::CREATED, '<', (time() - ($ttl * 3600))])
				->getQuery()
				->delete();
		}
	}

	/**
	 * @throws IOException
	 * @throws \Exception
	 */
	public function _systemCleanup() {
		if($this->_queue_item_system->getType() != self::TYPE_DAILY) return;
		$this->getLogController()->logMessage("\t [_systemCleanup]");
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_SYSTEM_SYSTEM_CLEANUP);
		$this->getQueueItem()->updateProgress('System Cleanup');

		$tmp_dir = Factory::getLocations()->getTempDir();
		$dir = dir($tmp_dir);
		$clearedFolders = 0;

		while(($file = $dir->read()) !== false) {
			$filepath = $tmp_dir . JetBackup::SEP . $file;

			$this->getLogController()->logDebug("\t File: {$filepath}, mtime: " . filemtime($filepath) . ", TTL Threshold: " . (time() - self::TEMP_FILES_TTL));

			if($file == '.' ||
				$file == '..' ||
				!is_dir($filepath) ||
			   (filemtime($filepath) > (time() - self::TEMP_FILES_TTL))
			) {
				$this->getLogController()->logDebug("\t Skipping: {$filepath}");
				continue;
			}
			$this->getLogController()->logDebug("\t Removing: {$filepath}");
			Util::rm($filepath);
			$clearedFolders++;
		}

		$dir->close();
		if($clearedFolders > 0) Alert::add('System Cleanup', "Removed $clearedFolders temporary folders, refer to system logs for more details", Alert::LEVEL_INFORMATION);

		$this->getLogController()->logMessage("Searching for old public restore files...");
		// Check if there are public restore leftovers file is older than 24 hours
		foreach (PreRestore::findPublicRestoreFiles() as $file) {
			if (filemtime($file) < (time() - self::TEMP_FILES_TTL)) { //24 hours
				$this->getLogController()->logMessage("Removing $file");
				@unlink($file);
			}
		}



		$this->getLogController()->logMessage("Clearing generated support users if exists");
		Helper::clearSupportUser();

	}

	/**
	 * @return void
	 */
	public function _processDailyAlerts() : void {

		if($this->_queue_item_system->getType() != self::TYPE_DAILY) return;
		$this->getLogController()->logMessage("\t [_processDailyAlerts]");
		$this->getLogController()->logMessage("\t Execution time: {$this->getExecutionTimeElapsed()}");
		$this->getLogController()->logMessage("\t TTL time: {$this->getExecutionTimeLimit()}");

		$this->getQueueItem()->updateStatus(Queue::STATUS_SYSTEM_DAILY_ALERTS);
		$this->getQueueItem()->updateProgress(Queue::STATUS_SYSTEM_NAMES[Queue::STATUS_SYSTEM_DAILY_ALERTS]);

		try {
			Alert::processDailyAlerts();
		} catch (Exception $e) {
			$this->getLogController()->logMessage('[_processDailyAlerts] Error : ' . $e->getMessage());
			// just logging without breaking
		}

	}

	/**
	 * @return void
	 * @throws HttpRequestException
	 * @throws NotificationException
	 */
	public function _validateChecksums() {

		if(
			$this->_queue_item_system->getType() != self::TYPE_DAILY ||
			!Factory::getSettingsSecurity()->isValidateChecksumsEnabled()
		) return;

		$this->getLogController()->logMessage("\t [_validateChecksums]");
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_SYSTEM_VALIDATE_CHECKSUMS);
		$this->getQueueItem()->updateProgress('Validate System Checksums');

		$response = JetHttp::request()
			->setReturnTransfer()
			->setTimeout(5)
			->setFollowLocation()
		    ->exec(sprintf(self::CHECKSUM_URL, Wordpress::getVersion(), Wordpress::getLocale()));

		if($response->getHeaders()->getCode() != 200 || !($body = $response->getBody())) return;

		$files = [];
		$homedir = Factory::getWPHelper()->getWordPressHomedir();
		$body = json_decode($body);

		foreach ($body->checksums as $file => $checksum) {

			foreach (self::VALIDATE_CHECKSUMS_EXCLUDES as $pattern) {
				if (fnmatch($pattern, $file)) continue 2;
			}

			$local_file = $homedir . $file;
			if(!file_exists($local_file)) continue;

			$local_checksum = md5_file($local_file);

			if ($local_checksum !== $checksum) {
				$files[] = [
					'file'              => $local_file,
					'api_checksum'      => $checksum,
					'local_checksum'    => $local_checksum,
				];
			}
		}


		if(!sizeof($files)) return;
		$this->getLogController()->logDebug("\t [_validateChecksums] Found files: " . print_r($files, true));
		Notification::message()
			->addParam('backup_domain', Wordpress::getSiteDomain())
			->addParam('checksums', $files)
			->send('Checksum Verification Failed', 'checksum_alert');
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function _retentionCleanup() {

		try {
			$this->getLogController()->logMessage("\t [_retentionCleanup]");
			$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
			$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

			$this->getQueueItem()->updateProgress('Adding retention cleanup to queue');

			$queue_item = QueueItem::prepare();
			$queue_item->setType(Queue::QUEUE_TYPE_RETENTION_CLEANUP);
			Queue::addToQueue($queue_item);
		} catch (QueueException $e) {
			$this->getLogController()->logMessage('[System] Adding retention cleanup failed: ' . $e->getMessage());
			// just logging without breaking
		}


	}

	/**
	 * @return void
	 * @throws DBException
	 */
	public function _downloadsCleanup() {
		try {
			$this->getLogController()->logMessage("\t [_downloadsCleanup]");
			$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
			$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());
			$this->getQueueItem()->updateProgress('Cleaning download folder');

			if (($ttl = Factory::getSettingsMaintenance()->getDownloadItemsTTL() * 3600) === 0) return;
			Download::deleteByTTL($ttl);

		} catch ( \SleekDB\Exceptions\IOException | InvalidArgumentException $e) {
			$this->getLogController()->logMessage('[System] Cleaning download  failed: ' . $e->getMessage());
			// just logging without breaking
		}
	}

	public function _logsCleanup() {
		if($this->_queue_item_system->getType() != self::TYPE_DAILY) return;
		$this->getLogController()->logMessage("\t [_logsCleanup]");
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_SYSTEM_LOGS_CLEANUP);
		$this->getQueueItem()->updateProgress('Logs Cleanup');

		$log_dir = Factory::getLocations()->getLogsDir();
		$dir = dir($log_dir);

		$all_logs = [];
		
		while(($file = $dir->read()) !== false) {
			if($file == '.' || $file == '..' || !preg_match("/_([^_]+)\.log$/", $file, $matches)) continue;
			$filepath = $log_dir . JetBackup::SEP . $file;
			$all_logs[$matches[1]][filemtime($filepath)] = $filepath;
		}
		
		$dir->close();
		
		$keep_logs = Factory::getSettingsLogging()->getLogRotate();
		
		foreach ($all_logs as $logs) {
			if(sizeof($logs) <= $keep_logs) continue;
			krsort($logs);
			
			$skip = $keep_logs;

			foreach ($logs as $log) {
				if($skip) {
					$skip--;
					continue;
				}
				
				unlink($log);
			}
		}
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws NotificationException
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function _backupJobMonitor() {
		if($this->_queue_item_system->getType() != self::TYPE_DAILY) return;
		$this->getLogController()->logMessage("\t [_backupJobMonitor]");
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_SYSTEM_JOB_MONITOR);
		$this->getQueueItem()->updateProgress('Backup Jobs Monitor');


		$backups = BackupJob::query()
			->getQuery()
			->fetch();
		
		foreach($backups as $backup_details) {
			$backup = new BackupJob( $backup_details[ JetBackup::ID_FIELD]);

			if(
				// skip if job never executed
				!$backup->getLastRun() || 
				// skip if monitor isn't set for this job
				!$backup->getMonitor() ||
				// skip if job was executed properly
				($backup->getLastRun() >= (time() - ($backup->getMonitor() * 86400)))
			) continue;

			// send notification
			Notification::message()
				->addParam('backup_domain', Wordpress::getSiteDomain())
				->addParam('job_name', $backup->getName())
				->addParam('job_monitor', $backup->getMonitor())
				->addParam('backup_date', Util::date('Y-m-d H:i:s', $backup->getLastRun()))
				->send('JetBackup Status Update', 'job_monitor');
			
		}
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	public function _checkLicense() {
		$this->getLogController()->logMessage("\t [_checkLicense]");
		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());
		$this->getQueueItem()->updateProgress('Checking license');
		$settings = Factory::getConfig();
		if(time() < $settings->getLicenseNextCheck()) return;
		$settings->setLicenseNextCheck(time() + License::LOCALKEY_FAIL_INTERVAL);
		$settings->save();

		try {
			License::retrieveLocalKey();
			$this->getLogController()->logMessage("\t [_checkLicense] License Key Valid");
		} catch(LicenseException $e) {
			$error = "Failed retrieving license Local Key. Error: " . $e->getMessage();
			$this->getLogController()->logError($error);
			Alert::add("License check failed", $error, Alert::LEVEL_CRITICAL);
		}
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws DBException
	 */
	public function _indexJBBackups() {

		$settings = Factory::getSettingsGeneral();
		
		if(!$settings->isJBIntegrationEnabled() || !JetBackupLinux::isInstalled()) {
			JetBackupLinux::deleteSnapshots();
			return;
		}

		try {
			$this->getLogController()->logMessage("\t [_indexJBBackups]");
			$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
			$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());
			$this->getQueueItem()->updateProgress('Indexing JB Linux');
			JetBackupLinux::checkRequirements();
		} catch (JetBackupLinuxException $e) {
			$settings->setJBIntegrationEnabled(false);
			$settings->save();
			Alert::add("JetBackup Linux integration has been disabled", "JetBackup linux integration has been disabled due to the following error: " . $e->getMessage() . ". After fixing this issue you will need to manually re-enabled it from the general settings page.", Alert::LEVEL_WARNING);
			JetBackupLinux::deleteSnapshots();
			return;
		}

		try {
			JetBackupLinux::addToQueue();
		} catch (Exception $e) {
			return;
		}
	}
}