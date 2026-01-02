<?php

namespace JetBackup\Cron\Task;

use Exception;
use JetBackup\Alert\Alert;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Data\Engine;
use JetBackup\Destination\Destination;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as DestinationFileAlias;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\Exception\ReindexException;
use JetBackup\Exception\SnapshotMetaException;
use JetBackup\Exception\TaskException;
use JetBackup\Exception\ValidationException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\JetBackupLinux\JetBackupLinux;
use JetBackup\License\License;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemReindex;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Snapshot\SnapshotItem;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Reindex extends Task {

	const LOG_FILENAME = 'reindex';

	private QueueItemReindex $_queue_item_reindex;
	private ?Destination $_destination=null;

	public function __construct() {
		parent::__construct(self::LOG_FILENAME);
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws TaskException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function execute():void {
		parent::execute();

		$this->_queue_item_reindex = $this->getQueueItem()->getItemData();
		$this->_destination = $this->_queue_item_reindex->getDestinationId() ? new Destination($this->_queue_item_reindex->getDestinationId()) : null;

		if($this->_destination && !License::isValid() && !in_array($this->_destination->getType(), Destination::LICENSE_EXCLUDED)) {
			$this->getLogController()->logError("You can't reindex from {$this->_destination->getType()} destination without a license");
			$this->getQueueItem()->updateStatus(Queue::STATUS_ABORTED);
			$this->getQueueItem()->updateProgress('Reindex Aborted!', QueueItem::PROGRESS_LAST_STEP);
			return;
		}

		if($this->getQueueItem()->getStatus() == Queue::STATUS_PENDING) {
			if($this->_destination) $this->getLogController()->logMessage("Starting reindex for destination \"{$this->_destination->getName()}\"");
			else $this->getLogController()->logMessage("Starting reindex for JetBackup Linux");

			$this->getQueueItem()->getProgress()->setTotalItems(count(Queue::STATUS_REINDEX_NAMES)+3);
			$this->getQueueItem()->save();

			$this->getQueueItem()->updateProgress('Starting reindex');
		} else if($this->getQueueItem()->getStatus() > Queue::STATUS_PENDING) {
			$this->getLogController()->logMessage('Resumed Reindex');
		}

		try {

			$this->func([$this, '_checkRequirements']);
			$this->func([$this, '_markSnapshots']);
			$this->func([$this, '_reindexSnapshots']);
			$this->func([$this, '_deleteSnapshots']);

			if($this->getQueueItem()->getStatus() < Queue::STATUS_DONE && !$this->getQueueItem()->getErrors()) $this->getQueueItem()->updateStatus(Queue::STATUS_DONE);
			else $this->getQueueItem()->updateStatus(Queue::STATUS_PARTIALLY);
			$this->getLogController()->logMessage('Completed!');
		} catch(ReindexException $e) {
			$this->getQueueItem()->updateStatus(Queue::STATUS_FAILED);
			$this->getLogController()->logError($e->getMessage());
			$this->getLogController()->logMessage('Failed!');
		}

		$this->getQueueItem()->updateProgress(
			$this->getQueueItem()->getStatus() == Queue::STATUS_DONE
				? 'Reindex Completed!'
				: ($this->getQueueItem()->getStatus() == Queue::STATUS_PARTIALLY
				? 'Completed with errors (see logs)'
				: 'Reindex Failed!'),
			QueueItem::PROGRESS_LAST_STEP
		);

		$this->getLogController()->logMessage('Total time: ' . $this->getExecutionTimeLimit());
	}

	public function _checkRequirements() {
		if($this->_destination) return;
		
		if(!Factory::getSettingsGeneral()->isJBIntegrationEnabled()) throw new ReindexException("JetBackup Linux integration isn't enabled");
		if(!JetBackupLinux::isInstalled()) throw new ReindexException("JetBackup Linux isn't installed on this server");

		try {
			JetBackupLinux::checkRequirements();
		} catch (JetBackupLinuxException $e) {
			throw new ReindexException($e->getMessage());
		}
	}
	
	/**
	 * @return void
	 * @throws ReindexException
	 */
	public function _deleteSnapshots() {

		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_REINDEX_DELETE_SNAPSHOTS);
		$this->getLogController()->logMessage("Removing all unneeded snapshots");

		try {

			// remove all snapshots with reindex flag
			if($this->_destination) {
				$list = Snapshot::query()
			        ->where([Snapshot::DESTINATION_ID, '=', $this->_destination->getId()])
			        ->where([Engine::ENGINE, '=', Engine::ENGINE_WP])
			        ->where([Snapshot::REINDEX, '=', true])
			        ->getQuery()
			        ->fetch();
			} else {
				$list = Snapshot::query()
			        ->where([Engine::ENGINE, '=', Engine::ENGINE_JB])
			        ->where([Snapshot::REINDEX, '=', true])
			        ->getQuery()
			        ->fetch();
			}

			if (empty($list)) return;

			foreach ($list as $item) {
				$snapshot = new Snapshot($item[JetBackup::ID_FIELD]);
				if(!$snapshot->getId()) continue;
				$snapshot->delete();
			}

		} catch( Exception $e) {
			throw new ReindexException($e->getMessage());
		}
	}

	/**
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	private function _handleSGBSnapshot($filename) {

		$this->getLogController()->logDebug("[_handleSnapshot] Filename: $filename");

		try {
			$stat = $this->_destination->getInstance()->getFileStat($filename);
			$this->getLogController()->logDebug("[_handleSnapshot] getFileStat: " . print_r($stat, true));

		} catch( Exception $e) {
			$this->getLogController()->logError("[_handleSnapshot] Failed getting snapshot stats. Error: " . $e->getMessage());
			return;
		}

		$name = substr($stat->getName(), 0, -5);
		$created = $stat->getModifyTime() ?? 0;
		$size = $stat->getSize() ?? 0;

		$this->getLogController()->logDebug("[_handleSnapshot] Name: $name");
		$this->getLogController()->logDebug("[_handleSnapshot] Created: $created");
		$this->getLogController()->logDebug("[_handleSnapshot] Size: $size");

		$this->getLogController()->logMessage("");
		$this->getLogController()->logMessage("\tLegacy Snapshot found \"$name\"");


		try {
			$details = Snapshot::query()
				->where([Snapshot::NAME, '=', $name])
				->where([ Engine::ENGINE, '=', Engine::ENGINE_SGB])
				->where([Snapshot::DESTINATION_ID, '=', $this->_destination->getId()])
				->getQuery()
				->first();
		} catch( Exception $e) {
			$this->getLogController()->logError("[_handleSnapshot] Failed importing snapshot. Error: " . $e->getMessage());
			return;
		}

		if($details) {
			$snapshot = new Snapshot($details[JetBackup::ID_FIELD]);
			$this->getLogController()->logMessage("\t[_handleSnapshot] Snapshot found on local database {$snapshot->getName()}");
			$snapshot->setCreated($created);
			$snapshot->setBackupType(BackupJob::TYPE_ACCOUNT);
			$snapshot->setContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL);
			$snapshot->setStructure(BackupJob::STRUCTURE_COMPRESSED);
			$snapshot->setReindex(false);
			$snapshot->save();
			return;
		}

		$this->getLogController()->logMessage("\t[_handleSnapshot] Snapshot not exist on local database");
		$snapshot = new Snapshot();
		$snapshot->setBackupType(BackupJob::TYPE_ACCOUNT);
		$snapshot->setContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL);
		$snapshot->setStructure(BackupJob::STRUCTURE_COMPRESSED);
		$snapshot->setDestinationId($this->_destination->getId());
		$snapshot->setName($name);
		$snapshot->setCreated($created);
		$snapshot->setSize($size);
		$snapshot->setEngine(Engine::ENGINE_SGB);
		$snapshot->save();
		
		$item = new SnapshotItem();
		$item->setParentId($snapshot->getId());
		$item->setBackupType(BackupJob::TYPE_ACCOUNT);
		$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL);
		$item->setName($name);
		$item->setCreated($created);
		$item->setSize($size);
		$item->setPath($filename);
		$item->setEngine(Engine::ENGINE_SGB);
		$item->save();
	}
	
	/**
	 * @param $identifier
	 * @param $name
	 *
	 * @return void
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	private function _handleSnapshot($identifier, $name) {
		$this->getLogController()->logMessage("");
		$this->getLogController()->logMessage("\tSnapshot found \"$name\"");

		$meta_filepath = sprintf(Snapshot::META_FILEPATH, JetBackup::SEP . $identifier . JetBackup::SEP . $name);
		$this->getLogController()->logDebug("[_handleSnapshot] Identifier: $identifier");
		$this->getLogController()->logDebug("[_handleSnapshot] Meta path: $meta_filepath");
		try {
			if(!$this->_destination->fileExists($meta_filepath)) throw new DestinationException("can't find meta file $meta_filepath");
		} catch(DestinationException $e) {
			$this->getLogController()->logError("[_handleSnapshot] Failed importing snapshot. Error: " . $e->getMessage());
			$this->getQueueItem()->addError();
			return;
		}

		$meta_file = Factory::getLocations()->getTempDir() . JetBackup::SEP . 'dest_reindex_' . Util::generateUniqueId() . '.tmp';

		try {
			$this->_destination->copyFileToLocal($meta_filepath, $meta_file, $this->getQueueItem(), $this);
		} catch(IOException|DestinationException $e) {
			$this->getLogController()->logError("[_handleSnapshot] Failed importing snapshot. Error: failed downloading meta file ({$e->getMessage()})");
			$this->getQueueItem()->addError();
			return;
		}

		try {

			$details = Snapshot::query()
				->where([Snapshot::NAME, '=', $name])
				->where([Engine::ENGINE, '=', Engine::ENGINE_WP])
				->where([Snapshot::DESTINATION_ID, '=', $this->_destination->getId()])
				->where([Snapshot::JOB_IDENTIFIER, '=', $identifier])
				->getQuery()
				->first();
		} catch( Exception $e) {
			$this->getLogController()->logError("[_handleSnapshot] Failed importing snapshot. Error: " . $e->getMessage());
			$this->getQueueItem()->addError();
			return;
		}

		if($details) {
			$snapshot = new Snapshot($details[JetBackup::ID_FIELD]);
			$this->getLogController()->logMessage("\t[_handleSnapshot] Snapshot found on local database {$snapshot->getName()}");
			$snapshot->setReindex(false);
			try {
				$snapshot->removeItems();
			} catch( Exception $e) {
				$this->getLogController()->logError("[_handleSnapshot] Failed removing snapshot items. Error: " . $e->getMessage());
				$this->getQueueItem()->addError();
				return;
			}
		} else {
			$this->getLogController()->logMessage("\t[_handleSnapshot] Snapshot not exist on local database");
			$snapshot = new Snapshot();
			$snapshot->setDestinationId($this->_destination->getId());
			$snapshot->setJobIdentifier($identifier);
		}
		
		try {
			$snapshot->importMeta($meta_file, $this->_queue_item_reindex->isCrossDomain());
		} catch(SnapshotMetaException $e) {
			// Can't import snapshot, there is a missing data in meta file
			$this->getLogController()->logError("Failed importing snapshot. Error: " . $e->getMessage());
			$this->getQueueItem()->addError();
			unlink($meta_file);
			return;
		}

		unlink($meta_file);

		$snapshot->save();

		$this->getLogController()->logMessage("\tSnapshot imported successfully");
		$this->getLogController()->logDebug("Snapshot data: " . print_r($snapshot->getDisplay(), true));
	}

	/**
	 * @param $identifier
	 *
	 * @return array
	 * @throws ReindexException
	 */
	public function _fetchSnapshots($identifier): array {

		$this->getLogController()->logDebug("[_fetchSnapshots] Identifier: /$identifier/");

		try {
			$list = $this->_destination->listDir("/$identifier/");
		} catch(DestinationException $e) {
			throw new ReindexException($e->getMessage());
		}

		$snapshots = [];

		while($list->hasNext()) {
			$name = $list->getNext()->getName();
			$this->getLogController()->logDebug("[_fetchSnapshots] Name: $name");
			if(!preg_match(Snapshot::SNAPSHOT_NAME_REGEX, $name)) continue;
			$snapshots[] = $name;
		}

		return $snapshots;
	}

	/**
	 * @param $key
	 * @param $backup
	 *
	 * @return void
	 * @throws DBException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws Exception
	 */
	public function _handleJBSnapshot($key, $backup) {
		if(!$backup['items']) return;

		if ($backup['backup_structure'] != JetBackupLinux::BACKUP_STRUCTURE_INCREMENTAL) {
			$this->getLogController()->logError("[_handleJBSnapshot] Backup ID" . $backup['_id'] . " is not incremental, this type is not supported, skipping");
			return;
		}

		try {
			$details = Snapshot::query()
				->where([Engine::ENGINE, '=', Engine::ENGINE_JB])
				->where([Snapshot::UNIQUE_ID, '=', $backup['_id']])
				->getQuery()
				->first();
		} catch( Exception $e) {
			$this->getLogController()->logError("[_handleJBSnapshot] Failed importing snapshot. Error: " . $e->getMessage());
			return;
		}

		if($details) {
			$this->getLogController()->logMessage("\t[_handleJBSnapshot] Snapshot found on local database, skipping import");
			$snapshot = new Snapshot($details[JetBackup::ID_FIELD]);
			$snapshot->setReindex(false);
			$snapshot->removeItems();
		} else {
			$this->getLogController()->logMessage("\t[_handleJBSnapshot] New snapshot found! Importing to local database...");
			$snapshot = new Snapshot();
			$snapshot->setNotes($backup['notes']);
		}

		$snapshot->setEngine(Engine::ENGINE_JB);
		$snapshot->setBackupType(BackupJob::TYPE_ACCOUNT);
		$snapshot->setUniqueId($backup['_id']);
		$snapshot->setCreated(strtotime($backup['created']));
		$snapshot->setStructure(BackupJob::STRUCTURE_INCREMENTAL);
		$snapshot->setName(sprintf(Snapshot::SNAPSHOT_NAME_PATTERN, Util::date('Y-m-d_His', strtotime($backup['created'])), $backup['_id']));

		$items = [];
		$size = 0;
		$contains = 0;

		foreach($backup['items'] as $item_details) {

			if($item_details['disabled']) {
				$this->getLogController()->logMessage("\t[_handleJBSnapshot] Skipping item " . $item_details['_id'] . " Remote destination disabled");
				continue;
			}

			switch($item_details['backup_contains']) {
				case JetBackupLinux::BACKUP_TYPE_ACCOUNT_HOMEDIR:
					$item = new SnapshotItem();
					$item->setEngine(Engine::ENGINE_JB);
					$item->setName($item_details['name']);
					$item->setPath($item_details['path']);
					$item->setUniqueId($item_details['_id']);
					$item->setCreated(strtotime($item_details['created']));
					$item->setBackupType(BackupJob::TYPE_ACCOUNT);
					$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR);
					$size += intval($item_details['size']);
					$items[] = $item;
					$contains |= BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR;
				break;

				case JetBackupLinux::BACKUP_TYPE_ACCOUNT_DATABASES:

					if($item_details['name'] != DB_NAME) continue 2;

					$item = new SnapshotItem();
					$item->setEngine(Engine::ENGINE_JB);
					$item->setName($item_details['name']);
					$item->setPath($item_details['path']);
					$item->setUniqueId($item_details['_id']);
					$item->setCreated(strtotime($item_details['created']));
					$item->setBackupType(BackupJob::TYPE_ACCOUNT);
					$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE);
					$size += intval($item_details['size']);

					$items[] = $item;
					$contains |= BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE;
				break;
			}
		}

		if(!$items) {
			$snapshot->delete();
			return;
		}

		if($contains != BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL) {
			$item = new SnapshotItem();
			$item->setEngine(Engine::ENGINE_JB);
			$item->setBackupType(BackupJob::TYPE_ACCOUNT);
			$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL);
			$item->setCreated(strtotime($backup['created']));
			$item->setName('');
			$item->setPath('');
		}

		$items[] = $item;

		$snapshot->setContains($contains);
		$snapshot->setSize($size);
		$snapshot->save();
		
		foreach ($items as $item) {
			$item->setParentId($snapshot->getId());
			$item->save();
		}
	}


	/**
	 * @return void
	 * @throws ReindexException
	 */
	public function _reindexSnapshots() : void {

		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_REINDEX_INDEXING_SNAPSHOTS);
		$this->getQueueItem()->updateProgress('Indexing Snapshots');

		if($this->_destination) {
			
			$this->foreachCallable([ $this, '_fetchRootDirectory' ], [], function($i, $filename) {
				$this->getLogController()->logDebug("Inspecting $filename");
				if(preg_match(BackupJob::IDENTIFIER_REGEX, $filename)) {
					$this->getLogController()->logMessage("Reindexing identifier \"$filename\"");

					$this->foreachCallable([ $this, '_fetchSnapshots'], [$filename], function($s, $name) use ($filename) {
						$this->_handleSnapshot($filename, $name);
					}, 'snapshots_' . $filename);
				} elseif(str_ends_with($filename, BackupJob::SNAPSHOT_SGBP_SUFFIX)) {
					$this->_handleSGBSnapshot($filename); // Anything *.sgbp
				}


			});
		} else {
			
			try {
				$this->foreachCallable(['\JetBackup\JetBackupLinux\JetBackupLinux', 'listBackups'], [], [$this, '_handleJBSnapshot']);
			} catch(JetBackupLinuxException $e) {
				throw new ReindexException($e->getMessage());
			}
		}
	}

	/**
	 * @throws ReindexException
	 */
	public function _markSnapshots():void {

		$this->getLogController()->logMessage('Execution time: ' . $this->getExecutionTimeElapsed());
		$this->getLogController()->logMessage('TTL time: ' . $this->getExecutionTimeLimit());

		$this->getQueueItem()->updateStatus(Queue::STATUS_REINDEX_MARKING_SNAPSHOTS);
		$this->getQueueItem()->updateProgress('Hiding all destination snapshots');
		$this->getLogController()->logMessage("Hiding all destination snapshots");

		try {
			// mark all destination snapshots with reindex flag
			if($this->_destination) {
				Snapshot::query()
			        ->where([Snapshot::DESTINATION_ID, '=', $this->_destination->getId()])
			        ->where([Engine::ENGINE, '=', Engine::ENGINE_WP])
			        ->getQuery()
			        ->update([Snapshot::REINDEX => true]);
			} else {
				Snapshot::query()
			        ->where([Engine::ENGINE, '=', Engine::ENGINE_JB])
			        ->getQuery()
			        ->update([Snapshot::REINDEX => true]);
			}
		} catch( Exception $e) {
			throw new ReindexException($e->getMessage());
		}
	}
	
	/**
	 * @return array
	 * @throws ReindexException
	 */
	public function _fetchRootDirectory():array {

		$this->getLogController()->logMessage("Fetching destination root directory");

		try {
			$this->_destination->setLogController($this->getLogController());
			$this->_destination->validate();
			$list = $this->_destination->listDir('/');
		} catch(ValidationException|DestinationException $e) {
			throw new ReindexException($e->getMessage());
		}

		$output = [];

		while($list->hasNext()) {
			$output[] = $list->getNext()->getName();
		}

		if(!sizeof($output)) $this->getLogController()->logMessage("No files and directories were found on the destination root directory");

		return $output;
	}
}