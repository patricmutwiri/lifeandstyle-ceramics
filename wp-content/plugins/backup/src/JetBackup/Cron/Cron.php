<?php

namespace JetBackup\Cron;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\BackupJob\BackupJob;
use JetBackup\Cache\CacheHandler;
use JetBackup\Cron\Task\Backup;
use JetBackup\Cron\Task\DownloadBackupLog;
use JetBackup\Cron\Task\RetentionCleanup;
use JetBackup\Cron\Task\Download;
use JetBackup\Cron\Task\Export;
use JetBackup\Cron\Task\Extract;
use JetBackup\Cron\Task\Reindex;
use JetBackup\Cron\Task\PreRestore;
use JetBackup\Cron\Task\System;
use JetBackup\Exception\CronException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\LogException;
use JetBackup\Exception\QueueException;
use JetBackup\Exception\ReindexException;
use JetBackup\Factory;
use JetBackup\IO\Lock;
use JetBackup\JetBackup;
use JetBackup\Log\FileLogger;
use JetBackup\Log\LogController;
use JetBackup\Log\Logger;
use JetBackup\Log\StdLogger;
use JetBackup\Queue\Queue;
use JetBackup\Wordpress\Helper;
use SleekDB\Exceptions\InvalidArgumentException;
use WP_REST_Response;

class Cron {

	const LOCK_FILE = 'cron.lock';
	const LAST_FILE = 'cron.last';

	private const CRON_LOG_FILE = 'cron.log';

	private LogController $_logController;
	private string $_data_dir;


	/**
	 * @throws CronException
	 * @throws LogException
	 */
	private function __construct() {
		if(!$this->canRun()) throw new CronException('Cron system disabled, you can only execute via wp-cli');

		$this->_data_dir = Factory::getLocations()->getDataDir();
		$this->_setLastRun();

		if (!Lock::LockFile($this->_data_dir . JetBackup::SEP . self::LOCK_FILE)) throw new CronException('Cron is already running', 501);

		$logFile = Factory::getLocations()->getLogsDir() . JetBackup::SEP . self::CRON_LOG_FILE;
		$level = Logger::LOG_LEVEL_ERROR | Logger::LOG_LEVEL_WARNING | Logger::LOG_LEVEL_NOTICE | Logger::LOG_LEVEL_MESSAGE;
		if(Factory::getSettingsLogging()->isDebugEnabled()) $level |= Logger::LOG_LEVEL_DEBUG;

		$this->_logController = new LogController();
		$this->_logController->addLogger(new FileLogger($logFile, $level));
		if(Cron::inDebug() || Helper::isWPCli()) $this->_logController->addLogger(new StdLogger($level));

	}

	/**
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws QueueException|DBException|JBException
	 */
	public static function main() {
		CacheHandler::pre();
		$cron = new Cron();
		$cron->execute();
		CacheHandler::post();
	}



	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException|DBException|JBException
	 */
	public function execute() {

		try {
			$this->_executeNextQueue();
		} catch(\TypeError|\Error $e) {
			$message = sprintf("Cron exited due to an fatal error. Error: %s in %s on line %s", $e->getMessage(), $e->getFile(), $e->getLine());
			$this->_logController->logError($message);
			$this->_logController->logError($e->getTraceAsString());
			die($message . PHP_EOL . $e->getTraceAsString());
		}

		// Add all scheduled backup jobs to queue
		BackupJob::addToQueueScheduled();
		
		// Add system tasks to queue 
		System::addToQueue();
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException|JBException
	 */
	private function _executeNextQueue():void {
		if(!($item = Queue::next())) return;

		$this->getLogController()->logMessage("Got next queue item");

		try {
			switch ($item->getType()) {
				case Queue::QUEUE_TYPE_BACKUP: $task = new Backup(); break;
				case Queue::QUEUE_TYPE_DOWNLOAD: $task = new Download(); break;
                case Queue::QUEUE_TYPE_DOWNLOAD_BACKUP_LOG: $task = new DownloadBackupLog(); break;
                case Queue::QUEUE_TYPE_EXTRACT: $task = new Extract(); break;
				case Queue::QUEUE_TYPE_REINDEX: $task = new Reindex(); break;
				case Queue::QUEUE_TYPE_RETENTION_CLEANUP: $task = new RetentionCleanup(); break;
				case Queue::QUEUE_TYPE_SYSTEM: $task = new System(); break;
				case Queue::QUEUE_TYPE_EXPORT: $task = new Export(); break;
				case Queue::QUEUE_TYPE_RESTORE: $task = new PreRestore(); break;
				default: throw new CronException('Could not find queue type');
			}
			
			$task->setQueueItem($item);
			$task->setCronLogController($this->getLogController());
			if(Helper::isCLI()) $task->setExecutionTimeLimit(0);
			$task->setExecutionTimeDie(true);
			$task->execute();
		} catch (\Exception $e) {
			$message = "Cron exited due to an uncaught " . get_class($e) . ". Error: " . $e->getMessage();
			$this->_logController->logError($message);

			$item->updateStatus(Queue::STATUS_NEVER_FINISHED);
			$progress = $item->getProgress();
			$progress->setMessage($message);

			$backup_job = null;

			if ($item->getType() == Queue::QUEUE_TYPE_BACKUP) {
				// Prevent infinite loop with a failed backup destination
				// It will fail before getting to the backup job, so the job meta will never update
				$instance = $item->getItemData();
				$backup_job = new BackupJob($instance->getJobId());
				$backup_job->setLastRun(time());
				$backup_job->calculateNextRun();
			}

			// Save operation is return void so have to try-catch them

			try {
				// Save queue item
				$item->save();
			} catch (\Exception $saveException) {
				$this->_logController->logError("Failed to save Queue item: " . $saveException->getMessage());
			}

			if ($backup_job !== null) {
				try {
					// Save the backup job
					$backup_job->save();
				} catch (\Exception $saveException) {
					$this->_logController->logError("Failed to save Backup Job: " . $saveException->getMessage());
				}
			}
		}

	}
	
	public function getLogController(): LogController {
		return $this->_logController;
	}
	
	private function _setLastRun() {
		if (!Helper::isCLI()) return;
		touch($this->_data_dir . JetBackup::SEP . self::LAST_FILE);
	}

	private function canRun(): bool {
		$_cron_disabled = !Factory::getSettingsAutomation()->isCronsEnabled();
		if ($_cron_disabled && !Helper::isCLI()) return false; // cannot run
		// Disable the script timeout for CLI mode
		if (Helper::isCLI()) set_time_limit(0);
		return true;
	}

	public static function inDebug(): bool { return self::_argExists('debug'); }


	public function __destruct() {
		Lock::UnlockFile($this->_data_dir . JetBackup::SEP . self::LOCK_FILE);
	}

	private static function _argExists($arg): bool {
		global $argv;
		return isset($argv) && in_array('--'.$arg, $argv);
	}
}