<?php

namespace JetBackup\Cron\Task;

use Exception;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Cron\Cron;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DirIteratorException;
use JetBackup\Exception\ExecutionTimeException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\TaskException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Log\FileLogger;
use JetBackup\Log\LogController;
use JetBackup\Log\Logger;
use JetBackup\Log\StdLogger;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Config\System;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

abstract class Task {

	private string $_log_file_name;
	private int $_execution_memory;
	private int $_execution_start;
	private int $_execution_limit;
	private bool $_execution_die=true;
	private ?QueueItem $_queue_item=null;
	private ?LogController $_cronLogController=null;
	private LogController $_logController;
	
	public function __construct(string $log_file_name) {
		$this->_log_file_name = $log_file_name;
		$this->_execution_start = time();
		$this->_execution_memory = memory_get_usage();
		$this->_execution_limit = Factory::getSettingsPerformance()->getExecutionTime();
	}

	/**
	 * Errors logged through this function are also counted and saved in the DB (->addError())
	 * Using the error count we know if the job is completed with error
	 *
	 * Only use this for errors which will not break the loop (errors that just report and continue)
	 *
	 * @param $error
	 *
	 * @return void
	 */
	public function logError($error) {
		$this->getQueueItem()->addError();
		$this->getLogController()->logError($error);
	}

	public function setExecutionTimeLimit(int $limit):void {
		if(Factory::getSettingsPerformance()->getExecutionTime()) return;
		$this->_execution_limit = ($limit ?? max(System::getServerExecutionTime() - 20, 40)); 
	}

	public function setExecutionTimeDie(bool $die):void {
		$this->_execution_die = $die;
	}

	public function getExecutionTimeLimit():int {
		return $this->_execution_limit;
	}

	public function getExecutionTimeElapsed():int {
		return time() - $this->_execution_start;
	}

	public function getExecutionMemoryUsage():int {
		return memory_get_usage() - $this->_execution_memory;
	}

	public function isExecutionTimeLimitReached():bool {
		if($this->getExecutionTimeLimit() <= 0) return false;
		return $this->getExecutionTimeElapsed() >= $this->getExecutionTimeLimit();
	}

	/**
	 * @param callable|null $callback
	 *
	 * @return void
	 * @throws ExecutionTimeException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws JBException
	 */
	public function checkExecutionTime(?callable $callback=null):void {

		if(file_exists($this->getQueueItem()->getAbortFileLocation())) {

			// Prevent infinite loop with an aborted backup job (case #862)
			if ($this->getQueueItem()->getType() == Queue::QUEUE_TYPE_BACKUP) {
				$instance = $this->getQueueItem()->getItemData();
				$backup_job = new BackupJob($instance->getJobId());
				$backup_job->setLastRun(time());
				$backup_job->calculateNextRun();
				$backup_job->save();
			}

			$this->getQueueItem()->getProgress()->setSubMessage("[Queue Item aborted, exiting]");
			$this->getLogController()->logMessage("Queue Item aborted, exiting");
			$this->getQueueItem()->save();
			$this->getLogController()->logDebug("!! Nice Exit !!");
			unlink($this->getQueueItem()->getAbortFileLocation());
			die(0);
		}

		if(!$this->isExecutionTimeLimitReached() && $this->getQueueItem()->getStatus() < Queue::STATUS_DONE) return;
		if($callback) $callback($this->getExecutionTimeElapsed(), $this->getExecutionTimeLimit(), $this->getExecutionMemoryUsage());

		$this->getLogController()->logMessage("Current memory usage: " . Util::bytesToHumanReadable(max($this->getExecutionMemoryUsage(), 0)));

		if($this->getQueueItem()->getStatus() >= Queue::STATUS_DONE) {
			$this->getQueueItem()->getProgress()->setSubMessage("[Queue Item finished, exiting]");
			$this->getLogController()->logMessage("Queue Item finished, exiting");
		} else {
			$this->getQueueItem()->getProgress()->setSubMessage("[Waiting for next cron execution]");
			$this->getLogController()->logMessage("Execution TTL [{$this->getExecutionTimeLimit()} seconds] Time reached: {$this->getExecutionTimeElapsed()} seconds, exiting to resume later");
		}

		$this->getQueueItem()->save();

		$this->getLogController()->logDebug("!! Nice Exit !!");

		if($this->_execution_die) die(0);
		throw new ExecutionTimeException("!! Nice Exit !!");
	}

	public function setQueueItem(QueueItem $item):void { $this->_queue_item = $item; }
	public function getQueueItem():?QueueItem { return $this->_queue_item; }

	public function setLogController(LogController $logController):void { $this->_logController = $logController; }
	public function getLogController():LogController { return $this->_logController; }

	public function setCronLogController(LogController $logController):void { $this->_cronLogController = $logController; }
	public function getCronLogController():LogController { return $this->_cronLogController ?? new LogController(); }

	/**
	 * @throws Exception
	 */
	public function getLogFile(): string {
		return Factory::getLocations()->getLogsDir() . JetBackup::SEP . $this->_log_file_name . '_' . $this->getQueueItem()->getUniqueId() . '.log';
	}
	
	public function execute():void {
		if(!$this->getQueueItem()) throw new TaskException("No queue item provided");

		$this->getQueueItem()->setLogFile($this->getLogFile());
		$this->getQueueItem()->save();

		$level=Logger::LOG_LEVEL_ERROR | Logger::LOG_LEVEL_WARNING | Logger::LOG_LEVEL_NOTICE | Logger::LOG_LEVEL_MESSAGE;
		if(Factory::getSettingsLogging()->isDebugEnabled()) $level |= Logger::LOG_LEVEL_DEBUG;

		$this->setLogController(new LogController());
		$this->getLogController()->addLogger(new FileLogger($this->getLogFile(), $level));
		if(Cron::inDebug()) $this->getLogController()->addLogger(new StdLogger($level));
	}
	
	public function func(callable $callback, array $args=[], ?string $name=null) {
		$resumable = $this->getQueueItem()->getResumableTask();
		$resumable->setLogController($this->getLogController());
		$resumable->setTask($this);
		return $resumable->func($callback, $args, $name);
	}

	public function foreach(array $data, callable $func, ?string $name=null) {
		$resumable = $this->getQueueItem()->getResumableTask();
		$resumable->setLogController($this->getLogController());
		$resumable->setTask($this);
		$resumable->foreach($data, $func, $name);
	}

	public function foreachCallable(callable $data, array $args, callable $func, ?string $name=null) {
		$resumable = $this->getQueueItem()->getResumableTask();
		$resumable->setLogController($this->getLogController());
		$resumable->setTask($this);
		$resumable->foreachCallable($data, $args, $func, $name);
	}

	/**
	 * @throws IOException
	 * @throws DirIteratorException
	 */
	public function scan(string $source, callable $func, array $excludes=[], ?string $name=null):void {
		$resumable = $this->getQueueItem()->getResumableTask();
		$resumable->setLogController($this->getLogController());
		$resumable->setTask($this);
		$resumable->scan($source, $func, $excludes, $name);
	}
	
	public function fileRead(string $file_path, callable $func, ?string $name=null):void {
		$resumable = $this->getQueueItem()->getResumableTask();
		$resumable->setLogController($this->getLogController());
		$resumable->setTask($this);
		$resumable->fileRead($file_path, $func, $name);
	}

	public function fileMerge(string $source, string $target, ?string $name=null):void {
		$resumable = $this->getQueueItem()->getResumableTask();
		$resumable->setLogController($this->getLogController());
		$resumable->setTask($this);
		$resumable->fileMerge($source, $target, $name);
	}
}