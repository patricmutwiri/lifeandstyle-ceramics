<?php

namespace JetBackup\Backup;

use Exception;
use JetBackup\Archive\Gzip;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Config\System;
use JetBackup\Data\Mysqldump;
use JetBackup\Entities\Util;
use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\JBException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Notification\Notification;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Snapshot\SnapshotItem;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class BackupAccount extends Backup {

	const SKELETON_FILES_DIRNAME = 'files';
	const SKELETON_DATABASE_DIRNAME = 'database';

	const DEFAULT_EXCLUDE_TABLES = [
		// Shield security
		'icwp_wpsf_events',
		'icwp_wpsf_audit_trail',
		'icwp_wpsf_sessions',
		'icwp_wpsf_scan_results',
		'icwp_wpsf_scan_items',
		'icwp_wpsf_lockdown',

		// Woocommerce
		'woocommerce_sessions',
		'actionscheduler_logs',
		'woocommerce_log',

		// Yoast
		'yoast_seo_links',
		'yoast_seo_meta',

		// Wordfence
		'wfLiveTrafficHuman',
		'wfBlockedIPLog',
		'wfCrawlers',
		'wfFileChanges',
		'wfFileMods',
		'wfHits',
		'wfIssues',
		'wfKnownFileList',
		'wfLocs',
		'wfLogins',
		'wfNet404s',
		'wfNotifications',
		'wfPendingIssues',
		'wfReverseCache',
		'wfSNIPCache',
		'wfStatus',
		'wfTrafficRates',

		// UpdraftPlus (Temporary data for backup jobs)
		'updraft_jobdata',

		//Activity Log Plugins
		'aryo_activity_log',
		'wsal_occurrences',
		'simple_history',
		'wpml_mails',

		//Redirection Plugins
		'redirection_logs',
		'redirection_404',

		//WP Statistics
		'statistics_events',
		'statistics_exclusions',
		'statistics_historical',
		'statistics_pages',
		'statistics_useronline',
		'statistics_visit',
		'statistics_visitor',
		'statistics_visitor_relationships'
	];

	/**
	 * @throws IOException
	 * @throws InvalidArgumentException|JBException
	 */
	public function execute():void {
		
		try {


			$this->getTask()->func([$this, '_prepareWorkingSpace']);
			$this->getTask()->func([$this, '_dumpDB']);
			$this->getTask()->func([$this, '_archiveFiles'], [rtrim(Factory::getWPHelper()->getWordPressHomedir(), JetBackup::SEP)]);
			$this->getTask()->func([$this, '_compressFiles']);
			$this->getTask()->func([$this, '_transferToDestination']);

			if($this->getQueueItem()->getStatus() < Queue::STATUS_DONE) {
				$backup_destinations = sizeof($this->getBackupJob()->getDestinations());
				$queue_destinations = sizeof($this->getQueueItemBackup()->getDestinations());

				if($backup_destinations == $queue_destinations && !$this->getQueueItem()->getErrors()) $this->getQueueItem()->updateStatus(Queue::STATUS_DONE);
				else $this->getQueueItem()->updateStatus(Queue::STATUS_PARTIALLY);
			}
			
			$this->getLogController()->logMessage('Completed!');

			$this->getQueueItem()->updateProgress( 'Backup Completed! '. '['.$this->getBackupJob()->getName().']', QueueItem::PROGRESS_LAST_STEP);
			
			$this->getBackupJob()->calculateNextRun();
			$this->getBackupJob()->setLastRun(time());
			$this->getBackupJob()->save();

			//Send notification
			Notification::message()
				->addParam('backup_domain', Wordpress::getSiteDomain())
	            ->addParam('job_name', $this->getBackupJob()->getName())
	            ->addParam('backup_date', Util::date('Y-m-d H:i:s', $this->getBackupJob()->getLastRun()))
	            ->addParam('backup_status', Queue::STATUS_NAMES[$this->getQueueItem()->getStatus()])
	            ->send('JetBackup Completed', 'job_completed');

			//Add retention cleanup to the queue after job is done;
			//This has to run AFTER reindex because the data is based on reindex table
			$this->_retentionCleanup();

			//Add to queue backups that have 'After job is done'
			$this->_calculateAfterJobDone();
		} catch( Exception $e) {
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

		$this->getQueueItem()->updateStatus(Queue::STATUS_BACKUP_ACCOUNT_SEND_TO_DESTINATION);
		$this->getQueueItem()->updateProgress("Transferring backup to destinations");

		parent::_transferToDestination();
	}

	public function _compressFiles() {

		if(!($this->getBackupJob()->getContains() & BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR) || !Factory::getSettingsPerformance()->isGzipCompressArchive()) {
			$this->getLogController()->logMessage( 'GZIP Compression is disabled, skipping!' );
			$this->getQueueItem()->updateStatus(Queue::STATUS_BACKUP_ACCOUNT_SEND_TO_DESTINATION);
			return;
		}

		$this->getLogController()->logMessage('Execution time: ' . $this->getTask()->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getTask()->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_BACKUP_ACCOUNT_COMPRESSING);
		$this->getQueueItem()->updateProgress("Compressing backup");

		parent::_compressFiles();
	}

	/**
	 * @throws ArchiveException
	 */
	public function _archiveFiles($source):void {

		if (!( $this->getBackupJob()->getContains() & BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR)) {
			$this->getLogController()->logMessage('Skipping files backup');
			return;
		}

		$this->getLogController()->logMessage('Execution time: ' . $this->getTask()->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getTask()->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_BACKUP_ACCOUNT_ARCHIVING);
		$this->getQueueItem()->getProgress()->setSubMessage('');
		$this->getQueueItem()->updateProgress("Archiving data");

		parent::_archiveFiles($source);
	}

	/**
	 * @return void
	 */
	public function _prepareWorkingSpace():void {

		$this->getLogController()->logMessage('Execution time: ' . $this->getTask()->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getTask()->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_PREPARING);
		$this->getQueueItem()->updateProgress("Preparing");
		$this->getLogController()->logMessage('Status 2 Preparing work space');
		$this->getLogController()->logMessage("Execution mode: " . (Helper::isCLI() ? "CLI" : "WEB"));
		$this->getLogController()->logMessage("Plugin Version: " . JetBackup::VERSION);
		$this->getLogController()->logMessage("\t -- Backup Environment:");
		foreach ( System::getSystemInfo()  as $key => $value ) {
			$this->getLogController()->logMessage("\t\t$key: $value");
		}

		$this->_createWorkspace();
		$this->_exportConfig();
	}


	private function _exportConfig () {
		// export WordPress wp-config & htaccess file

		$helper = Factory::getWPHelper();

		$config_files = [
			sprintf(self::HTACCESS_FILE, $helper->getWordPressHomedir()),
			sprintf(self::WP_CONFIG_FILE, $helper->getWordPressHomedir()),
		];

		foreach ($config_files as $file) {
			if (!file_exists($file)) continue;

			$target = $this->getSnapshotDirectory() . JetBackup::SEP . Snapshot::SKELETON_CONFIG_DIRNAME . JetBackup::SEP . basename($file);
			copy($file, $target);
			chmod($target, 0600);
		}
	}

	protected function getSnapshotItems():array {

		$multisite = [];
		foreach(Wordpress::getMultisiteBlogs() as $blog) $multisite[] = $blog->getData();
		$db_prefix = Wordpress::getDB()->getPrefix();

		$output = [];

		$path = self::SKELETON_FILES_DIRNAME . JetBackup::SEP . Snapshot::SKELETON_FILES_ARCHIVE_NAME . (Factory::getSettingsPerformance()->isGzipCompressArchive() ? '.gz' : '');
		$homedir = $this->getSnapshotDirectory() . JetBackup::SEP . $path;
		$size = file_exists($homedir) ? filesize($homedir) : 0;

		// Files Item
		$item = new SnapshotItem();
		$item->setBackupType(BackupJob::TYPE_ACCOUNT);
		$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR);
		$item->setCreated(time());
		$item->setName('');
		$item->setSize($size);
		$item->setPath($path);
		$output[] = $item;
		
		// Database Tables Items
		foreach($this->_getDBTables() as $table) {
			
			$path = self::SKELETON_DATABASE_DIRNAME . JetBackup::SEP . $table . '.sql' . (Factory::getSettingsPerformance()->isGzipCompressDB() ? '.gz' : '');
			$file = $this->getSnapshotDirectory() . JetBackup::SEP . $path;
			$size = file_exists($file) ? filesize($file) : 0;

			$item = new SnapshotItem();
			$item->setBackupType(BackupJob::TYPE_ACCOUNT);
			$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE);
			$item->setCreated(time());
			$item->setName($table);
			$item->setSize($size);
			$item->setPath($path);
			$item->addParam(Snapshot::PARAM_DB_PREFIX, $db_prefix);
			$item->addParam(Snapshot::PARAM_DB_EXCLUDED,  in_array($table, $this->getBackupJob()->getExcludeDatabases()));
			$output[] = $item;
		}

		// Full Item
		$item = new SnapshotItem();
		$item->setBackupType(BackupJob::TYPE_ACCOUNT);
		$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL);
		$item->setCreated(time());
		$item->setName('');
		$item->setPath('');
		$item->setSize(0);
		$item->addParam(Snapshot::PARAM_MULTISITE, $multisite);
		$item->addParam(Snapshot::PARAM_SITE_URL, Wordpress::getSiteURL());

		$output[] = $item;

		return $output;
	}


	/**
	 * @return array
	 */
	public function _getDBTables():array {
		return $this->getTask()->func(function () {
			return Wordpress::getDB()->listTables();
		}, [], '_getDBTables');
	}

	/**
	 * @return array
	 */
	public function _getExcludedTables(): array {
		return $this->getTask()->func(function () {
			$excludes = $this->getBackupJob()->getExcludeDatabases();
			if (Factory::getSettingsPerformance()->isUseDefaultDBExcludes()) {
				$tables = $this->_getDBTables();
				foreach (self::DEFAULT_EXCLUDE_TABLES as $table) {
					$table = Wordpress::getDB()->getPrefix() . $table;
					if (!in_array($table, $tables)) continue;
					$excludes[] = $table;
				}
			}
			return $excludes;
		}, [], '_getExcludedTables');
	}

	/**
	 * @throws Exception
	 */
	public function _dumpDB() {

		$log = $this->getLogController();

		if (!($this->getBackupJob()->getContains() & BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE)) {
			$log->logMessage('Skipping database backup');
			return;
		}

		$queueItem = $this->getQueueItem();

		$log->logDebug('[_dumpDB]');
		$log->logMessage('Execution time: ' . $this->getTask()->getExecutionTimeElapsed());
		$log->logMessage('TTL time: ' . $this->getTask()->getExecutionTimeLimit());

		$queueItem->updateStatus(Queue::STATUS_BACKUP_ACCOUNT_DUMPING_DB);
		$queueItem->updateProgress("Dumping database");
		$db_dump_folder = $this->getSnapshotDirectory() . JetBackup::SEP . BackupAccount::SKELETON_DATABASE_DIRNAME;

		if (Factory::getSettingsPerformance()->isSQLCleanupRevisionsEnabled()) {
			$this->getTask()->func(function() use ($log){
				$log->logMessage('Revision cleanup is enabled, starting revision cleanup');
				Wordpress::getDB()->clearPostRevisions();
				$log->logMessage('Revision cleanup is done');
			}, [], '_revisionCleanup');
		}
		
		$this->getTask()->foreach($this->_getDBTables(), function($i, $tableName, $totalTables) use ($db_dump_folder, $log, $queueItem) {

			$currentCount = ($i+1);

			// Dump table
			$this->getTask()->func(function () use ($tableName, $totalTables, $db_dump_folder, $log, $queueItem, $currentCount) {

				$dump = new Mysqldump(DB_NAME, DB_USER, DB_PASSWORD, DB_HOST);
				$dump->setInclude([$tableName]);
				$dump->setExclude($this->_getExcludedTables());
				$dump->setLogController($log);
				$log->logMessage("Exporting $tableName [$currentCount/$totalTables]");

				$dump->start($db_dump_folder . JetBackup::SEP . $tableName . '.sql');

				$progress = $queueItem->getProgress();
				$progress->setSubMessage("Exporting $tableName");
				$queueItem->save();

				$this->getTask()->checkExecutionTime(function () use ($log, $queueItem) {
					$log->logDebug("[_dumpDB] exitOnLimitReached triggered");
					$queueItem->getProgress()->setMessage('[ DB Dump ] Waiting for next cron iteration');
					$queueItem->save();
				});

				$log->logDebug("[_dumpDB] Finished export loop, Current: $tableName, Total: $totalTables");


			}, [], '_dumpTable' . $tableName);



			// Compress table
			$this->getTask()->func(function () use ($tableName, $totalTables, $db_dump_folder, $log, $queueItem, $currentCount) {

				if (!Factory::getSettingsPerformance()->isGzipCompressDB()) {
					$log->logMessage('Skipping database compression, option is disabled.');
					return;
				}

				$currentFile = $db_dump_folder . JetBackup::SEP .  $tableName . ".sql";

				$log->logMessage("Compressing $tableName [$currentCount/$totalTables]");

				if(!file_exists($currentFile)) {
					$log->logError("File $currentFile does not exist.");
					return;
				}

				Gzip::compress(
					$currentFile,
					Gzip::DEFAULT_COMPRESS_CHUNK_SIZE,
					Gzip::DEFAULT_COMPRESSION_LEVEL,
					function($byteRead) use ($tableName) {

						$progress = $this->getQueueItem()->getProgress();
						$progress->setSubMessage("Compressing $tableName [$byteRead bytes]");
						$this->getQueueItem()->save();

						$this->getTask()->checkExecutionTime(function() {
							$this->getQueueItem()->getProgress()->setMessage('[ Gzip ] Waiting for next cron iteration');
							$this->getQueueItem()->save();
						});
					}
				);

				$log->logDebug("[_compressDB] Finished compress loop, Current: $tableName, Total: $totalTables");

			}, [], '_compressTable' . $tableName);


			$progress = $queueItem->getProgress();
			$progress->setTotalSubItems($totalTables);
			$progress->setCurrentSubItem($currentCount);
			$queueItem->save();

		});


	}
}