<?php

namespace JetBackup\BackupJob;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use Exception;
use JetBackup\Alert\Alert;
use JetBackup\Backup\BackupAccount;
use JetBackup\Backup\BackupConfig;
use JetBackup\CLI\CLI;
use JetBackup\Data\DBObject;
use JetBackup\Data\SleekStore;
use JetBackup\Destination\Destination;
use JetBackup\Destination\Vendors\Local\Local;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\QueueException;
use JetBackup\Exception\ScheduleException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemBackup;
use JetBackup\Schedule\Schedule;
use JetBackup\Schedule\ScheduleItem;
use JetBackup\Snapshot\Snapshot;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\QueryBuilder;

class BackupJob extends DBObject {
	
	const COLLECTION = 'jobs';
	
	const UNIQUE_ID = 'unique_id';
	const NAME = 'name';
	const TYPE = 'type';
	const DEFAULT = 'default';
	const DESTINATIONS = 'destinations';
	const EXCLUDES = 'excludes';
	const IS_FILES_EXCLUDED = 'is_files_excluded';
	const IS_TABLES_EXCLUDED = 'is_tables_excluded';

	const SCHEDULES = 'schedules';
	const SCHEDULE_TIME = 'schedule_time';
	const JOB_MONITOR = 'job_monitor';
	const EXCLUDE_DATABASES = 'database_excludes';
	const DESTINATION_NAMES = 'destination_names';
	const SCHEDULE_NAMES = 'schedule_names';
	const CONTAINS = 'backup_contains';
	const ENABLED = 'enabled';
	const LAST_RUN = 'last_run';
	const NEXT_RUN = 'next_run';
	const HIDDEN = 'hidden';
	
	const DEFAULT_JOB_NAME = 'Default Job';
	const DEFAULT_CONFIG_JOB_NAME = 'Default Config Job';

	const IDENTIFIER_PATTERN = 'job_%d_%s';

	const IDENTIFIER_REGEX = '/^(job_([0-9]{1})_([a-f0-9]{24})|[a-zA-Z0-9]{12})$/';

	const SNAPSHOT_SGBP_SUFFIX = '.sgbp';

	const TYPE_ACCOUNT  = 1;
	const TYPE_CONFIG   = 2;

	const TYPE_NAMES    = [
		self::TYPE_ACCOUNT  => 'Account',
		self::TYPE_CONFIG   => 'Configuration',
	];

	const BACKUP_ACCOUNT_CONTAINS_HOMEDIR = 1;
	const BACKUP_ACCOUNT_CONTAINS_DATABASE = 2;
	const BACKUP_ACCOUNT_CONTAINS_FULL = self::BACKUP_ACCOUNT_CONTAINS_HOMEDIR | self::BACKUP_ACCOUNT_CONTAINS_DATABASE;

	const BACKUP_CONFIG_CONTAINS_CONFIG = 1;
	const BACKUP_CONFIG_CONTAINS_DATABASE = 2;
	const BACKUP_CONFIG_CONTAINS_FULL = self::BACKUP_CONFIG_CONTAINS_CONFIG | self::BACKUP_CONFIG_CONTAINS_DATABASE;

	const STRUCTURE_ARCHIVED    = 1;
	const STRUCTURE_COMPRESSED  = 2;
	const STRUCTURE_INCREMENTAL  = 3;
	
	const DEFAULT_DATABASE_EXCLUDES = [
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
	];

	public function __construct($_id=null) {
		parent::__construct(self::COLLECTION);
		if($_id) $this->_loadById((int) $_id);
    }

	public function getIdentifier():string {
		return sprintf(self::IDENTIFIER_PATTERN, $this->getType(), $this->getUniqueId());
	}
	
	public function setUniqueId($id) { $this->set(self::UNIQUE_ID, $id); }
	public function getUniqueId():string { return $this->get(self::UNIQUE_ID); }

	public function setName(string $name) { $this->set(self::NAME, $name); }
	public function getName():string { return $this->get(self::NAME); }

