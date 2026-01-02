<?php

namespace JetBackup\Destination;

use Exception;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Cron\Task\Task;
use JetBackup\Data\DBObject;
use JetBackup\Data\SleekStore;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Vendors\JetStorage\JetStorage;
use JetBackup\Encryption\Crypt;
use JetBackup\Destination\Vendors\Local\Local;
use JetBackup\Entities\Util;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\ExecutionTimeException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\QueueException;
use JetBackup\Exception\RegistrationException;
use JetBackup\Exception\ValidationException;
use JetBackup\Factory;
use JetBackup\Destination\Integration\Destination as iDestination;
use JetBackup\Filesystem\File;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemReindex;
use JetBackup\ResumableTask\ResumableTask;
use JetBackup\Web\File\FileChunkIterator;
use JetBackup\Web\File\FileStream;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\QueryBuilder;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Destination extends DBObject {

	const COLLECTION = 'destinations';
	
	const PROTECTED_FIELD = 'JB|HIDDEN|********************';
	
	const UNIQUE_ID         = 'unique_id';
	const NAME              = 'name';
	const TYPE              = 'type';
	const NOTES             = 'notes';
	const PATH              = 'path';
	const ENABLED           = 'enabled';
	const READ_ONLY         = 'read_only';
	const FREE_DISK         = 'free_disk';
	const CHUNK_SIZE        = 'chunk_size';
	const EXPORT_CONFIG     = 'export_config';
	const JOBS_ASSIGNED     = 'jobs_assigned';
	const OPTIONS           = 'options';
	const DEFAULT           = 'default';

	const LICENSE_EXCLUDED = [Local::TYPE, JetStorage::TYPE];

	private ?iDestination $_destination=null;
	private ?LogController $_logController=null;

	/**
	 * @param $_id
	 *
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function __construct($_id=null) {
		parent::__construct(self::COLLECTION);
		if($_id) $this->_loadById((int) $_id);
	}

	/**
	 * @param $id
	 *
	 * @return void
	 */
	public function setUniqueId($id) { $this->set(self::UNIQUE_ID, $id); }

	/**
	 * @return string
	 */
	public function getUniqueId():string { return $this->get(self::UNIQUE_ID); }

	/**
	 * @param string $name
	 *
	 * @return void
	 */
	public function setName(string $name) { $this->set(self::NAME, $name); }

	/**
	 * @return string
	 */
	public function getName():string { return $this->get(self::NAME); }

	/**
	 * @param string $path
	 *
	 * @return void
	 */
	public function setPath(string $path) { $this->set(self::PATH, $path); }

	/**
	 * @return string
	 */
	public function getPath():string { return $this->get(self::PATH); }

	/**
	 * @param string $notes
	 *
	 * @return void
	 */
	public function setNotes(string $notes) { $this->set(self::NOTES, $notes); }

	/**
	 * @return string
	 */
	public function getNotes():string { return $this->get(self::NOTES); }

	/**
	 * @param object $options
	 *
	 * @return void
	 * @throws DestinationException
	 */
	public function setOptions(object $options):void { $this->getInstance()->setData($options); }

	/**
	 * @return object|array
	 * @throws DestinationException
	 */
	public function getOptions(): object { return (object) $this->getInstance()->getData(); }

	/**
	 * @param int $disk
	 *
	 * @return void
	 */
	public function setFreeDisk(int $disk) { $this->set(self::FREE_DISK, $disk); }

	/**
	 * @return int
	 */
	public function getFreeDisk():int { return (int) $this->get(self::FREE_DISK, 0); }

	/**
	 * @param bool $enabled
	 *
	 * @return void
	 */
	public function setEnabled(bool $enabled) { $this->set(self::ENABLED, $enabled); }

	/**
	 * @return bool
	 */
	public function isEnabled():bool { return !!$this->get(self::ENABLED, true); }

	/**
	 * @param bool $readonly
	 *
	 * @return void
	 */
	public function setReadOnly(bool $readonly) { $this->set(self::READ_ONLY, $readonly); }
	public function setDefault(bool $default) { $this->set(self::DEFAULT, $default); }
	public function isDefault():bool { return $this->get(self::DEFAULT, false); }
	/**
	 * @return bool
	 */
	public function isReadOnly():bool { return !!$this->get(self::READ_ONLY, false); }

	/**
	 * @param bool $export
	 *
	 * @return void
	 */
	public function setExportConfig(bool $export) { $this->set(self::EXPORT_CONFIG, $export); }

	/**
	 * @return bool
	 */
	public function isExportConfig():bool { return !!$this->get(self::EXPORT_CONFIG, true); }

	public function getDecryptedOptions():string {
		$option = $this->get(self::OPTIONS);
		return $option ? Crypt::decrypt($option, Factory::getConfig()->getEncryptionKey()) : '';
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws DBException
	 */
	public static function getIsDefault(?int $destinationId): bool {
		if (!$destinationId) return false;
		$destination = new self($destinationId);
		return $destination->isDefault();
	}

	/**
	 * @param LogController $logController
	 *
	 * @return void
	 */
	public function setLogController(LogController $logController):void { $this->_logController = $logController; }

	/**
	 * @return LogController
	 */
	public function getLogController(): LogController {
		if(!$this->_logController) $this->_logController = new LogController();
		return $this->_logController;
	}

	/**
	 * @return string
	 */
	public function getType():string { return $this->get(self::TYPE); }

	/**
	 * @param string $type
	 *
	 * @return void
	 */
	public function setType(string $type):void { $this->set(self::TYPE, $type); }

	/**
	 * By default, chunk size is saved in the DB as MB representation (1 for 1MB, 2 for 2MB)
	 * for size in bytes we use getChunkSizeBytes
	 * @return int
	 */
	public function getChunkSize(): int { return $this->get(self::CHUNK_SIZE, 1); }

	/**
	 * returns chunk size from DB in bytes
	 * @return int
	 */
	public function getChunkSizeBytes(): int { return ($this->getChunkSize()) * 1024 * 1024; }

	/**
	 * @param int $size
	 *
	 * @return void
	 */
	public function setChunkSize(int $size):void { $this->set(self::CHUNK_SIZE, $size); }

	/**
	 * @param $file
	 *
	 * @return bool
	 * @throws DestinationException
	 * @throws IOException
	 */
	public function fileExists($file):bool {
		return $this->getInstance()->fileExists($file);
	}

	/**
	 * @param $directory
	 *
	 * @return bool
	 * @throws DestinationException
	 * @throws IOException
	 */
	public function dirExists($directory):bool {
		return $this->getInstance()->dirExists($directory);
	}

	/**
	 * @param $directory
	 *
	 * @return DestinationDirIterator
	 * @throws DestinationException
	 */
	public function listDir($directory):DestinationDirIterator {
		return $this->getInstance()->listDir($directory);
	}

	/**
	 * @throws IOException
	 * @throws DestinationException
	 */

	/**
	 * @return Destination|null
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException|DBException
	 */
	public static function getDefaultDestination(): ?Destination {

		$result = self::query()
			->select([JetBackup::ID_FIELD])
			->where([ self::TYPE, "=", Local::TYPE ])
			->where([ self::DEFAULT, "=", true ])
			->getQuery()
			->first();

		return $result ? new Destination( $result[ JetBackup::ID_FIELD]) : null;

	}

	/**
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException|DestinationException|DBException
	 */
	public static function createDefaultDestination():Destination {

		if($destination = self::getDefaultDestination()) return $destination;

			$config = new Destination();
			$config->setType(Local::TYPE);
			$config->setName('Default');
			$config->setDefault(true);
			$config->setPath('/');
			$config->setChunkSize(1);
			$config->setExportConfig(false); // since case #1007 it's disabled by default
			$config->save();

			return $config;

	}


	public function createDir($directory) {
		$this->getInstance()->createDir($directory, true);
	}
	
	/**
	 * @throws IOException
	 * @throws DestinationException
	 */
	public function removeDir(string $directory):void {
		$this->getInstance()->removeDir($directory);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param QueueItem|null $queue_item
	 * @param Task|null $task
	 *
	 * @return void
	 * @throws DestinationException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws ExecutionTimeException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function copyFileToLocal(string $source, string $destination, ?QueueItem $queue_item=null, ?Task $task=null):void {

		if(!($file = $this->getInstance()->getFileStat($source))) throw new IOException("The provided file doesn't exist");

		if($file->getSize() > $this->getChunkSizeBytes()) {

			// Download in chunks
			$this->getLogController()->logMessage("Downloading file $source to $destination in chunks");
			$this->getLogController()->logDebug("[copyFileToLocal] Chunk size: {$this->getChunkSizeBytes()}");

			$download = $this->getInstance()->copyFileToLocalChunked($source, $destination);
			$offset = file_exists($destination) ? filesize($destination) : 0;
			$this->getLogController()->logDebug("[copyFileToLocal] [CHUNKED] Offset: $offset");

			while ($offset < $file->getSize()) {
				if($task) $task->checkExecutionTime();
				$chunkSize = min($this->getChunkSizeBytes(), $file->getSize() - $offset);
				$read = $download->download($offset, $offset + $chunkSize);
				$offset += $read;
				
				if($queue_item) {
					$progress = $queue_item->getProgress();
					$progress->setCurrentSubItem($progress->getCurrentSubItem()+$read);
					$queue_item->save();
				}

				$this->getLogController()->logDebug( "[copyFileToLocal] [CHUNKED] Progress: {$offset}/{$file->getSize()}" );
			}
		} else {
			// Download regular
			$this->getLogController()->logMessage("Downloading file $source to $destination");
			if(file_exists($destination)) {
				$this->getLogController()->logDebug("[copyFileToLocal] Destination file exists: $destination, DELETING BEFORE DOWNLOAD");
				unlink($destination);
			}
			$this->getInstance()->copyFileToLocal($source, $destination);

			if($queue_item) {
				$progress = $queue_item->getProgress();
				$progress->setCurrentSubItem($progress->getCurrentSubItem()+filesize($destination));
				$queue_item->save();
			}
		}
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param ResumableTask $resumableTask
	 *
	 * @return void
	 * @throws DestinationException
	 * @throws IOException
	 * @throws Exception
	 */
	public function copyFileToRemote(string $source, string $destination, ?QueueItem $queue_item=null, ?Task $task=null):void {
		
		$file = new File($source);
		
		if($file->isLink()) return;

		if($file->size() > $this->getChunkSizeBytes()) {
			
			// Upload in chunks
			$this->getLogController()->logMessage("[copyFileToRemote] [CHUNKED] Uploading file $source to $destination");
			$this->getLogController()->logDebug("[copyFileToRemote] [CHUNKED] Chunk size: " . $this->getChunkSizeBytes());

			$upload = $this->getInstance()->copyFileToRemoteChunked($source, $destination);
			
			$resumableTask = $queue_item ? $queue_item->getResumableTask() : new ResumableTask(sha1($destination));
			$resumableTask->setLogController($this->getLogController());
			
			$data = $resumableTask->func([$upload, 'prepare'], [], 'prepare_' . $source . '|' . $this->getId());
			$upload->setData((object) $data);

			$resumableTask->func(function() use ($source, $upload, $queue_item, $task) {

				$offset = $upload->getOffset();
				$chunkSize = $upload->getChunkSize() ?: $this->getChunkSizeBytes();
				$this->getLogController()->logDebug("[copyFileToRemote] [CHUNKED] Offset: " . $offset);

				$file = new FileStream($source);
				if($offset) $file->seek($offset);

				$iterator = new FileChunkIterator($file, $chunkSize);

				while($iterator->hasNext()) {
					$this->getLogController()->logDebug("[copyFileToRemote] [CHUNKED] Sending chunk {$file->tell()}/{$file->getSize()}");
					if($task) $task->checkExecutionTime();
					$chunk = $iterator->next();
					$upload->upload($chunk);

					if($queue_item) {
						$progress = $queue_item->getProgress();
						$progress->setCurrentSubItem($progress->getCurrentSubItem()+$chunk->getSize());
						$queue_item->save();
					}
				}
			}, [], 'upload_' . $source . '|' . $this->getId());

			$resumableTask->func([ $upload, 'finalize' ], [], 'finalize_' . $source . '|' . $this->getId());

		} else {
			// Upload regular
			$this->getLogController()->logMessage("[copyFileToRemote] Uploading file $source to $destination");
			$this->getInstance()->copyFileToRemote($source, $destination);

			if($queue_item) {
				$progress = $queue_item->getProgress();
				$progress->setCurrentSubItem($progress->getCurrentSubItem()+filesize($source));
				$queue_item->save();
			}
		}
	}

	/**
	 * @return iDestination
	 * @throws DestinationException
	 */
	public function getInstance():iDestination {
		if(!$this->_destination) {
			$type = $this->getType();
			$method = "\JetBackup\Destination\Vendors\\$type\\$type";
			if(!$type || !class_exists($method)) throw new DestinationException("Destination type not found ($type)");
			$this->_destination = new $method($this->getChunkSizeBytes(), $this->getPath(), $this->getLogController(), $this->getName() ?? null, $this->getId());
			$decrypted_options = $this->getDecryptedOptions();
			if($decrypted_options) $this->_destination->setSerializedData($decrypted_options);
		}
		return $this->_destination;
	}

	public function updateSerializedData(string $data):void {
		$this->set(self::OPTIONS, Crypt::encrypt($data, Factory::getConfig()->getEncryptionKey()));
		parent::save();
	}

	/**
	 * @return void
	 * @throws ConnectionException
	 * @throws DestinationException
	 */
	public function connect():void { $this->getInstance()->connect(); }

	/**
	 * @return void
	 * @throws DestinationException
	 */
	public function disconnect():void { $this->getInstance()->disconnect(); }

	/**
	 * @return void
	 * @throws DestinationException
	 * @throws RegistrationException
	 */
	public function register():void { $this->getInstance()->register(); }

	/**
	 * @return void
	 * @throws DestinationException
	 */
	public function unregister():void { $this->getInstance()->unregister(); }

	/**
	 * @return array
	 * @throws DestinationException
	 */
	public function protectedFields():array { return $this->getInstance()->protectedFields(); }

	/**
	 * @return void
	 * @throws DestinationException
	 */
	public function save():void {
		if(!$this->getUniqueId()) $this->setUniqueId(Util::generateUniqueId());
		$this->updateSerializedData($this->getInstance()->getSerializedData());
	}

	/**
	 * @return SleekStore
	 */
	public static function db():SleekStore {
		return new SleekStore(self::COLLECTION);
	}

	/**
	 * @return QueryBuilder
	 */
	public static function query():QueryBuilder {
		return self::db()->createQueryBuilder();
	}

	/**
	 * @return void
	 * @throws ValidationException|DestinationException
	 */
	public function validate():void {
		$instance = $this->getInstance();
		
		try {
			$instance->connect();
			$instance->disconnect();
		} catch(ConnectionException $e) {
			throw new ValidationException($e->getMessage());
		}
	}

	/**
	 * @param bool $cross_domain
	 * @param bool $recursive
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function addToQueue( bool $cross_domain=false):void {

		$reindex = new QueueItemReindex();
		$reindex->setDestinationId($this->getId());
		$reindex->setCrossDomain($cross_domain ?: Factory::getSettingsRestore()->isRestoreAllowCrossDomain());
		$queue_item = QueueItem::prepare();
		$queue_item->setType(Queue::QUEUE_TYPE_REINDEX);
		$queue_item->setItemId($this->getId());
		$queue_item->setItemData($reindex);

		Queue::addToQueue($queue_item);
	}

	/**
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	private function _findPathByType(): array {
		return Destination::query()
		           ->where([ Destination::TYPE, "=", $this->getType()])
		           ->where([Destination::PATH, '=', $this->getPath()])
					->where([JetBackup::ID_FIELD, '!=', $this->getId()])
		           ->getQuery()
		           ->fetch();
	}

	/**
	 * @return void
	 * @throws DBException
	 * @throws DestinationException
	 * @throws InvalidArgumentException
	 * @throws ValidationException
	 * @throws \SleekDB\Exceptions\IOException|FieldsValidationException
	 */
	public function validateFields():void {

		if(!$this->getName()) throw new FieldsValidationException("Name is required");
		if(!$this->getChunkSize()) throw new FieldsValidationException("Chunk size is required");
		if(!$this->getPath()) throw new FieldsValidationException("Backup directory cannot be empty");
		if(sizeof($this->_findPathByType())) throw new FieldsValidationException("Destination path '{$this->getPath()}' for destination type '{$this->getType()}' already in use");

		if (preg_match('#^[^/.a-zA-Z0-9_-]+$#', $this->getPath()))
			throw new FieldsValidationException('Backup directory name can only contain letters, numbers, periods, underscores, dashes and slashes.');

		if (preg_match('#(/\.\./|^\.\./|/\.{2}/|/\.\.$)#', $this->getPath()))
			throw new FieldsValidationException('Backup directory contains invalid directory traversal patterns.');

		if($this->getId()) {
			$destination = new Destination($this->getId());
			if($destination->getPath() != $this->getPath()) throw new FieldsValidationException("Cannot change backup directory for an active destination");
		}

		$this->getInstance()->validateFields();
	}

	/**
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 * @throws DestinationException
	 */
	public function getDisplay(): array {

		$jobs_assigned = count(BackupJob::query()
			->where([ BackupJob::DESTINATIONS, "contains", $this->getId()])
			->where([BackupJob::HIDDEN, '=', false])
			->getQuery()
			->fetch());

		$output = [
			JetBackup::ID_FIELD => $this->getId(),
			self::UNIQUE_ID     => $this->getUniqueId(),
			self::NAME          => $this->getName(),
			self::PATH          => $this->getPath(),
			self::TYPE          => $this->getType(),
			self::NOTES         => $this->getNotes(),
			self::ENABLED       => $this->isEnabled(),
			self::READ_ONLY     => $this->isReadOnly(),
			self::FREE_DISK     => $this->getFreeDisk(),
			self::EXPORT_CONFIG => $this->isExportConfig() ? 1 : 0,
			self::OPTIONS       => (array) $this->getOptions(),
			self::CHUNK_SIZE    => $this->getChunkSize(),
			self::DEFAULT    => $this->isDefault(),
			self::JOBS_ASSIGNED => $jobs_assigned
		];

		$fields = $this->protectedFields();
		foreach($fields as $field) $output[self::OPTIONS][$field] = self::PROTECTED_FIELD;

		return $output;
	}

	/**
	 * @return array
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function getDisplayCLI(): array {

		$jobs_assigned = count(BackupJob::query()
            ->where([ BackupJob::DESTINATIONS, "contains", $this->getId()])
            ->getQuery()
            ->fetch());

		return [
			'ID'            => $this->getId(),
			'Name'          => $this->getName(),
			'Path'          => $this->getPath(),
			'Type'          => $this->getType(),
			'Notes'         => $this->getNotes(),
			'Enabled'       => $this->isEnabled() ? 'Yes' : 'No',
			'Read Only'     => $this->isReadOnly() ? 'Yes' : 'No',
			'Default'        => $this->isDefault() ? 'Yes' : 'No',
			'Limit Disk'     => $this->getFreeDisk() ? $this->getFreeDisk() . '%' : 'Disabled',
			'Export Config' => $this->isExportConfig() ? 'Yes' : 'No',
			'Jobs Assigned' => $jobs_assigned,
		];
	}
}