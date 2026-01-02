<?php

namespace JetBackup\Queue;

use JetBackup\Exception\QueueException;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Queue {

	public const QUEUE_TYPE_BACKUP = 1;
	public const QUEUE_TYPE_RESTORE = 2;
	public const QUEUE_TYPE_DOWNLOAD = 4;
    public const QUEUE_TYPE_DOWNLOAD_BACKUP_LOG = 5;
    public const QUEUE_TYPE_REINDEX = 8;
	public const QUEUE_TYPE_RETENTION_CLEANUP = 16;
	public const QUEUE_TYPE_SYSTEM = 32;
	public const QUEUE_TYPE_EXPORT = 64;
    public const QUEUE_TYPE_EXTRACT = 128;

	public const QUEUE_TYPES = [
		self::QUEUE_TYPE_BACKUP,
		self::QUEUE_TYPE_RESTORE,
		self::QUEUE_TYPE_DOWNLOAD,
        self::QUEUE_TYPE_DOWNLOAD_BACKUP_LOG,
		self::QUEUE_TYPE_REINDEX,
		self::QUEUE_TYPE_RETENTION_CLEANUP,
		self::QUEUE_TYPE_SYSTEM,
		self::QUEUE_TYPE_EXPORT,
		self::QUEUE_TYPE_EXTRACT,
	];

	public const QUEUE_STATUS_NAMES = [
		self::QUEUE_TYPE_BACKUP => self::STATUS_BACKUP_ACCOUNT_NAMES,
		self::QUEUE_TYPE_RESTORE => self::STATUS_PRE_RESTORE_NAMES,
		self::QUEUE_TYPE_DOWNLOAD => self::STATUS_DOWNLOAD_NAMES,
        self::QUEUE_TYPE_DOWNLOAD_BACKUP_LOG => self::STATUS_DOWNLOAD_LOG_NAMES,
		self::QUEUE_TYPE_REINDEX => self::STATUS_REINDEX_NAMES,
		self::QUEUE_TYPE_RETENTION_CLEANUP => self::STATUS_CLEANUP_NAMES,
		self::QUEUE_TYPE_SYSTEM => self::STATUS_SYSTEM_NAMES,
		self::QUEUE_TYPE_EXPORT => self::STATUS_EXPORT_NAMES,
		self::QUEUE_TYPE_EXTRACT => self::STATUS_EXTRACT_NAMES,
	];

	public const QUEUE_TYPES_NAMES = [
		self::QUEUE_TYPE_BACKUP                 => 'Backup',
		self::QUEUE_TYPE_RESTORE                => 'Restore',
		self::QUEUE_TYPE_DOWNLOAD               => 'Download',
        self::QUEUE_TYPE_DOWNLOAD_BACKUP_LOG    => 'Download Backup Log',
		self::QUEUE_TYPE_REINDEX                => 'Reindex',
		self::QUEUE_TYPE_RETENTION_CLEANUP      => 'Retention Cleanup',
		self::QUEUE_TYPE_SYSTEM                 => 'System',
		self::QUEUE_TYPE_EXPORT                 => 'Export',
		self::QUEUE_TYPE_EXTRACT                => 'Extract',
	];

	// Backup Account Statuses
	public const STATUS_BACKUP_ACCOUNT_DUMPING_DB = 10;
	public const STATUS_BACKUP_ACCOUNT_ARCHIVING = 20;
	public const STATUS_BACKUP_ACCOUNT_COMPRESSING = 21;
	public const STATUS_BACKUP_ACCOUNT_SEND_TO_DESTINATION = 30;
	public const STATUS_BACKUP_ACCOUNT_NAMES = [
		self::STATUS_BACKUP_ACCOUNT_DUMPING_DB              => 'Dumping Database',
		self::STATUS_BACKUP_ACCOUNT_ARCHIVING               => 'Archiving',
		self::STATUS_BACKUP_ACCOUNT_COMPRESSING             => 'Compressing',
		self::STATUS_BACKUP_ACCOUNT_SEND_TO_DESTINATION     => 'Transferring to Destination',
	];

	// Backup Config Statuses
	public const STATUS_BACKUP_CONFIG_ARCHIVING = 20;
	public const STATUS_BACKUP_CONFIG_COMPRESSING = 21;
	public const STATUS_BACKUP_CONFIG_SEND_TO_DESTINATION = 30;
	public const STATUS_BACKUP_CONFIG_NAMES = [
		self::STATUS_BACKUP_CONFIG_ARCHIVING               => 'Archiving',
		self::STATUS_BACKUP_CONFIG_COMPRESSING             => 'Compressing',
		self::STATUS_BACKUP_CONFIG_SEND_TO_DESTINATION     => 'Transferring to Destination',
	];

	// Restore Statuses
	public const STATUS_RESTORE_DOWNLOAD = 10;
	public const STATUS_RESTORE_EXTRACT = 20;
	public const STATUS_RESTORE_BUILD_URL = 30;
	public const STATUS_RESTORE_WAITING_FOR_RESTORE = 40;
	public const STATUS_RESTORE_DATABASE = 50;
	public const STATUS_RESTORE_FILES = 55;
	public const STATUS_RESTORE_POST_RESTORE_DB_PREFIX = 60;
	public const STATUS_RESTORE_POST_RESTORE_DOMAIN_MIGRATION = 65;
	public const STATUS_RESTORE_POST_RESTORE_PLUGIN_ACTIONS = 70;
	public const STATUS_RESTORE_POST_RESTORE_HEALTH_CHECK = 75;
	
	// Restore JB Linux Statuses
	public const STATUS_RESTORE_JB_IMPORTING_DB = 55;
	public const STATUS_RESTORE_JB_DATABASES = 60;
	public const STATUS_RESTORE_JB_DATABASE_USERS = 65;
	public const STATUS_RESTORE_JB_HOMEDIR = 70;
	public const STATUS_RESTORE_JB_POST = 75;
	public const STATUS_RESTORE_JB_PRE = 80;

	public const STATUS_PRE_RESTORE_NAMES = [
		self::STATUS_RESTORE_DOWNLOAD                       => 'Downloading',
		self::STATUS_RESTORE_EXTRACT                        => 'Extracting',
		self::STATUS_RESTORE_BUILD_URL                      => 'Building Restore URL',
		self::STATUS_RESTORE_WAITING_FOR_RESTORE            => 'Waiting for external restore',
	];

	public const STATUS_RESTORE_NAMES = [
		self::STATUS_RESTORE_DATABASE                           => 'Restoring Database',
		self::STATUS_RESTORE_FILES                              => 'Restoring Files',
		self::STATUS_RESTORE_POST_RESTORE_DB_PREFIX             => 'Post Restore - DB Prefix',
		self::STATUS_RESTORE_POST_RESTORE_DOMAIN_MIGRATION      => 'Post Restore - Domain Migration',
		self::STATUS_RESTORE_POST_RESTORE_PLUGIN_ACTIONS        => 'Post Restore - Plugin Actions',
		self::STATUS_RESTORE_POST_RESTORE_HEALTH_CHECK          => 'Post Restore - Health Check',
	];

	// Reindex Statuses
	public const STATUS_REINDEX_MARKING_SNAPSHOTS = 10;
	public const STATUS_REINDEX_INDEXING_SNAPSHOTS = 30;
	public const STATUS_REINDEX_DELETE_SNAPSHOTS = 40;
	public const STATUS_REINDEX_NAMES = [
		self::STATUS_REINDEX_MARKING_SNAPSHOTS              => 'Marking Snapshots for delete',
		self::STATUS_REINDEX_INDEXING_SNAPSHOTS             => 'Indexing Snapshots',
		self::STATUS_REINDEX_DELETE_SNAPSHOTS               => 'Deleting Snapshots',
	];
	
	// Extract Statuses
	public const STATUS_EXTRACT_DOWNLOAD = 10;
	public const STATUS_EXTRACT_EXTRACT = 15;
	public const STATUS_EXTRACT_LEGACY = 25;
	public const STATUS_EXTRACT_NAMES = [
		self::STATUS_EXTRACT_DOWNLOAD                       => 'Downloading',
		self::STATUS_EXTRACT_EXTRACT                        => 'Extracting',
		self::STATUS_EXTRACT_LEGACY                         => 'Extracting Legacy',
	];

	// Export Statuses
	public const STATUS_EXPORT_DOWNLOAD = 10;
	public const STATUS_EXPORT_EXTRACT = 15;
	public const STATUS_EXPORT_BUILD = 25;
	public const STATUS_EXPORT_NAMES = [
		self::STATUS_EXPORT_DOWNLOAD                        => 'Downloading',
		self::STATUS_EXPORT_EXTRACT                         => 'Extracting',
		self::STATUS_EXPORT_BUILD                           => 'Building',
	];

	// Download Statuses
	public const STATUS_DOWNLOAD_DOWNLOAD = 10;
	public const STATUS_DOWNLOAD_ARCHIVE = 15;
	public const STATUS_DOWNLOAD_COMPRESS = 20;
	public const STATUS_DOWNLOAD_NAMES = [
		self::STATUS_DOWNLOAD_DOWNLOAD                       => 'Downloading',
		self::STATUS_DOWNLOAD_ARCHIVE                        => 'Archiving',
		self::STATUS_DOWNLOAD_COMPRESS                       => 'Compressing',

	];

    public const STATUS_DOWNLOAD_LOG_DOWNLOADING = 10;
    public const STATUS_DOWNLOAD_LOG_NAMES = [
        self::STATUS_DOWNLOAD_LOG_DOWNLOADING                       => 'Downloading',
    ];

	// Retention Cleanup Statuses
	public const STATUS_CLEANUP_DELETING = 10;
	public const STATUS_CLEANUP_NAMES = [
		self::STATUS_CLEANUP_DELETING                        => 'Deleting',
	];
	
	// System Statuses
	public const STATUS_SYSTEM_JOB_MONITOR = 10;
	public const STATUS_SYSTEM_DB_CLEANUP = 15;
	public const STATUS_SYSTEM_UPLOAD_CLEANUP = 17;
	public const STATUS_SYSTEM_SYSTEM_CLEANUP = 20;
	public const STATUS_SYSTEM_LOGS_CLEANUP = 25;
	public const STATUS_SYSTEM_VALIDATE_CHECKSUMS = 30;

	public const STATUS_SYSTEM_DAILY_ALERTS = 35;
	public const STATUS_SYSTEM_NAMES = [
		self::STATUS_SYSTEM_JOB_MONITOR                     => 'Backup Job Monitoring',
		self::STATUS_SYSTEM_DB_CLEANUP                      => 'Database Cleanup',
		self::STATUS_SYSTEM_UPLOAD_CLEANUP                  => 'Uploads Cleanup',
		self::STATUS_SYSTEM_SYSTEM_CLEANUP                  => 'System Cleanup',
		self::STATUS_SYSTEM_LOGS_CLEANUP                    => 'Logs Cleanup',
		self::STATUS_SYSTEM_VALIDATE_CHECKSUMS              => 'Validating Checksums',
		self::STATUS_SYSTEM_DAILY_ALERTS              => 'Process Daily Alerts',
	];

	public const STATUS_PENDING = 1;
	public const STATUS_STARTED = 2;
	public const STATUS_PREPARING = 3;
	public const STATUS_DONE = 100;
	public const STATUS_PARTIALLY = 101;
	public const STATUS_FAILED = 102;
	public const STATUS_ABORTED = 103;
	public const STATUS_NEVER_FINISHED = 104;

	public const REQUIRES_CLEANUP = [
		self::STATUS_FAILED,
		self::STATUS_ABORTED,
		self::STATUS_NEVER_FINISHED,
	];

	public const STATUS_NAMES = [
		self::STATUS_PENDING            => 'Pending',
		self::STATUS_STARTED            => 'Started',
		self::STATUS_PREPARING          => 'Preparing',
		self::STATUS_DONE               => 'Completed',
		self::STATUS_PARTIALLY          => 'Partially Completed',
		self::STATUS_FAILED             => 'Failed',
		self::STATUS_ABORTED            => 'Aborted',
		self::STATUS_NEVER_FINISHED     => 'Never Finished',
	];


	public function __construct() {}


	public function getNext():?QueueItem {
		$res = QueueItem::query()
            ->select([JetBackup::ID_FIELD])
            ->where([QueueItem::STATUS, '<', self::STATUS_DONE])
            ->orderBy([QueueItem::STATUS => 'asc'])
            ->limit(1)
            ->getQuery()
            ->first();
		
		return $res ? new QueueItem($res[JetBackup::ID_FIELD]) : null;
	}	
	
	public static function next():?QueueItem {
		return (new Queue())->getNext();
	}

	/**
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public static function inQueue($type, $_id=null):bool {
		$query = QueueItem::query()
			->where([ QueueItem::TYPE, '=', $type ])
			->where([ QueueItem::STATUS, '<', self::STATUS_DONE ]);
		if($_id) $query->where([ QueueItem::ITEM_ID, '=', $_id ]);
		return !!$query->getQuery()->first();
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public static function getTotalActiveItems(): int {

		return count(QueueItem::query()
             ->select([JetBackup::ID_FIELD])
             ->where([QueueItem::STATUS, '<', self::STATUS_DONE])
             ->getQuery()
             ->fetch());
	}
	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public static function getTotalPendingItems(): int {
		return count(QueueItem::query()
			->select([JetBackup::ID_FIELD])
			->where([QueueItem::STATUS, '=', self::STATUS_PENDING])
			->getQuery()
			->fetch());
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public static function getTotalCompletedItems(): int {
		return count(QueueItem::query()
			->select([JetBackup::ID_FIELD])
			->where([QueueItem::STATUS, '=', self::STATUS_DONE])
			->getQuery()
			->fetch());
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public static function getTotalAbortedItems(): int {
		return count(QueueItem::query()
			->select([JetBackup::ID_FIELD])
			->where([QueueItem::STATUS, '=', self::STATUS_ABORTED])
			->getQuery()
			->fetch());
	}

	public static function clearCompleted(): void {

	QueueItem::query()->select([JetBackup::ID_FIELD])
		                      ->where([QueueItem::STATUS, '>=', self::STATUS_DONE])
		                      ->getQuery()
		                      ->delete();

	}



	/**
	 * @param QueueItem $item
	 *
	 * @return void
	 * @throws QueueException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public static function addToQueue(QueueItem $item):void {
		if(self::inQueue($item->getType(), $item->getItemId())) throw new QueueException("Already in queue");
		$item->save();
	}
}