	public function setType(int $type) { $this->set(self::TYPE, $type); }
	public function setDefault(bool $bool = false) { $this->set(self::DEFAULT, $bool); }
	public function isDefault():bool { return $this->get(self::DEFAULT, false); }

	public function getType():int { return $this->get(self::TYPE, 0); }

	public function setDestinations(array $destinations) { $this->set(self::DESTINATIONS, $destinations); }
	public function getDestinations():array { return $this->get(self::DESTINATIONS, []); }

	public function setExcludes(array $excludes) { $this->set(self::EXCLUDES, $excludes); }
	public function getExcludes():array { return $this->get(self::EXCLUDES, []); }

	public static function getDefaultExcludes($content_dir, $upload_dir): array {

		$content_dir = $content_dir ?: JetBackup::SEP . Wordpress::WP_CONTENT . JetBackup::SEP;
		$upload_dir = $upload_dir ?: JetBackup::SEP . Factory::getWPHelper()->getUploadDir() . JetBackup::SEP;

		return [
			$content_dir . 'Dropbox_Backup',
			$content_dir . 'updraft',
			$content_dir . 'upsupsystic',
			$content_dir . 'wpbackitup_backups',
			$content_dir . 'wpbackitup_restore',
			$content_dir . 'backups',
			$content_dir . 'ai1wm-backups',
			$content_dir . 'cache',
			$content_dir . 'et-cache',
			$content_dir . 'litespeed',
			$content_dir . 'w3tc-config',
			$content_dir . 'wflogs',
			$content_dir . 'wp-rocket-config',
			$content_dir . 'et-temp',
			$content_dir . 'webtoffee_migrations',
			$content_dir . 'WPvivid_Uploads',
			$content_dir . 'wp-reset-autosnapshots',
			$content_dir . 'wpvivid_staging',
			$content_dir . 'backup-db',
			$content_dir . 'as3b_backups',
			$content_dir . 'backups-dup-pro',
			$content_dir . 'managewp' . JetBackup::SEP . 'backups',
			$upload_dir . 'wp-clone',
			$upload_dir . 'wp-staging',
			$upload_dir . 'wp-migrate-db',
			$upload_dir . 'db-backup',
			$upload_dir . 'wordpress-move' . JetBackup::SEP . 'backup',
			$upload_dir . 'backupbuddy_backupsp',
			$upload_dir . 'backupbuddy_temp',
			$upload_dir . 'pb_backupbuddy',
			$upload_dir . 'snapshots',
			$upload_dir . 'prime-mover-export-files',
			$upload_dir . 'prime-mover-lock-files',
			$upload_dir . 'prime-mover-tmp-downloads',
			$upload_dir . 'wpo',
			$upload_dir . 'ithemes-security' . JetBackup::SEP . 'backups',
			$upload_dir . 'jetbackup_converted',
			$upload_dir . 'siteground-optimizer-assets',
            $upload_dir .  'backwpup',
            $upload_dir .  'backwpup-restore',
            '*.log',
			'*' . JetBackup::SEP . 'templates_c' . JetBackup::SEP . '*',
		];
	}

	public function getAllExcludes():array {

		$homedir = Factory::getWPHelper()->getWordPressHomedir();
		$content_dir = JetBackup::SEP . Wordpress::WP_CONTENT . JetBackup::SEP;
		$plugins_dir = Wordpress::WP_PLUGINS . JetBackup::SEP;
		$upload_dir = JetBackup::SEP . Factory::getWPHelper()->getUploadDir() . JetBackup::SEP;

		$excludes = $this->getExcludes();
		if (str_starts_with(Factory::getLocations()->getDataDir(), $homedir)) {
			$excludes[] = JetBackup::SEP . substr(Factory::getLocations()->getDataDir(), strlen($homedir));
		}
		$excludes[] = $content_dir . $plugins_dir . JetBackup::PLUGIN_NAME;

		// when default excludes enabled we will exclude unneeded data from known plugins 
		if (Factory::getSettingsPerformance()->isUseDefaultExcludes()) {
			$excludes = array_merge($excludes, self::getDefaultExcludes($content_dir, $upload_dir));
		}

		if (Factory::getSettingsPerformance()->isExcludeNestedSitesEnabled()) {

			$path = rtrim($homedir, JetBackup::SEP);
			$targets = glob($path . JetBackup::SEP . '*' . JetBackup::SEP . 'wp-config.php');
			
			foreach($targets as $target) {
				$_wp_includes = dirname($target) . JetBackup::SEP . 'wp-includes';
				if (file_exists($_wp_includes)) $excludes[] = substr(dirname($target), strlen($path));
			}
		}

		return $excludes;
	}

