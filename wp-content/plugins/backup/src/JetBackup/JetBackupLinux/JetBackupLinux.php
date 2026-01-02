<?php

namespace JetBackup\JetBackupLinux;

use JetBackup\BackupJob\BackupJob;
use JetBackup\Config\System;
use JetBackup\Data\Engine;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\Exception\QueueException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemReindex;
use JetBackup\Snapshot\Snapshot;
use JetBackup\SocketAPI\Client\Client;
use JetBackup\SocketAPI\Exception\SocketAPIException;
use JetBackup\SocketAPI\SocketAPI;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class JetBackupLinux {

	const BACKUP_TYPE_ACCOUNT = 1;

	const BACKUP_TYPE_ACCOUNT_CONFIG            = 1<<0;
	const BACKUP_TYPE_ACCOUNT_HOMEDIR           = 1<<1;
	const BACKUP_TYPE_ACCOUNT_DATABASES         = 1<<2;
	const BACKUP_TYPE_ACCOUNT_EMAILS            = 1<<3;
	const BACKUP_TYPE_ACCOUNT_CRON_JOBS         = 1<<4;
	const BACKUP_TYPE_ACCOUNT_DOMAINS           = 1<<5;
	const BACKUP_TYPE_ACCOUNT_CERTIFICATES      = 1<<6;
	const BACKUP_TYPE_ACCOUNT_DATABASE_USERS    = 1<<7;
	const BACKUP_TYPE_ACCOUNT_FTP               = 1<<8;
	const BACKUP_TYPE_ACCOUNT_FULL              =
		self::BACKUP_TYPE_ACCOUNT_CONFIG |
		self::BACKUP_TYPE_ACCOUNT_HOMEDIR |
		self::BACKUP_TYPE_ACCOUNT_DATABASES |
		self::BACKUP_TYPE_ACCOUNT_EMAILS |
		self::BACKUP_TYPE_ACCOUNT_CRON_JOBS |
		self::BACKUP_TYPE_ACCOUNT_DOMAINS |
		self::BACKUP_TYPE_ACCOUNT_CERTIFICATES |
		self::BACKUP_TYPE_ACCOUNT_DATABASE_USERS |
		self::BACKUP_TYPE_ACCOUNT_FTP;

	const BACKUP_STRUCTURE_INCREMENTAL  = 1;
	const BACKUP_STRUCTURE_ARCHIVED     = 2;
	const BACKUP_STRUCTURE_COMPRESSED   = 4;


	const QUEUE_STATUS_RESTORE_ACCOUNT_CONFIG = 30;
	const QUEUE_STATUS_RESTORE_ACCOUNT_DOMAINS = 31;
	const QUEUE_STATUS_RESTORE_ACCOUNT_CERTIFICATES = 32;
	const QUEUE_STATUS_RESTORE_ACCOUNT_FTP = 33;
	const QUEUE_STATUS_RESTORE_ACCOUNT_CRON_JOBS = 34;
	const QUEUE_STATUS_RESTORE_ACCOUNT_IMPORTING_DATABASES = 35;
	const QUEUE_STATUS_RESTORE_ACCOUNT_DATABASES = 36;
	const QUEUE_STATUS_RESTORE_ACCOUNT_DATABASE_USERS = 37;
	const QUEUE_STATUS_RESTORE_ACCOUNT_HOMEDIR = 38;
	const QUEUE_STATUS_RESTORE_ACCOUNT_EMAILS = 39;
	const QUEUE_STATUS_RESTORE_ACCOUNT_POST_RESTORE = 40;
	const QUEUE_STATUS_RESTORE_ACCOUNT_PRE_RESTORE = 41;

	const QUEUE_STATUS_RESTORE_MAPPING = [
		self::QUEUE_STATUS_RESTORE_ACCOUNT_IMPORTING_DATABASES => Queue::STATUS_RESTORE_JB_IMPORTING_DB,
		self::QUEUE_STATUS_RESTORE_ACCOUNT_DATABASES  => Queue::STATUS_RESTORE_JB_DATABASES,
		self::QUEUE_STATUS_RESTORE_ACCOUNT_DATABASE_USERS => Queue::STATUS_RESTORE_JB_DATABASE_USERS,
		self::QUEUE_STATUS_RESTORE_ACCOUNT_HOMEDIR => Queue::STATUS_RESTORE_JB_HOMEDIR,
		self::QUEUE_STATUS_RESTORE_ACCOUNT_POST_RESTORE => Queue::STATUS_RESTORE_JB_POST,
		self::QUEUE_STATUS_RESTORE_ACCOUNT_PRE_RESTORE => Queue::STATUS_RESTORE_JB_PRE,
	];

	const QUEUE_STATUS_RESTORE_ACCOUNT_NAMES = [
		self::QUEUE_STATUS_RESTORE_ACCOUNT_CONFIG => "Restoring Panel Configurations",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_DOMAINS => "Restoring Domains and DNS",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_CERTIFICATES => "Restoring SSL Certificates",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_FTP => "Restoring FTP Accounts",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_CRON_JOBS => "Restoring Cron Jobs",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_IMPORTING_DATABASES => "Importing databases data",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_DATABASES => "Restoring Databases",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_DATABASE_USERS => "Restoring Database Users",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_HOMEDIR => "Restoring Home Directory files",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_EMAILS => "Restoring Email Accounts",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_POST_RESTORE => "Performing post restore actions",
		self::QUEUE_STATUS_RESTORE_ACCOUNT_PRE_RESTORE => "Performing pre restore actions",
	];

	
	const QUEUE_STATUS_COMPLETED                = 100;
	const QUEUE_STATUS_PARTIALLY_COMPLETED      = 101;
	const QUEUE_STATUS_FAILED                   = 102;
	const QUEUE_STATUS_ABORTED                  = 103;
	const QUEUE_STATUS_NEVER_FINISHED           = 104;

	const QUEUE_STATUS_MAPPING = [
		self::QUEUE_STATUS_COMPLETED            => Queue::STATUS_DONE,
		self::QUEUE_STATUS_PARTIALLY_COMPLETED  => Queue::STATUS_PARTIALLY,
		self::QUEUE_STATUS_FAILED               => Queue::STATUS_FAILED,
		self::QUEUE_STATUS_ABORTED              => Queue::STATUS_ABORTED,
		self::QUEUE_STATUS_NEVER_FINISHED       => Queue::STATUS_NEVER_FINISHED,
	];

	const QUEUE_TYPE_RESTORE          = 1<<1;

	const MINIMUM_VERSION = '5.3.15';

	private function __construct() {}
	
	public static function isEnabled():bool {
		return Factory::getSettingsGeneral()->isJBIntegrationEnabled() && self::isInstalled();
	}

	/**
	 * Return true will allow to check if socket file exist, we will not continue to check for socket file is this return false
	 * @return bool
	 */
	private static function isOpenBaseDir(): bool {
		$openBasedir = ini_get('open_basedir');
		if (!$openBasedir) return true;
		$allowedPaths = explode(PATH_SEPARATOR, $openBasedir);
		foreach ($allowedPaths as $allowedPath) {
			if (str_starts_with($allowedPath, Client::SOCKET_FILE))  return true;
		}
		return false; // The socket file is not within allowed paths
	}

	private static function isSocket(): bool {
		return file_exists(Client::SOCKET_FILE);
	}


	/**
	 * Using this in the background for different implementations, just to verify if JBLinux is installed
	 * @return bool
	 */
	public static function isInstalled():bool {
		return !System::isWindowsOS() && self::isOpenBaseDir() && self::isSocket();
	}

	/**
	 * Using this for Ajax validation
	 * @return void
	 * @throws JetBackupLinuxException
	 */
	public static function checkRequirements():void {
		if(System::isWindowsOS()) throw new JetBackupLinuxException("This feature is not supported in Windows operating system");
		if(!self::isOpenBaseDir()) throw new JetBackupLinuxException("'open_basedir' restriction is enabled, make sure " . dirname(Client::SOCKET_FILE) . " is available");
		if(!Util::has_posix_getpwuid()) throw new JetBackupLinuxException("Required function 'posix_getpwuid' is disabled. Enable it to allow Socket API.");
		if(!Util::has_posix_getgrgid()) throw new JetBackupLinuxException("Required function 'posix_getgrgid' is disabled. Enable it to allow Socket API.");
		if(!Util::has_posix_geteuid()) throw new JetBackupLinuxException("Required function 'posix_geteuid' is disabled. Enable it to allow Socket API.");

		if (!self::isSocket()) throw new JetBackupLinuxException("Cannot find JetBackup Linux socket");
		if(!function_exists('socket_connect')) throw new JetBackupLinuxException("The function socket_connect not installed or disabled within your PHP.");
		try {
			$response = SocketAPI::api('getMyAccount')->execute();
		} catch(SocketAPIException $e) {
			throw new JetBackupLinuxException($e->getMessage());
		}

		if(!isset($response['system']['version'])) throw new JetBackupLinuxException("Failed fetching JetBackup linux version");
		if(version_compare(self::MINIMUM_VERSION, $response['system']['version'], '>')) throw new JetBackupLinuxException("JetBackup linux version is not compatible ({$response['system']['version']}), Minimum required version is " . self::MINIMUM_VERSION);
	}

	/**
	 * @return array|mixed
	 * @throws JetBackupLinuxException
	 * @throws SocketAPIException
	 */
	public static function getAccountInfo() {
		return Query::api('getMyAccount')->execute();
	}

	/**
	 * @param string $id
	 * @param string $path
	 * @param int $limit
	 * @param int $skip
	 * @param array $sort
	 *
	 * @return array|mixed
	 * @throws JetBackupLinuxException
	 * @throws SocketAPIException
	 */
	public static function fileManager(string $id, string $path, int $limit=0, int $skip=0, array $sort=[]) {
		$query = Query::api('fileManager')
			->arg('type', 'backup')
			->arg('_id', $id)
			->arg('path', $path);

		if($limit || $skip) $query->limit($limit, $skip);

		foreach($sort as $key => $direction) {
			if($direction > 0) $query->sortAsc($key);
			else if($direction < 0) $query->sortDesc($key);
		}

		return $query->execute();
	}

	/**
	 * @param array $items
	 * @param array $files
	 * @param array $options
	 *
	 * @return array|mixed
	 * @throws JetBackupLinuxException
	 * @throws SocketAPIException
	 */
	public static function addQueueItems(array $items, array $files=[], array $options=[]) {

		// Force exclude plugin's main folder and datadir
		if(!isset($options['exclude'])) $options['exclude'] = [];
		$options['exclude'][] = Factory::getWPHelper()->getWordPressRelativePublicDir() .
		                        JetBackup::SEP . Wordpress::WP_CONTENT .
		                        JetBackup::SEP . Wordpress::WP_PLUGINS .
		                        JetBackup::SEP . JetBackup::PLUGIN_NAME;
		if (Factory::getLocations()->getPublicDataDir()) {
			$options['exclude'][] = Factory::getWPHelper()->getWordPressRelativePublicDir() . JetBackup::SEP . Factory::getLocations()->getPublicDataDir();
		} else {
			$options['exclude'][] = Factory::getLocations()->getRelativeDataDir();
		}

		return Query::api('addQueueItems')
			->arg('type', self::QUEUE_TYPE_RESTORE)
			->arg('items', $items)
			->arg('files', $files)
			->arg('options', $options)
			->execute();
	}


	/**
	 * @param array $sort
	 *
	 * @return array
	 * @throws JetBackupLinuxException|SocketAPIException
	 */
	public static function listQueueGroups(array $sort=[]):array {
		$query = Query::api('listQueueGroups')
			->arg('type', self::QUEUE_TYPE_RESTORE)
			->limit(9999999);

		foreach($sort as $key => $direction) {
			if($direction > 0) $query->sortAsc($key);
			else if($direction < 0) $query->sortDesc($key);
		}

		$response = $query->execute();

		return $response['groups'];
	}

	/**
	 * @param string $group_id
	 *
	 * @return array
	 * @throws JetBackupLinuxException|SocketAPIException
	 */
	public static function getQueueGroup(string $group_id) {
		return Query::api('getQueueGroup')->arg('_id', $group_id)->execute();
	}
	
	/**
	 * @param string $group_id
	 * @param array $sort
	 *
	 * @return array
	 * @throws JetBackupLinuxException|SocketAPIException
	 */
	public static function listQueueItems(string $group_id, array $sort=[]):array {

		$query = Query::api('listQueueItems')
			->arg('group_id', $group_id)
			->limit(9999999);

		foreach($sort as $key => $direction) {
			if($direction > 0) $query->sortAsc($key);
			else if($direction < 0) $query->sortDesc($key);
		}

		$response = $query->execute();

		return $response['items'];
	}

	/**
	 * @param array $sort
	 *
	 * @return array
	 * @throws JetBackupLinuxException
	 * @throws SocketAPIException
	 */
	public static function listBackups(array $sort=[]):array {
		
		$query = Query::api('listBackupsWithItems')
			->arg('type', self::BACKUP_TYPE_ACCOUNT)
			->arg('structure', self::BACKUP_STRUCTURE_INCREMENTAL)
			->limit(9999999);
		
		foreach($sort as $key => $direction) {
			if($direction > 0) $query->sortAsc($key);
			else if($direction < 0) $query->sortDesc($key);
		}
		
		$response = $query->execute();

		return $response['backups'];
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws DBException
	 */
	public static function deleteSnapshots():void {

		$list = Snapshot::query()
	        ->where([Engine::ENGINE, '=', Engine::ENGINE_JB])
	        ->getQuery()
	        ->fetch();

		if (empty($list)) return;

		foreach ($list as $item) {
			$snapshot = new Snapshot($item[JetBackup::ID_FIELD]);
			if(!$snapshot->getId()) continue;
			$snapshot->delete();
		}
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws QueueException
	 * @throws JetBackupLinuxException
	 */
	public static function addToQueue():void {

		if(!Factory::getSettingsGeneral()->isJBIntegrationEnabled()) throw new JetBackupLinuxException("JetBackup Linux integration is disabled");
		if(!self::isInstalled()) throw new JetBackupLinuxException("JetBackup Linux integration is not installed");

		$reindex = new QueueItemReindex();
		$queue_item = QueueItem::prepare();
		$queue_item->setType(Queue::QUEUE_TYPE_REINDEX);
		$queue_item->setItemId(0);
		$queue_item->setItemData($reindex);

		Queue::addToQueue($queue_item);
	}

	public static function prepareFilesForRestore($homedir_snapID, $fileManger): array {

		$public_dir = trim(Factory::getWPHelper()->getWordPressHomedir(true), JetBackup::SEP);
		$files = [];
		if(!sizeof($fileManger)) return $files;

		foreach ($fileManger as $file) {
			$path = JetBackup::SEP . $public_dir . JetBackup::SEP . trim($file['path'], JetBackup::SEP);
			$files[$homedir_snapID][$path] = $file['type'];
		}

		return $files;

	}
}