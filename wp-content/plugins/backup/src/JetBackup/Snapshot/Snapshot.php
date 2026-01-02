<?php

namespace JetBackup\Snapshot;

use Exception;
use JetBackup\Archive\Archive;
use JetBackup\BackupJob\BackupJob;
use JetBackup\CLI\CLI;
use JetBackup\Cron\Task\Task;
use JetBackup\Data\Engine;
use JetBackup\Data\SleekStore;
use JetBackup\Destination\Destination;
use JetBackup\Entities\Util;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\GzipException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\QueueException;
use JetBackup\Exception\SnapshotMetaException;
use JetBackup\Export\Vendor\Vendor;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemDownload;
use JetBackup\Queue\QueueItemExport;
use JetBackup\Queue\QueueItemExtract;
use JetBackup\Queue\QueueItemRestore;
use JetBackup\ResumableTask\ResumableTask;
use JetBackup\Schedule\Schedule;
use JetBackup\UserInput\UserInput;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\QueryBuilder;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Snapshot extends Engine {

	const COLLECTION = 'snapshots';
	
	const UNIQUE_ID = 'unique_id';
	const NAME = 'name';
	const VERSION = 'version';
	const BACKUP_TYPE = 'backup_type';
	const NOTES = 'notes';
	const CREATED = 'created';
	const CONTAINS = 'contains';
	const STRUCTURE = 'structure';
	const SIZE = 'size';
	const REINDEX = 'reindex';
	const LOCKED = 'locked';
	const DELETED = 'deleted';
	const SCHEDULES = 'schedules';
	const SCHEDULES_NAMES = 'schedules_names';
	const DESTINATION_ID = 'destination_id';
	const DESTINATION_NAME = 'destination_name';
	const JOB_IDENTIFIER = 'job_identifier';
	const ITEMS = 'items';
	const PARAMS = 'params';

	const SKELETON_DATABASE_DIRNAME = 'database';
	const SKELETON_LOG_DIRNAME = 'log';
	const SKELETON_LOG_FILENAME = 'task.log.gz';
	const SKELETON_TEMP_DIRNAME = 'tmp';
	const SKELETON_META_DIRNAME = 'meta';
	const SKELETON_CONFIG_DIRNAME = 'config';
	const SKELETON_FILES_DIRNAME = 'files';
	const SKELETON_FILES_ARCHIVE_NAME = 'files' . Archive::ARCHIVE_EXT;
	const SKELETON_META_FILENAME = 'meta.json';
	const SKELETON_FILES_LIST_FILENAME = 'files.list';

	const META_MIN_VERSION = '1.0.3';
	const META_VERSION = '1.0.3';
	const META_FILEPATH = '%s' . JetBackup::SEP . self::SKELETON_META_DIRNAME . JetBackup::SEP . self::SKELETON_META_FILENAME;
	
	const LOG_FILEPATH = '%s' . JetBackup::SEP . self::SKELETON_LOG_DIRNAME . JetBackup::SEP . self::SKELETON_LOG_FILENAME;

	const SNAPSHOT_NAME_PATTERN = 'snap_%s_%s';
	const SNAPSHOT_NAME_REGEX = "/^snap_([\d]{4}-[\d]{2}-[\d]{2})_([\d]{6})_(.*)$/";

	const PARAM_MULTISITE = 'multisite';
	const PARAM_SITE_URL = 'site_url';
	const PARAM_DB_PREFIX = 'db_prefix';
	const PARAM_DB_EXCLUDED = 'db_excluded';

	/**
	 * @var SnapshotItem[]
	 */
	private array $_items=[];
	
	public function __construct($_id=null) {
		parent::__construct(self::COLLECTION);
		if($_id) $this->_loadById((int) $_id);
	}

	public function setUniqueId($id) { $this->set(self::UNIQUE_ID, $id); }
	public function getUniqueId():string { return $this->get(self::UNIQUE_ID); }

	public function setCreated(int $created) { $this->set(self::CREATED, $created); }
	public function getCreated():int { return $this->get(self::CREATED, 0); }

	public function setName(string $name) { $this->set(self::NAME, $name); }
	public function getName():string { return $this->get(self::NAME); }

	public function setBackupType(int $type) { $this->set(self::BACKUP_TYPE, $type); }
	public function getBackupType() : int { return $this->get(self::BACKUP_TYPE, 0); }

	public function setDestinationId(int $_id) { $this->set(self::DESTINATION_ID, $_id); }
	public function getDestinationId():int { return $this->get(self::DESTINATION_ID, 0); }

	public function getDestinationName():string {return (new Destination($this->getDestinationId()))->getName();}

	public function setJobIdentifier(string $identifier) { $this->set(self::JOB_IDENTIFIER, $identifier); }
	public function getJobIdentifier():string { return $this->get(self::JOB_IDENTIFIER); }

	public function setSize(int $size) { $this->set(self::SIZE, $size); }
	public function getSize():int { return (int) $this->get(self::SIZE, 0); }

	public function setStructure(int $structure) { $this->set(self::STRUCTURE, $structure); }
	public function getStructure():int { return (int) $this->get(self::STRUCTURE, 0); }

	public function setContains(int $contains) { $this->set(self::CONTAINS, $contains); }
	public function getContains():int { return (int) $this->get(self::CONTAINS, 0); }

	public function setNotes(string $notes) { $this->set(self::NOTES, $notes); }
	public function getNotes():string { return $this->get(self::NOTES); }

	public function setSchedules(array $schedules) { $this->set(self::SCHEDULES, $schedules); }
	public function getSchedules(): array {return $this->get(self::SCHEDULES, []);}

	/**
	 * @return array
	 */
	public function getSchedulesNamesByType(): array {
		$schedules = $this->getSchedules();
		$output = [];
		if (!empty($schedules)) {
			foreach ($schedules as $schedule_type) {
				$output[] = [
					'schedule_id' => $schedule_type,
					'schedule_name' => Schedule::TYPE_NAMES[$schedule_type] ?? 'Unknown',
				];
			}
		}
		return $output;
	}


	public function setReindex(bool $reindex) { $this->set(self::REINDEX, $reindex); }
	public function isReindex():bool { return $this->get(self::REINDEX, false); }

	public function setLocked(bool $locked) { $this->set(self::LOCKED, $locked); }
	public function isLocked():bool { return $this->get(self::LOCKED, false); }

	public function setDeleted(int $deleted) { $this->set(self::DELETED, $deleted); }
	public function getDeleted():int { return $this->get(self::DELETED, 0); }
	public function isDeleted():bool { return $this->getDeleted() > 0 && $this->getDeleted() <= time(); }

	public function setParams(array $params) { $this->set(self::PARAMS, $params); }
	public function getParams():array { return $this->get(self::PARAMS, []); }
	public function getParam(string $key, $default=null) {
		$params = $this->getParams();
		return $params[$key] ?? $default;
	}
	public function addParam(string $key, $value):void {
		$params = $this->getParams();
		$params[$key] = $value;
		$this->setParams($params);
	}

	public function setItems(array $items) { $this->_items = $items; }
	public function addItem(SnapshotItem $item) { $this->_items[] = $item; }

	/**
	 * @return SnapshotItem[]
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException|DBException
	 */
	public function getItems():array {
		
		if($this->_items) return $this->_items;

		$output = [];
		
		$list = SnapshotItem::query()
			->where([SnapshotItem::PARENT_ID, '=', $this->getId()])
            ->orderBy([SnapshotItem::NAME =>'asc'])
			->getQuery()
			->fetch();

		foreach($list as $item_details) $output[] = new SnapshotItem($item_details[JetBackup::ID_FIELD]);

		return $output; 
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function removeItems() {
		SnapshotItem::query()
			->where([SnapshotItem::PARENT_ID, '=', $this->getId()])
			->getQuery()
			->delete();
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
	 * @throws DBException
	 * @throws GzipException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function extract($source, ?LogController $logController=null, ?callable $callback=null, array $excludes=[],  array $includes=[]) {

		if(!($items = $this->getItems())) return;

		if(!$logController) $logController = new LogController();

		foreach($items as $item) $item->extract($source, $logController, $callback, $excludes, $includes);
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function delete():void {
		parent::delete();
		
		SnapshotItem::query()
			->where([SnapshotItem::PARENT_ID, '=', $this->getId()])
			->getQuery()
			->delete();
	}
	
	public function isCompressed():bool { return $this->getStructure() == BackupJob::STRUCTURE_COMPRESSED; }
	
	public static function generateName(): string {
		return sprintf(self::SNAPSHOT_NAME_PATTERN, Util::date('Y-m-d_His'), Util::generateUniqueId());
	}
	
	public function save():void {
		if(!$this->getUniqueId()) $this->setUniqueId(Util::generateUniqueId());
		parent::save();
		
		foreach($this->_items as $item) {
			$item->setParentId($this->getId());
			$item->save();
		}
	}

	public static function db():SleekStore {
		return new SleekStore(self::COLLECTION);
	}

	public static function query():QueryBuilder {
		return self::db()->createQueryBuilder();
	}

	/**
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public static function getTotalSnapshots():int {
		return count(self::query()
			->select([JetBackup::ID_FIELD])
			->getQuery()
			->fetch());
	}

	/**
	 * @param int $type
	 *
	 * @return void
	 */
	public function addScheduleByType(int $type):void {
		$types = $this->getSchedules();
		if(in_array($type, $types)) return;
		$types[] = $type;
		$this->setSchedules($types);
	}

	/**
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws QueueException
	 * @throws InvalidArgumentException
	 */
	public function addToDownloadQueue() {
		$download = new QueueItemDownload();
		$download->setSnapshotId($this->getId());

		$queue_item = QueueItem::prepare();
		$queue_item->setType(Queue::QUEUE_TYPE_DOWNLOAD);
		$queue_item->setItemId($this->getId());
		$queue_item->setItemData($download);

		Queue::addToQueue($queue_item);
	}

    /**
     * @throws \SleekDB\Exceptions\IOException
     * @throws QueueException
     * @throws InvalidArgumentException
     */

    public function addToDownloadLogQueue() {
        $download = new QueueItemDownload();
        $download->setSnapshotId($this->getId());

        $queue_item = QueueItem::prepare();
        $queue_item->setType(Queue::QUEUE_TYPE_DOWNLOAD_BACKUP_LOG);
        $queue_item->setItemId($this->getId());
        $queue_item->setItemData($download);

        Queue::addToQueue($queue_item);
    }

	/**
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws QueueException
	 * @throws InvalidArgumentException
	 */
	private static function _addToRestoreQueue(int $id=0, string $path='', int $options=0, array $exclude=[],  array $include=[], array $exclude_db=[], array $include_db=[], array $filemanager=[]) {
		$restore = new QueueItemRestore();

		// these file cannot be restored, we can cut our own branch
		$exclude[] = 'wp-config.php';
		$exclude[] = '.htaccess';

		if($id) $restore->setSnapshotId($id);
		elseif($path) {
			if(!file_exists($path) || !is_readable($path)) throw new QueueException("The provided snapshot path not exists");
			if( !Archive::isGzCompressed($path) && !Archive::isTar($path)) throw new QueueException("The provided snapshot path is not a tar.gz file");
			$restore->setSnapshotPath($path);
		} else throw new QueueException("You must provide snapshot id or path");

		// in multisite restore, when restoring subsites $includes will always have values
		if (Factory::getSettingsRestore()->isRestoreWpContentOnlyEnabled() && empty($include)) {
			$exclude = [];
			$include[] = Wordpress::WP_CONTENT . JetBackup::SEP . '*';
		}

		$restore->setOptions($options);
		$restore->setExcludes($exclude);
		$restore->setIncludes($include);
		$restore->setExcludedDatabases($exclude_db);
		$restore->setIncludedDatabases($include_db);
		$restore->setFileManager($filemanager);
		
		$queue_item = QueueItem::prepare();
		$queue_item->setType(Queue::QUEUE_TYPE_RESTORE);
		$queue_item->setItemData($restore);

		Queue::addToQueue($queue_item);
	}

	/**
	 * @param $path
	 * @param int $options
	 * @param array $exclude
	 * @param array $exclude_db
	 * @param array $include_db
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public static function addToRestoreQueueByPath($path, int $options= QueueItemRestore::OPTION_RESTORE_FILES_ENTIRE | QueueItemRestore::OPTION_RESTORE_DATABASE_ENTIRE, array $exclude=[], array $exclude_db=[], array $include_db=[]) {
		$include = []; // placeholder for future implementation
		self::_addToRestoreQueue(0, $path, $options, $exclude, $include, $exclude_db, $include_db);
	}

	/**
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function addToRestoreQueue(int $options=QueueItemRestore::OPTION_RESTORE_FILES_ENTIRE | QueueItemRestore::OPTION_RESTORE_DATABASE_ENTIRE, array $exclude=[], array $include=[], array $exclude_db=[], array $include_db=[], array $filemanager=[]) {
		self::_addToRestoreQueue($this->getId(), '', $options, $exclude, $include, $exclude_db, $include_db, $filemanager);
	}

	/**
	 * @param int $type
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function addToExportQueue(int $type) {

		if (!$type || !in_array($type, Vendor::ALL_VENDORS)) throw new QueueException("Invalid Panel type");

		$export = new QueueItemExport();
		$export->setType($type);
		$export->setSnapshotId($this->getId());

		$queue_item = QueueItem::prepare();
		$queue_item->setType(Queue::QUEUE_TYPE_EXPORT);
		$queue_item->setItemId($this->getId());
		$queue_item->setItemData($export);

		Queue::addToQueue($queue_item);
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws QueueException
	 */
	public function addToExtractQueue($extract_path=null): void {
		$extract = new QueueItemExtract();
		$extract->setSnapshotId($this->getId());
		if($extract_path) $extract->setExtractPath($extract_path);

		$queue_item = QueueItem::prepare();
		$queue_item->setType(Queue::QUEUE_TYPE_EXTRACT);
		$queue_item->setItemId($this->getId());
		$queue_item->setItemData($extract);

		Queue::addToQueue($queue_item);
	}
	
	/**
	 * @param int $type
	 *
	 * @return void
	 */
	public function removeSchedule(int $type):void {
		if($type < 1) return;
		$types = $this->getSchedules();
		$offset = array_search($type, $types);
		if($offset === false) return;
		array_splice($types, $offset, 1);
		$this->setSchedules($types);
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public static function getTotalSnapshotsSize():int {
		$list = self::query()
			->where([Engine::ENGINE, '=', Engine::ENGINE_WP])
			->select([self::SIZE])
			->getQuery()
			->fetch();

		$size = 0;
		foreach ($list as $item) $size += $item[self::SIZE];
		return $size;
	}

	/**
	 * @param int $destinationID
	 *
	 * @return void
	 * @throws AjaxException
	 * @throws DBException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public static function deleteByDestinationID(int $destinationID):void {

		$snapshots = Snapshot::query()
		                     ->select([JetBackup::ID_FIELD])
		                     ->where([Snapshot::DESTINATION_ID, '=', $destinationID])
		                     ->getQuery()
		                     ->fetch();

		if(empty($snapshots)) return;

		foreach ($snapshots as $snapshotID) {
			$snapshot = new Snapshot($snapshotID[JetBackup::ID_FIELD]);
			if(!$snapshot->getId()) throw new AjaxException("Invalid snapshot id provided");
			$snapshot->delete();
		}

	}

	/**
	 * @return array
	 */
	public function getDisplay(): array {
		return [
			JetBackup::ID_FIELD     => $this->getId(),
			self::UNIQUE_ID         => $this->getUniqueId(),
			self::DESTINATION_ID    => $this->getDestinationId(),
			self::DESTINATION_NAME  => $this->getDestinationName(),
			self::NAME              => $this->getName(),
			self::BACKUP_TYPE       => $this->getBackupType(),
			Engine::ENGINE          => $this->getEngine(),
			self::CONTAINS          => $this->getContains(),
			self::STRUCTURE         => $this->getStructure(),
			self::SCHEDULES         => $this->getSchedules(),
			self::SCHEDULES_NAMES   => $this->getSchedulesNamesByType(),
			self::NOTES             => $this->getNotes(),
			self::PARAMS            => $this->getParams(),
			self::CREATED           => $this->getCreated(),
			self::SIZE              => Util::bytesToHumanReadable($this->getSize()),
			self::LOCKED            => $this->isLocked() ? 1 : 0,
			self::DELETED           => $this->getDeleted()
		];
	}

	/**
	 * @return array
	 */
	public function getDisplayCLI(): array {
		return [
			'ID'     => $this->getId(),
			'Destination ID'    => $this->getDestinationId(),
			'Name'              => $this->getName(),
			'Backup Type'       => $this->getBackupType(),
			'Engine'            => $this->getEngineName(),
			'Contains'          => $this->getContains(),
			'Structure'         => $this->getStructure(),
			'Schedules'         => $this->getSchedules(),
			'Notes'             => $this->getNotes(),
			'Created'           => CLI::date($this->getCreated()),
			'Size'              => $this->getSize(),
			'Locked'            => $this->isLocked() ? 'Yes' : 'No',
			'Deleted'           => $this->getDeleted(),
		];
	}

	public function exportMeta($path):void {

		if($this->getEngine() != Engine::ENGINE_WP) 
			throw new SnapshotMetaException("Only snapshot of type WordPress is allowed to be exported");
			
		$items = [];
		foreach($this->_items as $item) $items[] = $item->exportMeta();
		$umask = umask(077);

		file_put_contents(sprintf(self::META_FILEPATH, $path), json_encode([
			self::VERSION           => self::META_VERSION,
			self::NAME              => $this->getName(),
			self::BACKUP_TYPE       => $this->getBackupType(),
			Engine::ENGINE          => $this->getEngine(),
			self::CONTAINS          => $this->getContains(),
			self::STRUCTURE         => $this->getStructure(),
			self::SCHEDULES         => $this->getSchedules(),
			self::JOB_IDENTIFIER    => $this->getJobIdentifier(),
			self::CREATED           => $this->getCreated(),
			self::SIZE              => $this->getSize(),
			self::PARAMS            => $this->getParams(),
			self::ITEMS             => $items,
		]));
		umask($umask);
	}

	/**
	 * @param string $file
	 * @param bool $cross_domain
	 *
	 * @return void
	 * @throws SnapshotMetaException
	 */
	public function importMeta(string $file, bool $cross_domain=false):void {


		$meta = json_decode(file_get_contents($file));

		$current_site_url = Wordpress::getSiteDomain();

		if(isset($meta->version) && version_compare($meta->version, self::META_MIN_VERSION, '>=')) {

			$this->setEngine($meta->{Engine::ENGINE});
			$this->setBackupType($meta->{self::BACKUP_TYPE});
			$this->setCreated($meta->{self::CREATED});
			$this->setName($meta->{self::NAME});
			$this->setContains($meta->{self::CONTAINS});
			$this->setParams((array) $meta->{self::PARAMS});
			$this->setStructure($meta->{self::STRUCTURE});
			$this->setSchedules($meta->{self::SCHEDULES});
			$this->setSize($meta->{self::SIZE});

			$backup_site_url = preg_replace("#^http(s?)://#", "", $this->getParam(self::PARAM_SITE_URL));

			if( !$cross_domain && $backup_site_url != $current_site_url)
				throw new SnapshotMetaException("This snapshot isn't owned by this site, you need to allow cross-domain restores");

			foreach($meta->{self::ITEMS} as $item_meta) {
				$item = new SnapshotItem();
				$item->importMeta($item_meta);
				$this->addItem($item);
			}

		} elseif(isset($meta->meta_version)) {
			// Support meta file with version 1.0.2 and below

			$backup_site_url = preg_replace("#^http(s?)://#", "", $meta->domain_backup);

			if( !$cross_domain && $backup_site_url != $current_site_url)
				throw new SnapshotMetaException("This snapshot isn't owned by this site, you need to allow cross-domain restores");

			$contains = 0;

			if(isset($meta->job_settings->backup_contains)) {
				$backup_contains = (array) $meta->job_settings->backup_contains;
				if(in_array('homedir', $backup_contains)) $contains |= BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR;
				if(in_array('database', $backup_contains)) $contains |= BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE;
			}

			$this->setEngine(isset($meta->queue_item->engine) ? (Wordpress::strContains($meta->queue_item->engine, '5.303,13.236,5.301,20.758 C91.384,64.08,82.732,78.016,69.871,85.516z') ? Engine::ENGINE_WP : Engine::ENGINE_JB) : Engine::ENGINE_WP);
			$this->setBackupType(BackupJob::TYPE_ACCOUNT);
			$this->setCreated($meta->queue_item->created ?? 0);
			$this->setName(isset($meta->queue_item->snap) && $meta->queue_item->snap ? 'snap_' . $meta->queue_item->snap : '');
			$this->setContains($contains);
			if(isset($meta->backup_structure_name)) $this->setStructure($meta->backup_structure_name == 'Compressed' ? BackupJob::STRUCTURE_COMPRESSED : BackupJob::STRUCTURE_ARCHIVED);
			$this->setSize($meta->size ?? 0);

			$this->addParam(Snapshot::PARAM_MULTISITE, $meta->multisite_details);
			$this->addParam(Snapshot::PARAM_SITE_URL, $meta->domain_backup);
			
			$schedules = [];
			if(isset($meta->queue_item->parent_schedules)) foreach($meta->queue_item->parent_schedules as $schedule_id) $schedules[] = (int) $schedule_id;
			$this->setSchedules($schedules);

			if($this->getContains() & BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR) {
				$item = new SnapshotItem();
				$item->setBackupType(BackupJob::TYPE_ACCOUNT);
				$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR);
				$item->setCreated($meta->queue_item->created);
				$item->setName('');
				$item->setPath('files/files.tar' . ($this->isCompressed() ? '.gz' : ''));
				$item->setSize($meta->size - $meta->db_size);

				$this->addItem($item);
			}

			if(isset($meta->db_items) && is_array($meta->db_items)) {
				foreach ($meta->db_items as $db_item) {
					$item = new SnapshotItem();
					$item->setName($db_item->name ?? '');
					$item->setPath('db_dumps/' . $db_item->name . '.sql' . ($db_item->compressed ? '.gz' : ''));
					$item->setSize($db_item->size ?? 0);
					$item->setCreated($db_item->mtime);
					$item->setBackupType(BackupJob::TYPE_ACCOUNT);
					$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE);

					$item->addParam(Snapshot::PARAM_DB_PREFIX, $meta->db_prefix);
					$item->addParam(Snapshot::PARAM_DB_EXCLUDED,  in_array($db_item->name, $meta->job_settings->exDatabase));
					
					$this->addItem($item);
				}
			}

			$item = new SnapshotItem();
			$item->setBackupType(BackupJob::TYPE_ACCOUNT);
			$item->setBackupContains(BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL);
			$item->setCreated($meta->queue_item->created);
			$item->setName('');
			$item->setPath('');
			$item->setSize(0);

			$item->addParam(Snapshot::PARAM_MULTISITE, $meta->multisite_details);
			$item->addParam(Snapshot::PARAM_SITE_URL, $meta->domain_backup);

			$this->addItem($item);

		} else throw new SnapshotMetaException("Can't find meta file version");

		$this->_validateImportMeta();
	}

	/**
	 * @return void
	 * @throws SnapshotMetaException
	 */
	private function _validateImportMeta():void {
		if(!$this->getName()) throw new SnapshotMetaException("Can't find name in meta file");
		if(!$this->getEngine()) throw new SnapshotMetaException("Can't find engine in meta file");
		if(!$this->getBackupType()) throw new SnapshotMetaException("Can't find backup type in meta file");
		if(!$this->getCreated()) throw new SnapshotMetaException("Can't find creation date in meta file");
		if(!$this->getContains()) throw new SnapshotMetaException("Can't find backup contains in meta file");
		if(!$this->getStructure()) throw new SnapshotMetaException("Can't find backup structure in meta file");
		if(!$this->getSchedules()) throw new SnapshotMetaException("Can't find backup schedules in meta file");
		if(!$this->_items) throw new SnapshotMetaException("Can't find backup items in meta file");
	}
}