	/**
	 * @param ScheduleItem[] $schedules
	 *
	 * @return void
	 */
	public function setSchedules(array $schedules):void {
		$scheduleList = [];
		foreach($schedules as $schedule) $scheduleList[] = $schedule->getData();
		$this->set(self::SCHEDULES, $scheduleList);
	}

	/**
	 * @return ScheduleItem[]
	 */
	public function getSchedules():array {
		$schedules = $this->getScheduleTypes();
		$newSchedules = [];
		foreach($schedules as $schedule) $newSchedules[] = new ScheduleItem((array) $schedule);
		return $newSchedules;
	}

	public function getScheduleTypes():array {
		return $this->get(self::SCHEDULES, []);
	}
	
	public function removeSchedule(int $_id):void {
		if(!$_id) throw new IOException("Invalid schedule id");
		$new = [];
		$schedules = $this->getSchedules();
		foreach($schedules as $schedule) if($schedule->getId() != $_id) $new[] = $schedule;
		$this->setSchedules($new);
	}

	public function addSchedule(ScheduleItem $scheduleItem):void {
		if(!$scheduleItem->getId()) throw new IOException("Invalid schedule id");

		$schedules = $this->getSchedules();

		// validate
		foreach($schedules as $schedule) {
			if((string) $schedule->getId() == (string) $scheduleItem->getId()) {
				throw new IOException("Unable to add new schedule, this id already exists.");
			}
		}

		$schedules[] = $scheduleItem;
		$this->setSchedules($schedules);
	}

	public function updateSchedule(ScheduleItem $scheduleItem):void {

		if(
			!$scheduleItem->getId() ||
			($index = $this->getScheduleIndex($scheduleItem)) === null
		) return;
		
		$schedulesData = [];
		$schedules = $this->getSchedules();
		foreach($schedules as $i => $schedule) {
			if($i == $index) $schedulesData[$i] = $scheduleItem;
			else $schedulesData[$i] = $schedule;
		}
		$this->setSchedules($schedulesData);
	}

	public function getScheduleIndex(ScheduleItem $scheduleItem):?int {
		if(!$scheduleItem->getId()) return null;
		$schedules = $this->getSchedules();
		foreach($schedules as $index => $schedule) if($schedule->getId() == $scheduleItem->getId()) return $index;
		return null;
	}

	/**
	 * @throws JBException
	 */
	public function calculateNextRun():void {
		$schedules = $this->getSchedules();

		foreach($schedules as $schedule) {
			if(!$schedule->getId() || !$schedule->getScheduleInstance()) continue;
			$schedule->setNextRun(null, $this->getScheduleTime());
			$this->updateSchedule($schedule);
		}
	}

	public function getNextSchedule():?ScheduleItem {

		$nextSchedule = new ScheduleItem();
		$schedules = $this->getSchedules();
		foreach($schedules as $schedule) {
			if(
				$schedule->getNextRun() > 0 &&
				(!$nextSchedule->getId() || $schedule->getNextRun() < $nextSchedule->getNextRun())
			) $nextSchedule = $schedule;
		}
		return $nextSchedule->getId() ? $nextSchedule : null;
	}

