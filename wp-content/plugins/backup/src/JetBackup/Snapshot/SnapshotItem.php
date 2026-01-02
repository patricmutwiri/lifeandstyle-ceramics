<?php

namespace JetBackup\Snapshot;

use JetBackup\Archive\Archive;
use JetBackup\Archive\Gzip;
use JetBackup\Backup\BackupAccount;
use JetBackup\Backup\BackupConfig;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Cron\Task\Task;
use JetBackup\Data\Engine;
use JetBackup\Data\SleekStore;
use JetBackup\Destination\Destination;
use JetBackup\Entities\Util;
use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\ExecutionTimeException;
use JetBackup\Exception\GzipException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\IOException;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemExtract;
use JetBackup\Wordpress\Blog;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\QueryBuilder;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class SnapshotItem extends Engine {

	const COLLECTION = 'snapshot_item';
	const UNIQUE_ID = 'unique_id';
	const PARENT_ID = 'parent_id';
	const NAME = 'name';
	const PATH = 'path';
	const SIZE = 'size';
	const CREATED = 'created';
	const BACKUP_TYPE = 'backup_type';
	const BACKUP_CONTAINS = 'backup_contains';
	const PARAMS = 'params';

	public function __construct($_id=null) {
		parent::__construct(self::COLLECTION);
		if($_id) $this->_loadById((int) $_id);
	}

	public function setUniqueId(string $id) { $this->set(self::UNIQUE_ID, $id); }
	public function getUniqueId():string { return $this->get(self::UNIQUE_ID); }

	public function setParentId(int $id) { $this->set(self::PARENT_ID, $id); }
	public function getParentId():int { return $this->get(self::PARENT_ID, 0); }

	public function setCreated(int $created) { $this->set(self::CREATED, $created); }
	public function getCreated():int { return $this->get(self::CREATED, 0); }

	public function setSize(int $size) { $this->set(self::SIZE, $size); }
	public function getSize():int { return $this->get(self::SIZE, 0); }

	public function setName(string $name) { $this->set(self::NAME, $name); }
	public function getName():string { return $this->get(self::NAME); }

	public function setPath(string $path) { $this->set(self::PATH, $path); }
	public function getPath():string { return $this->get(self::PATH); }

	public function setBackupType(int $type) { $this->set(self::BACKUP_TYPE, $type); }
	public function getBackupType():int { return $this->get(self::BACKUP_TYPE, 0); }

	public function setBackupContains(int $contains) { $this->set(self::BACKUP_CONTAINS, $contains); }
	public function getBackupContains():int { return $this->get(self::BACKUP_CONTAINS, 0); }

	public function setParams(array $params) { $this->set(self::PARAMS, $params); }
	public function getParams():array { return $this->get(self::PARAMS, []); }
	public function addParam(string $key, $value):void { 
		$params = $this->getParams();
		$params[$key] = $value;
		$this->setParams($params);
	}

	public static function db():SleekStore {
		return new SleekStore(self::COLLECTION);
	}

	public static function query():QueryBuilder {
		return self::db()->createQueryBuilder();
	}

	public function save():void {
		if(!$this->getUniqueId()) $this->setUniqueId(Util::generateUniqueId());
		parent::save();
	}

	/**
	 * @param string $target
	 * @param LogController|null $logController
	 * @param Snapshot|null $snapshot
	 * @param Destination|null $destination
	 * @param QueueItem|null $queue_item
	 * @param Task|null $task
	 *
	 * @return void
	 * @throws DestinationException
	 * @throws IOException
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException|ExecutionTimeException
	 */
	public function download(string $target, ?LogController $logController=null, ?Snapshot $snapshot=null, ?Destination $destination=null, ?QueueItem $queue_item=null, ?Task $task=null) {

		if($this->getEngine() == Engine::ENGINE_JB) throw new IOException("You can't download JetBackup Linux snapshots");

		if(
			($this->getBackupType() == BackupJob::TYPE_ACCOUNT && $this->getBackupContains() == BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL && $this->getEngine() == Engine::ENGINE_WP) ||
			($this->getBackupType() == BackupJob::TYPE_CONFIG && $this->getBackupContains() == BackupJob::BACKUP_CONFIG_CONTAINS_FULL)
		) return;
		
		if(!$logController) $logController = new LogController();
		if(!$snapshot) $snapshot = new Snapshot($this->getParentId());
		if(!$destination) {
			$destination = new Destination($snapshot->getDestinationId());
			$destination->setLogController($logController);
		}

		if($this->getEngine() == Engine::ENGINE_SGB) $source = $this->getPath();
		else $source = $snapshot->getJobIdentifier() . '/' . $snapshot->getName() . '/' . $this->getPath();
		$target .= JetBackup::SEP . dirname($this->getPath());
		if(!file_exists($target)) @mkdir($target, 0700, true);

		$target .= JetBackup::SEP . basename($this->getPath());
		
		$logController->logMessage("Downloading " . $this->getPath() . " to $target");

		if($queue_item) {
			$queue_item->getProgress()->setSubMessage($this->getPath());
			$queue_item->save();
		}

		if (!$destination->fileExists($source)) {
			$logController->logError("File $source does not exist");
			return;
		}
		$destination->copyFileToLocal($source, $target, $queue_item, $task);
	}

	/**
	 * @param $source
	 * @param LogController|null $logController
	 * @param callable|null $callback
	 * @param array $excludes
	 * @param array $includes
	 *
	 * @return void
	 * @throws ArchiveException
	 * @throws GzipException
	 */
	public function extract($source, ?LogController $logController=null, ?callable $callback=null, array $excludes=[], array $includes=[]) {

		if(
			($this->getBackupType() == BackupJob::TYPE_ACCOUNT && $this->getBackupContains() == BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL) ||
			($this->getBackupType() == BackupJob::TYPE_CONFIG && $this->getBackupContains() == BackupJob::BACKUP_CONFIG_CONTAINS_FULL)
		) return;

		if(!$logController) $logController = new LogController();

		$filename = $source . JETBackup::SEP . $this->getPath();
		$target = dirname($filename);

		//$logController->logMessage('Preparing ' .basename($filename) . ' for extraction/decompressing');

		if(Archive::isGzCompressed($filename)) {
			if(file_exists($filename)) {
				$logController->logMessage("\tDecompressing $filename");
				Gzip::decompress($filename, $callback);
			}
			$filename = substr($filename, 0, -3);
		}

		if(file_exists($filename) && Archive::isTar($filename)) {
			$logController->logMessage("\tExtracting $filename");
			$archive = new Archive($filename);
			$archive->setLogController($logController);
			if($callback) $archive->setExtractFileCallback($callback);

			if($excludes) $archive->setExcludeCallback(function($path, $is_dir) use($excludes) {
				foreach($excludes as $exclude) if(fnmatch($exclude, $path) || ($is_dir && str_ends_with($exclude, '/') && fnmatch(substr($exclude, 0, -1), $path))) return true;
				return false;
			});

			if($includes) $archive->setExcludeCallback(function($path, $is_dir) use($includes) {
				foreach($includes as $include) if(fnmatch($include, $path) || ($is_dir && str_ends_with($include, '/') && fnmatch(substr($include, 0, -1), $path))) return false;
				return true;
			});

			$archive->extract($target);
			@unlink($filename);
		}
	}

	public function exportMeta():array {
		return [
			self::NAME              => $this->getName(),
			self::PATH              => $this->getPath(),
			self::SIZE              => $this->getSize(),
			self::CREATED           => $this->getCreated(),
			self::BACKUP_TYPE       => $this->getBackupType(),
			self::BACKUP_CONTAINS   => $this->getBackupContains(),
			self::PARAMS            => $this->getParams(),
		];
	}

	public function importMeta(object $data):void {
		$this->setName($data->{self::NAME});
		$this->setPath($data->{self::PATH});
		$this->setSize($data->{self::SIZE} ?? 0);
		$this->setCreated($data->{self::CREATED});
		$this->setParams((array) $data->{self::PARAMS});
		$this->setBackupType($data->{self::BACKUP_TYPE});
		$this->setBackupContains($data->{self::BACKUP_CONTAINS});
	}

	public function getDisplay(): array {
		return [
			JetBackup::ID_FIELD     => $this->getId(),
			self::PARENT_ID         => $this->getParentId(),
			self::UNIQUE_ID         => $this->getUniqueId(),
			self::NAME              => $this->getName(),
			self::PATH              => $this->getPath(),
			self::SIZE              => $this->getSize(),
			self::CREATED           => $this->getCreated(),
			self::BACKUP_TYPE       => $this->getBackupType(),
			self::BACKUP_CONTAINS   => $this->getBackupContains(),
			self::PARAMS            => $this->getParams(),
		];
	}
}