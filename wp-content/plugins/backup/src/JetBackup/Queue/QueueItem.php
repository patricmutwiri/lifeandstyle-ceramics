<?php

namespace JetBackup\Queue;

use JetBackup\BackupJob\BackupJob;
use JetBackup\CLI\CLI;
use JetBackup\Data\Engine;
use JetBackup\Data\SleekStore;
use JetBackup\Entities\Util;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\TaskException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use JetBackup\ResumableTask\ResumableTask;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;
use SleekDB\QueryBuilder;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class QueueItem extends Engine {
	
	const COLLECTION = 'queue';
	
	const UNIQUE_ID = 'unique_id';
	const ITEM_ID = 'item_id';
	const CREATED = 'created';
	const STARTED = 'started';
	const ENDED = 'ended';
	const TYPE = 'type';
	const TYPE_NAME = 'type_name';
	const STATUS = 'status';
	const STATUS_NAME = 'status_name';
	const STATUS_TIME = 'status_time';
	const PROGRESS = 'progress';
	const ERRORS = 'errors';
	const ITEM_DATA = 'item_data';
	const LOG_FILE = 'log_file';
	const EXEC_TIME = 'exec_time';
	
	const PROGRESS_LAST_STEP = -1;
	
	private ?Progress $_progress=null;
	private ?aQueueItem $_item_data=null;
	private ?ResumableTask $_resumable_task=null;
	
	public function __construct($_id=null) {
		parent::__construct(self::COLLECTION);
		if($_id) $this->_loadById((int) $_id);
	}

	/**
	 * @param $unique_id
	 *
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function loadByUniqueId($unique_id) {
		$this->_load([[self::UNIQUE_ID, '=', $unique_id]]);
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function addError(): void {
		$this->set(self::ERRORS, $this->getErrors() + 1);
		$this->save();
	}

	public function getErrors(): int {
		return $this->get(self::ERRORS, 0);
	}


	public function setUniqueId($id) { $this->set(self::UNIQUE_ID, $id); }
	public function getUniqueId():string { return $this->get(self::UNIQUE_ID); }

	public function setCreated($value):void { $this->set(self::CREATED, $value); }
	public function getCreated() { return $this->get(self::CREATED); }

	public function setStarted(int $value):void { $this->set(self::STARTED, $value); }
	public function getStarted():int { return (int) $this->get(self::STARTED, 0); }

	public function setEnded(int $value):void { $this->set(self::ENDED, $value); }
	public function getEnded():int { return (int) $this->get(self::ENDED, 0); }

	public function setType($value):void { 
		$this->set(self::TYPE, $value);
		$this->_item_data = null;
	}
	
	public function getType() { return $this->get(self::TYPE); }

	public function setStatus($value):void { 
		$this->set(self::STATUS, $value); 
		$this->setStatusTime(time());
	}
	public function getStatus() {
		return $this->get(self::STATUS); // this will return from memory
	}

	public function setProgress(Progress $value):void { 
		$this->_progress = $value;
		$this->set(self::PROGRESS, $value->getData()); 
	}

	public function getProgress():Progress { 
		if(!$this->_progress) $this->_progress = new Progress($this->get(self::PROGRESS, []));
		return $this->_progress; 
	}

	public function updateProgress(string $message, ?int $current_item=null) {
		$progress = $this->getProgress();
		$currentProgress = $progress->getCurrentItem();

		if($current_item !== null) $currentProgress = $current_item <= $progress->getTotalItems() ? $current_item : $progress->getTotalItems();
		if($current_item === self::PROGRESS_LAST_STEP) $currentProgress = $progress->getTotalItems();
		else $currentProgress++;
		$progress->setMessage($message);
		$progress->setCurrentItem($currentProgress);
		$this->save();
	}

	public function updateStatus($status) {
		if($this->getStatus() == Queue::STATUS_PENDING) $this->setStarted(time());
		$this->setStatus($status);
		$this->setStatusTime(time());
		if($status >= Queue::STATUS_DONE) {
			$this->getResumableTask()->delete();
			$this->setEnded(time());
		}
		$this->save();
		if (in_array($this->getStatus(), Queue::REQUIRES_CLEANUP)) Util::rm($this->getWorkspace());
	}

	public function setItemData(aQueueItem $value):void {
		$this->_item_data = $value;
		$this->set(self::ITEM_DATA, $value->getData()); 
	}
	
	/**
	 * @return QueueItemBackup|QueueItemRestore|QueueItemDownload|QueueItemReindex|QueueItemRetentionCleanup|QueueItemSystem|QueueItemExport|QueueItemExtract|null
	 */
	public function getItemData():?aQueueItem {

		if(!$this->_item_data) {
			$data = (array) $this->get(self::ITEM_DATA, []);
			switch ($this->getType()) {
				case Queue::QUEUE_TYPE_BACKUP: $this->_item_data = new QueueItemBackup($data); break;
				case Queue::QUEUE_TYPE_RESTORE: $this->_item_data = new QueueItemRestore($data); break;
				case Queue::QUEUE_TYPE_DOWNLOAD: $this->_item_data = new QueueItemDownload($data); break;
                case Queue::QUEUE_TYPE_DOWNLOAD_BACKUP_LOG: $this->_item_data = new QueueItemDownload($data); break;
                case Queue::QUEUE_TYPE_REINDEX: $this->_item_data = new QueueItemReindex($data); break;
				case Queue::QUEUE_TYPE_RETENTION_CLEANUP: $this->_item_data = new QueueItemRetentionCleanup($data); break;
				case Queue::QUEUE_TYPE_SYSTEM: $this->_item_data = new QueueItemSystem($data); break;
				case Queue::QUEUE_TYPE_EXPORT: $this->_item_data = new QueueItemExport($data); break;
				case Queue::QUEUE_TYPE_EXTRACT: $this->_item_data = new QueueItemExtract($data); break;

			}
		}
		
		return $this->_item_data;
	}

	public function setLogFile($value):void { $this->set(self::LOG_FILE, $value); }
	public function getLogFile() { return $this->get(self::LOG_FILE); }

	public function setItemId($value):void { $this->set(self::ITEM_ID, $value); }
	public function getItemId() { return $this->get(self::ITEM_ID); }

	public function setStatusTime($value):void { $this->set(self::STATUS_TIME, $value); }
	public function getStatusTime() { return $this->get(self::STATUS_TIME); }

	public static function db():SleekStore {
		return new SleekStore(self::COLLECTION);
	}

	public static function query():QueryBuilder {
		return self::db()->createQueryBuilder();
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function delete():void {
		if(!$this->getId()) return;
		$this->getDB()->clearCache();
		$this->getDB()->deleteById($this->getId());
	}

	public function save():void {
		if(!$this->getUniqueId()) $this->setUniqueId(Util::generateUniqueId());
		$this->setProgress($this->getProgress());
		$this->setItemData($this->getItemData());
		parent::save();
	}
	
	public function getResumableTask():ResumableTask {
		if(!$this->_resumable_task) $this->_resumable_task = new ResumableTask($this->getUniqueId(), $this->getWorkspace());
		return $this->_resumable_task;
	}
	
	public function getWorkspace(): string {
		return Factory::getLocations()->getTempDir() . JetBackup::SEP . $this->getUniqueId();
	}

	public function getAbortFileLocation(): string {
		return $this->getWorkspace() . JetBackup::SEP . $this->getUniqueId() .  '.abort';
	}
	/**
	 * @return void
	 * @throws TaskException
	 */
	public function abort() {
		if ($this->getStatus() == Queue::STATUS_PENDING) {
			$this->updateStatus(Queue::STATUS_ABORTED);
			return;
		}
		if(!file_exists($this->getWorkspace())) throw new TaskException('Queue item working folder not set yet');
		$this->updateStatus(Queue::STATUS_ABORTED);
		touch($this->getAbortFileLocation());
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public function startOver() {
		$this->setUniqueId('');
		$this->setStatus(Queue::STATUS_PENDING);
		$progress = $this->getProgress();
		$progress->setMessage('Starting over queue item');
		$progress->resetSub();
		$this->setProgress($progress);
		if(file_exists($this->getAbortFileLocation())) unlink($this->getAbortFileLocation());
		$this->save();
	}

	public function getExecutionTime():string {
		if ($this->getStarted() == 0) return '00:00:00';
		if ($this->getEnded() > 0) return gmdate('H:i:s', $this->getEnded() - $this->getStarted());
		return gmdate('H:i:s', time() - $this->getStarted());
	}
	
	public static function prepare():QueueItem {
		$item = new QueueItem();
		$item->setStatus(Queue::STATUS_PENDING);
		$item->setCreated(time());
		$item->setEngine(Engine::ENGINE_WP);

		$progress = $item->getProgress();
		$progress->setCurrentItem(0);
		$progress->setMessage('Waiting for cron...');

		return $item;
	}
	
	public function getDisplay():array {
		return [
			JetBackup::ID_FIELD             => $this->getId(),
			self::UNIQUE_ID                 => $this->getUniqueId(),
			self::ITEM_ID                   => $this->getItemId(),
			self::TYPE                      => $this->getType(),
			self::TYPE_NAME                 => Queue::QUEUE_TYPES_NAMES[$this->getType()] ?? 'Unknown',
			Engine::ENGINE                  => $this->getEngine(),
			self::CREATED                   => $this->getCreated(),
			self::STARTED                   => $this->getStarted(),
			self::ENDED                     => $this->getEnded(),
			self::ITEM_DATA                 => $this->getItemData()->getDisplay() ?: [],
			self::STATUS                    => $this->getStatus(),
			self::STATUS_NAME               => Queue::QUEUE_STATUS_NAMES[$this->getType()][$this->getStatus()] ?? '',
			self::STATUS_TIME               => $this->getStatusTime(),
			self::PROGRESS                  => $this->getProgress()->getDisplay(),
			self::LOG_FILE                  => $this->getLogFile(),
			self::EXEC_TIME                 => $this->getExecutionTime(),
		];
	}

	public function getDisplayCLI():array {
		$statuses = Queue::STATUS_NAMES;
		switch($this->getType()) {
			case Queue::QUEUE_TYPE_BACKUP:
				/** @var QueueItemBackup $item */
				$item = $this->getItemData();
				$statuses += $item->getType() == BackupJob::TYPE_ACCOUNT ? Queue::STATUS_BACKUP_ACCOUNT_NAMES : Queue::STATUS_BACKUP_CONFIG_NAMES;
			break;
			case Queue::QUEUE_TYPE_RESTORE: $statuses += Queue::STATUS_PRE_RESTORE_NAMES; break;
			case Queue::QUEUE_TYPE_DOWNLOAD: $statuses += Queue::STATUS_DOWNLOAD_NAMES; break;
			case Queue::QUEUE_TYPE_REINDEX: $statuses += Queue::STATUS_REINDEX_NAMES; break;
			case Queue::QUEUE_TYPE_RETENTION_CLEANUP: $statuses += Queue::STATUS_CLEANUP_NAMES; break;
			case Queue::QUEUE_TYPE_SYSTEM: $statuses += Queue::STATUS_SYSTEM_NAMES; break;
			case Queue::QUEUE_TYPE_EXPORT: $statuses += Queue::STATUS_EXPORT_NAMES; break;
			case Queue::QUEUE_TYPE_EXTRACT: $statuses += Queue::STATUS_EXTRACT_NAMES; break;
		}
		
		return [
			'ID'             => $this->getId(),
			'Item ID'                   => $this->getItemId(),
			'Type'                      => Queue::QUEUE_TYPES_NAMES[$this->getType()] . ' (' . $this->getType() . ')',
			'Engine'                    => $this->getEngineName(),
			'Created'                   => CLI::date($this->getCreated()),
			'Started'                   => $this->getStarted() ? CLI::date($this->getStarted()) : 'Never',
			'Ended'                     => $this->getEnded() ? CLI::date($this->getEnded()) : 'Never',
			'Status'                    => $statuses[$this->getStatus()],
			'Status Time'               => $this->getStatusTime() ? CLI::date($this->getStatusTime()) : 'Never',
			'Log File'                  => $this->getLogFile(),
		];
	}

	/**
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {
		if(!$this->getType()) throw new FieldsValidationException("Queue type must be set");
	}


}