	public function getScheduleById(int $_id):?ScheduleItem {
		$schedules = $this->getSchedules();
		foreach($schedules as $schedule) if($schedule->getId() == $_id) return $schedule;
		return null;
	}

	/**
	 * @param array|null $newSchedules
	 *
	 * @return array
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws ScheduleException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function updateMultiSchedules(?array $newSchedules=null):array {

		$output = [];

		$oldSchedules = $this->getSchedules();

		if(!isset($newSchedules)) {
			$newSchedules = [];
			foreach($oldSchedules as $oldSchedule) $newSchedules[] = $oldSchedule->getData();
		}

		// reset all schedules
		$this->setSchedules([]);

		foreach($newSchedules as $scheduleDetails) {
			$schedule = new Schedule($scheduleDetails[JetBackup::ID_FIELD]);
			if(!$schedule->getId()) throw new IOException('Invalid schedule provided');
			if($this->getScheduleById($scheduleDetails[JetBackup::ID_FIELD])) throw new IOException('You can\'t provide the same schedule twice. (Id: ' . $scheduleDetails[JetBackup::ID_FIELD] . ')');

			$scheduleItem = new ScheduleItem();
			$scheduleItem->setScheduleInstance($schedule);
			$scheduleItem->setId($scheduleDetails[JetBackup::ID_FIELD]);
			$scheduleItem->setNextRun(null, $this->getScheduleTime());
			$scheduleItem->setRetain($scheduleDetails[ScheduleItem::RETAIN] ?? 0 );

			$this->addSchedule($scheduleItem);

			$schedule->addJobsCount();
			$output[$schedule->getId()] = $schedule;
		}

		foreach($oldSchedules as $schedule) {
			if(!isset($output[$schedule->getId()])) $output[$schedule->getId()] = $schedule->getScheduleInstance();
			$output[$schedule->getId()]->reduceJobsCount();
		}

		return $output;
	}

	public function setScheduleTime($time) { $this->set(self::SCHEDULE_TIME, $time); }
	public function getScheduleTime():string { return $this->get(self::SCHEDULE_TIME, '00:00'); }

	public function setMonitor(int $monitor) { $this->set(self::JOB_MONITOR, $monitor); }
	public function getMonitor():int { return (int) $this->get(self::JOB_MONITOR, 0); }

	public function setExcludeDatabases($database) { $this->set(self::EXCLUDE_DATABASES, $database); }
	public function getExcludeDatabases():array { return $this->get(self::EXCLUDE_DATABASES, []); }

	public function setContains(int $contains):void { $this->set(self::CONTAINS, $contains); }
	public function getContains():int { return (int) $this->get(self::CONTAINS, 0); }
	public function getContainsName():string { 
		$contains = $this->getContains();
		$name = [];
		
		switch ($this->getType()) {
			case self::TYPE_ACCOUNT:
				if($contains == self::BACKUP_ACCOUNT_CONTAINS_FULL) return "Full Account";
				if($contains & self::BACKUP_ACCOUNT_CONTAINS_HOMEDIR) $name[] = "Files";
				if($contains & self::BACKUP_ACCOUNT_CONTAINS_DATABASE) $name[] = "Database";
			break;
			case self::TYPE_CONFIG:
				if($contains == self::BACKUP_CONFIG_CONTAINS_FULL) return "Full Config";
				if($contains & self::BACKUP_CONFIG_CONTAINS_CONFIG) $name[] = "Configs";
				if($contains & self::BACKUP_CONFIG_CONTAINS_DATABASE) $name[] = "Database";
			break;
		}
		
		return implode(", ", $name);
	}

	public function setHidden(bool $hidden) { $this->set(self::HIDDEN, $hidden); }
	public function isHidden():bool { return !!$this->get(self::HIDDEN, false); }
	
	public function setEnabled(bool $enabled) { $this->set(self::ENABLED, $enabled); }
	public function isEnabled():bool { return !!$this->get(self::ENABLED, true); }

	public function getNextRun():int {
		$next_schedule = $this->getNextSchedule();
		return $next_schedule ? $next_schedule->getNextRun() : 0;
	}

	public function setLastRun(int $last_run) { $this->set(self::LAST_RUN, $last_run); }
	public function getLastRun():int { return (int) $this->get(self::LAST_RUN, 0); }
	
	public function save():void {
		if(!$this->getUniqueId()) $this->setUniqueId(Util::generateUniqueId());
		$this->calculateNextRun();
		parent::save();
	}

	public static function db():SleekStore {
		return new SleekStore(self::COLLECTION);
	}

	public static function query():QueryBuilder {
		return self::db()->createQueryBuilder();
	}
	
	/**
	 * @return BackupJob|null
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public static function getDefaultJob():BackupJob {
		$result = self::query()
			->select([JetBackup::ID_FIELD])
			->where([ self::TYPE, "=", self::TYPE_ACCOUNT ])
			->where([ self::DEFAULT, "=", true ])
			->getQuery()
			->first();

		if($result) return new BackupJob($result[JetBackup::ID_FIELD]);

		$config = new BackupJob();
		$config->setType(self::TYPE_ACCOUNT);
		$config->setName(self::DEFAULT_JOB_NAME);
		$config->setDefault(true);
		$config->setDestinations([Destination::getDefaultDestination()->getId()]);
		$config->setContains(self::BACKUP_ACCOUNT_CONTAINS_FULL);
		$config->setScheduleTime('00:00');
		$config->setEnabled(true);
		$config->setHidden(false);
		$config->save();

		return $config;
	}

	/**
	 * Search for a given destination inside all backup jobs and return count
	 *
	 * @param $destination_id
	 *
	 * @return int
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public static function getDestinationsCount($destination_id): int {
		$list = self::query()
		                 ->select([JetBackup::ID_FIELD])
		                 ->where([ BackupJob::DESTINATIONS, 'contains', $destination_id])
		                 ->getQuery()
		                 ->fetch();
		return count($list);
	}

	/**
	 * @return BackupJob|null
	 * @throws InvalidArgumentException
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException|IOException|JBException
	 */
	public static function getDefaultConfigJob():BackupJob {
		$result = self::query()
			->select([JetBackup::ID_FIELD])
			->where([ self::TYPE, "=", self::TYPE_CONFIG ])
			->where([ self::DEFAULT, "=", true ])
			->getQuery()
			->first();

		$destinations = new Destination();
		$exportEnabled =  $destinations::query()
			->select([JetBackup::ID_FIELD])
			->where([ Destination::EXPORT_CONFIG, "=", true ])
			->getQuery()
			->fetch();

		$exportEnabledIDS = sizeof($exportEnabled) ? array_column($exportEnabled, JetBackup::ID_FIELD) : [Destination::getDefaultDestination()->getId()];

		if($result) return new BackupJob($result[JetBackup::ID_FIELD]);

		$schedule = Schedule::getDefaultConfigSchedule();

		$scheduleItem = new ScheduleItem();
		$scheduleItem->setId($schedule->getId());
		$scheduleItem->setType(Schedule::TYPE_WEEKLY);
		$scheduleItem->setRetain(2);
		
		$config = new BackupJob();
		$config->setType(self::TYPE_CONFIG);
		$config->setName(self::DEFAULT_CONFIG_JOB_NAME);
		$config->setDestinations($exportEnabledIDS);
		$config->setDefault(true);
		$config->setContains(self::BACKUP_ACCOUNT_CONTAINS_FULL);
		$config->addSchedule($scheduleItem);
		$config->setScheduleTime('00:00');
		$config->setEnabled(sizeof($exportEnabled));
		$config->setHidden(true);
		$config->calculateNextRun();
		$config->save();

		return $config;
	}

	public function getBackupDir():string {
		// Example:  /home/USER/public_html/wp-content/uploads/jetbackup-%s{24}/backups/job_%d{1}_%s{24}/
		return Factory::getLocations()->getBackupsDir() . JetBackup::SEP . $this->getIdentifier();
	}

	/**
	 * @param int $runTime
	 *
	 * @return ScheduleItem[]
	 */
	public function getRunningSchedules(int $runTime):array {
		$output = [];
		$schedules = $this->getSchedules();
		foreach($schedules as $schedule) if($schedule->getNextRun() <= $runTime && $schedule->getNextRun() > 0) $output[] = $schedule;
		return $output;
	}

	/**
	 * @param bool $manually
	 * @param bool $afterJobDone
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function addToQueue( bool $manually=false, bool $afterJobDone=false):void {
		
		$backup = new QueueItemBackup();
		$backup->setJobId($this->getId());
		$backup->setSnapshotName(Snapshot::generateName());
		$backup->setManually($manually);
		$backup->setAfterJobDone($afterJobDone);
		$backup->setType($this->getType());

		$queue_item = QueueItem::prepare();
		$queue_item->setType(Queue::QUEUE_TYPE_BACKUP);
		$queue_item->setItemId($this->getId());
		$queue_item->setItemData($backup);
		
		Queue::addToQueue($queue_item);
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws Exception
	 */
	public static function addToQueueScheduled() {

		$list = self::query()
			->select([JetBackup::ID_FIELD])
			->getQuery()
			->fetch();

		foreach($list as $details) {

 			$job = new BackupJob($details[JetBackup::ID_FIELD]);

			if(
				!$job->isEnabled() ||
				!($next_run = $job->getNextRun()) ||
				$next_run > Util::getDateTime()->getTimestamp() // if $next_run is in the future, break the loop
			) continue;

			try {
				$job->addToQueue();
				Alert::add("Backup job \"{$job->getName()}\" added to queue", "Starting Backup Job", Alert::LEVEL_INFORMATION);
			} catch(QueueException $e) {
				Alert::add("Backup job \"{$job->getName()}\" failed added to queue", "Add to queue failed: " . $e->getMessage(), Alert::LEVEL_WARNING);
			}
		}
	}
	
	public function duplicate():BackupJob {
		$job = clone $this;
		$job->setFind([]);
		$job->setId(0);
		$job->setUniqueId('');
		$job->setName($this->getName() . " [ Duplicated " . time() . " ]");
		$job->setEnabled(false);
		$job->setDefault(false);
		$job->save();
		return $job;
	}

	/**
	 * @return void
	 * @throws DBException
	 * @throws FieldsValidationException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function validateFields():void {
		if(!$this->getName()) throw new FieldsValidationException("Job name must be set");
		if(!$this->getType()) throw new FieldsValidationException("You must provide backup type");
		if(!in_array($this->getType(), [self::TYPE_ACCOUNT,self::TYPE_CONFIG])) throw new FieldsValidationException("Invalid backup type provided");

		if(!$this->getContains()) throw new FieldsValidationException("Backup has to contain at least files or database");

		if($this->getDestinations()) {
			$destinations = [];
			$is_local = 0;
			foreach($this->getDestinations() as $destination_id) {
				if(!$destination_id) throw new FieldsValidationException("Invalid destinations id provided");
				$destination = new Destination($destination_id);
				if ($destination->getId()) $destinations[] = $destination->getId();
				if($destination->isReadOnly()) throw new FieldsValidationException("Destination '{$destination->getName()}' is readonly");
				if(!$destination->isEnabled()) throw new FieldsValidationException("Destination '{$destination->getName()}' is disabled");
				if($destination->getType() == Local::TYPE) $is_local++;
			}

			if($is_local > 1)  throw new FieldsValidationException("Only one local destination is allowed per job");

			$this->setdestinations($destinations);
		} else throw new FieldsValidationException("You have to select at least one destination");

		if ($this->getScheduleTime() && !preg_match('/^(2[0-3]|[01]?[0-9]):[0-5][0-9]$/', $this->getScheduleTime()))
			throw new FieldsValidationException("Invalid schedule time provided! (Use 24H clock: 23:00)");

		if(!empty($this->getExcludes())) {
			foreach($this->getExcludes() as $exclude) {
				$exclude = trim($exclude);
				if(str_starts_with($exclude, Factory::getWPHelper()->getWordPressHomedir())) throw new FieldsValidationException("Exclude path '$exclude' cannot start with your homedir");
				if(!str_starts_with($exclude, JetBackup::SEP)) throw new FieldsValidationException("Exclude path '$exclude' must start with " . JetBackup::SEP);
			}
		}

	}

	public function getDisplay():array {

		$destination_names = [];
		$schedule_names = [];

		foreach($this->getDestinations() as $destination_id) {
			$destination = new Destination($destination_id);
			$destination_names[] = $destination->getId() ? $destination->getName() : '* Destination Deleted (' . $destination_id . ') *';
		}

		$schedules = [];
		
		foreach($this->getSchedules() as $scheduleItem) {
			$schedule = $scheduleItem->getScheduleInstance();
			$schedule_names[] = $schedule->getId() ? $schedule->getName() : '* Schedule Deleted (' . $schedule->getId() . ') *';

			$schedule_item = new \stdClass();
			$schedule_item->{JetBackup::ID_FIELD} = $scheduleItem->getId();
			$schedule_item->{ScheduleItem::RETAIN} = $scheduleItem->getRetain();
			$schedules[] = $schedule_item;
		}
		
		return [
			JetBackup::ID_FIELD     => $this->getId(),
			self::NAME              => $this->getName(),
			self::TYPE              => $this->getType(),
			self::CONTAINS          => $this->getContains(),
			self::NEXT_RUN          => $this->getNextRun(),
			self::LAST_RUN          => $this->getLastRun(),
			self::ENABLED           => $this->isEnabled(),
			self::UNIQUE_ID         => $this->getUniqueId(),
			self::DESTINATIONS      => $this->getDestinations(),
			self::EXCLUDES          => $this->getExcludes(),
			self::SCHEDULES         => $schedules,
			self::SCHEDULE_TIME     => $this->getScheduleTime(),
			self::JOB_MONITOR       => $this->getMonitor(),
			self::EXCLUDE_DATABASES => $this->getExcludeDatabases(),
			self::DESTINATION_NAMES => $destination_names ? implode(', ', $destination_names) : '-',
			self::SCHEDULE_NAMES    => $schedule_names ? implode(', ', $schedule_names) : '-',
		];
	}

	public function getDisplayCLI():array {

		$destination_names = [];
		$schedule_names = [];

		foreach($this->getDestinations() as $destination_id) {
			$destination = new Destination($destination_id);
			$destination_names[] = $destination->getId() ? $destination->getName() . ' (' . $destination->getType() . ')' : '* Destination Deleted (' . $destination_id . ') *';
		}

		foreach($this->getSchedules() as $scheduleItem) {
			$schedule = $scheduleItem->getScheduleInstance();
			$schedule_names[] = $schedule->getId() ? $schedule->getName() . ' (Retain: ' . $scheduleItem->getRetain() . ')' : '* Schedule Deleted (' . $schedule->getId() . ') *';
		}

		return [
			'ID'                => $this->getId(),
			'Name'              => $this->getName(),
			'Type'              => self::TYPE_NAMES[$this->getType()],
			'Contains'          => $this->getContainsName(),
			'Next Run'          => $this->getNextRun() ? CLI::date($this->getNextRun()) : 'Never',
			'Last Run'          => $this->getLastRun() ? CLI::date($this->getLastRun()) : 'Never',
			'Enabled'           => $this->isEnabled() ? 'Yes' : 'No',
			'Excludes'          => implode(', ', $this->getExcludes()),
			'Destinations'      => implode(', ', $destination_names),
			'Schedules'         => implode(', ', $schedule_names),
			'Schedule Time'     => $this->getScheduleTime(),
			'Monitor'           => $this->getMonitor(),
			'Excluded Databases'=> implode(', ', $this->getExcludeDatabases()),
		];
	}
}