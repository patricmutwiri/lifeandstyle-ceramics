<?php

namespace JetBackup\CLI;

use JetBackup\Ajax\iAjax;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Destination\Destination;
use JetBackup\Exception\AjaxException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Schedule\Schedule;
use JetBackup\Settings\Integrations;
use WP_CLI;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

/**
 * All-in-one Website Backup Platform
 *
 * @when after_wp_load
 */
class Command {

	const FLAGS = [
		'backup_path' => Destination::PATH,
		'id' => JetBackup::ID_FIELD
	];
	/**
	 * Add item to the queue
	 *
	 * ## OPTIONS
	 *
	 * --type=<type>
	 * : The queue type (1 = backup, 2 = restore, 4 = download, 8 = reindex, 64 = export, 128 = extract)
	 *
	 * [--id=<backup-job-id,snapshot-id,destination-id>]
	 * : The object id to add to queue (depend on the queue type, for backup - backup job id, for restore, download, export and extract - snapshot id, for reindex - destination id)
	 *
	 * [--snapshot_path=<absolute-path-to-snapshot>]
	 * : The snapshot path to import (restore from file)
	 *
	 * [--panel_type=<panel-type>]
	 * : The panel type to export to (1 = cPanel, 2 = DirectAdmin)
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup addToQueue --type=2 --id=2
	 *     wp jetbackup addToQueue --type=64 --id=5 --panel_type=1
	 *     wp jetbackup addToQueue --type=2 --snapshot_path="/home/user/public_html/mybackup.tar.gz"
	 *
	 * @when after_wp_load
	 */

	/**
	 * WordPress cli will not accept upper case flags (internal case #706)
	 * @param array $flags
	 *
	 * @return array
	 */
	private static function _keyToUpper(array $flags): array {
		$array = [];
		foreach ($flags as $key => $value) {$array[strtoupper($key)] = $value;}
		return $array;
	}

	private static function _argsToArray($args) {
		if (preg_match('/^(?:\[.*]|\{.*})$/s', $args)) return json_decode($args, true);
		return $args;
	}
	public function addToQueue($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * Delete backup job
	 *
	 * ## OPTIONS
	 *
	 * --id=<id>
	 * : The backup job id to delete
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup deleteBackupJob --id=2
	 *
	 * @when after_wp_load
	 */
	public function deleteBackupJob($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * Delete destination
	 *
	 * ## OPTIONS
	 *
	 * --id=<id>
	 * : The destination id to delete
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup deleteDestination --id=2
	 *
	 * @when after_wp_load
	 */
	public function deleteDestination($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * Abort queue item
	 *
	 * ## OPTIONS
	 *
	 * --id=<id>
	 * : The queue item id to delete
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup abortQueueItem --id=2
	 *
	 * @when after_wp_load
	 */
	public function abortQueueItem($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * Delete backup
	 *
	 * ## OPTIONS
	 *
	 * --id=<id>
	 * : The snapshot id to delete
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup deleteSnapshot --id=2
	 *
	 * @when after_wp_load
	 */
	public function deleteSnapshot($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * Delete download
	 *
	 * ## OPTIONS
	 *
	 * --id=<id>
	 * : The download id to delete
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup deleteSnapshot --id=2
	 *
	 * @when after_wp_load
	 */
	public function deleteDownload($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Enable/Disable export config
     *
     * ## OPTIONS
     *
     * --id=<id>
     * : the ID of the destination to configure.
     *
     * ## EXAMPLES
     *
     *     wp jetbackup destinationSetExportConfig --id=2
     *
     * @when after_wp_load
     */
	public function destinationSetExportConfig($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Duplicate backup job
     *
     * ## OPTIONS
     *
     * --id=<id>
     * : Job id of the job to duplicate.
     *
     * ## EXAMPLES
     *
     *     wp jetbackup duplicateBackupJob --id=2
     *
     * @when after_wp_load
     */
	public function duplicateBackupJob($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Edit the notes of a backup.
     *
     * ## OPTIONS
     *
     * --id=<id>
     * : The id of the backup whose notes you want to edit
     *
     * --notes=<notes>
     * : The new notes to associate with the backup
     *
     * ## EXAMPLES
     *
     *     wp jetbackup editBackupNotes --id=2 --notes="test"
     *
     * @when after_wp_load
     */
	public function editBackupNotes($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Enable/Disable backup job (Toggle the backup job status: enable to disable or disable to enable with a single action)
     *
     * ## OPTIONS
     *
     * --id=<id>
     * : The id of backup job enable/disable
     *
     * ## EXAMPLES
     *
     *     wp jetbackup enableBackupJob --id=2
     *
     * @when after_wp_load
     */
	public function enableBackupJob($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Enable/Disable destination  (Toggle the destination status: enable to disable or disable to enable with a single action)
     *
     * ## OPTIONS
     *
     * --id=<id>
     * : The id of Destination to enable/disable
     *
     * ## EXAMPLES
     *
     *     wp jetbackup enableDestination --id=2
     *
     * @when after_wp_load
     */
	public function enableDestination($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve a single backup
     *
     * ## OPTIONS
     *
     * --id=<snapshot-id>
     * : The id of the backup to retrieve
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getBackup --id=2
     *
     * @when after_wp_load
     */
	public function getBackup($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }
    /**
     * Retrieve a single backup job
     *
     * ## OPTIONS
     *
     * --id=<backup-job-id>
     * : The id of the backup job to retrieve
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getBackupJob --id=2
     *
     * @when after_wp_load
     */

	public function getBackupJob($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve a single destination
     *
     * ## OPTIONS
     *
     * --id=<destination-id>
     * : The id of the destination  to retrieve
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getDestination --id=2
     *
     * @when after_wp_load
     */
    public function getDestination($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve a single queue item
     *
     * ## OPTIONS
     *
     * --id=<queue-item-id>
     * : The id of the queue item  to retrieve
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getQueueItem --id=2
     *
     * @when after_wp_load
     */
	public function getQueueItem($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }
    /**
     * Retrieve a single schedule
     *
     * ## OPTIONS
     *
     * --id=<schedule-id>
     * : The id of the schedule to retrieve
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSchedule --id=2
     *
     * @when after_wp_load
     */
	public function getSchedule($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * Retrieve log data or full content from a queue item.
	 *
	 * ## OPTIONS
	 *
	 * --queue_item_id=<queue-item-id>
	 * : The ID of the queue item to fetch the log for.
	 *
	 * --content=<bool>
	 * : If set, the actual content of the log file will be returned.
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup getLog --queue_item_id=123 --content=0
	 *     wp jetbackup getLog --queue_item_id=123 --content=1
	 *
	 * @when after_wp_load
	 */
	public function getLog($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * List all backup jobs
	 *
	 * ## OPTIONS
	 *
	 * --id=<snapshot-id>
	 * : The id of the snapshot item to retrieve (Must be homedir snapshot item indexed from JetBackup Linux integration)
	 *
	 * --location=<path>
	 * : The path location to browse (from WP public directory)
	 *
	 * [--limit=<limit>]
	 * : limit the result to the specified number (default to 99999)
	 *
	 * [--skip=<skip>]
	 * : skip the result to the specified number (default to 0)
	 *
	 * [--sort=<sort>]
	 * : sort the result (default to asc)
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup listBackupJobs --limit=5  --sort=desc
	 *
	 * @when after_wp_load
	 */
	public function fileManager($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
     * Retrieve automation settings
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSettingsAutomation
     *
     * @when after_wp_load
     */
	public function getSettingsAutomation($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve general settings
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSettingsGeneral
     *
     * @when after_wp_load
     */
	public function getSettingsGeneral($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve logging settings
     *
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSettingsLogging
     *
     * @when after_wp_load
     */
	public function getSettingsLogging($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve maintenance settings
     *
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSettingsMaintenance
     *
     * @when after_wp_load
     */
	public function getSettingsMaintenance($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve notifications settings
     *
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSettingsNotifications
     *
     * @when after_wp_load
     */
	public function getSettingsNotifications($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }
    /**
     * Retrieve performance settings
     *
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSettingsPerformance
     *
     * @when after_wp_load
     */
	public function getSettingsPerformance($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve restore settings
     *
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSettingsRestore
     *
     * @when after_wp_load
     */
	public function getSettingsRestore($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * Retrieve Integrations settings
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup getSettingsIntegrations
	 *
	 * @when after_wp_load
	 */
	public function getSettingsIntegrations($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve security settings
     *
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSettingsSecurity
     *
     * @when after_wp_load
     */
	public function getSettingsSecurity($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * Retrieve updates settings
     *
     *
     * ## EXAMPLES
     *
     *     wp jetbackup getSettingsUpdates
     *
     * @when after_wp_load
     */
	public function getSettingsUpdates($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * List all system alerts
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<limit>]
	 * : limit the result to the specified number (default to 99999)
	 *
	 * [--skip=<skip>]
	 * : skip the result to the specified number (default to 0)
	 *
	 * [--sort=<sort>]
	 * : sort the result (default to asc)
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup listAlerts --limit=5  --sort=desc
	 *
	 * @when after_wp_load
	 */
	public function listAlerts($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * List all backup jobs
     *
     * ## OPTIONS
     *
     * [--limit=<limit>]
     * : limit the result to the specified number (default to 99999)
     *
     * [--skip=<skip>]
     * : skip the result to the specified number (default to 0)
     *
     * [--sort=<sort>]
     * : sort the result (default to asc)
     *
     * ## EXAMPLES
     *
     *     wp jetbackup listBackupJobs --limit=5  --sort=desc
     *
     * @when after_wp_load
     */
	public function listBackupJobs($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * List all backups
     *
     * ## OPTIONS
     *
     * [--limit=<limit>]
     * : limit the result to the specified number (default to 99999)
     *
     * [--skip=<skip>]
     * : skip the result to the specified number (default to 0)
     *
     * [--sort=<sort>]
     * : sort the result (default to asc)
     *
     * ## EXAMPLES
     *
     *     wp jetbackup listBackups --limit=5 --sort=desc
     *
     * @when after_wp_load
     */
	public function listBackups($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }
    /**
     * List all destinations
     *
     * ## OPTIONS
     *
     * [--limit=<limit>]
     * : limit the result to the specified number (default to 99999)
     *
     * [--skip=<skip>]
     * : skip the result to the specified number (default to 0)
     *
     * [--sort=<sort>]
     * : sort the result (default to asc)
     *
     * ## EXAMPLES
     *
     *     wp jetbackup listDestinations --limit=5  --sort=desc
     *
     * @when after_wp_load
     */
	public function listDestinations($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * List available Downloads
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<limit>]
	 * : limit the result to the specified number (default to 99999)
	 *
	 * [--skip=<skip>]
	 * : skip the result to the specified number (default to 0)
	 *
	 * [--sort=<sort>]
	 * : sort the result (default to asc)
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup listDownloads --limit=5  --sort=desc
	 *
	 * @when after_wp_load
	 */
	public function listDownloads($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * List all queue items
     *
     * ## OPTIONS
     *
     * [--limit=<limit>]
     * : limit the result to the specified number (default to 99999)
     *
     * [--skip=<skip>]
     * : skip the result to the specified number (default to 0)
     *
     * [--sort=<sort>]
     * : sort the result (default to asc)
     *
     * ## EXAMPLES
     *
     *     wp jetbackup listQueueItems --limit=5 --sort=desc
     *
     * @when after_wp_load
     */
	public function listQueueItems($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     * List all schedules
     *
     * ## OPTIONS
     *
     * [--limit=<limit>]
     * : limit the result to the specified number (default to 99999)
     *
     * [--skip=<skip>]
     * : skip the result to the specified number (default to 0)
     *
     * [--sort=<sort>]
     * : sort the result (default to asc)
     *
     * ## EXAMPLES
     *
     *     wp jetbackup listSchedules --limit=5 --sort=desc
     *
     * @when after_wp_load
     */
	public function listSchedules($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }
    /**
     *  lock snapshot of a backup
     *
     * ## OPTIONS
     *
     * --id=<id>
     * : backup id
     *
     * ## EXAMPLES
     *
     *     wp jetbackup lockSnapshot --id=5
     *
     * @when after_wp_load
     */
	public function lockSnapshot($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 * Create or modify backup job
	 *
	 * ## OPTIONS
	 *
	 * [--id=<backup-job-id>]
	 * : The backup job id to modify.
	 *
	 * [--name=<backup-job-name>]
	 * : The backup job name.
	 *
	 * [--type=<backup-job-type>]
	 * : The backup job type (1 for Account, 2 for Config).
	 *
	 * [--backup_contains=<backup-contains>]
	 * : The backup contains type (Files backup only = 1,Database backup only = 2, Full backup = 3).
	 *
	 * [--destinations=<list-of-destination-ids>]
	 * : The backup job destinations (json format)
	 *
	 * [--excludes=<list-of-files-to-exclude>]
	 * : Exclude files from backup (json format)
	 *
	 * [--database_excludes=<list-of-database-tables-to-exclude>]
	 * : Exclude database tables from backup (json format)
	 *
	 * [--job_monitor=<days>]
	 * : Will notify you if the backup wasn't executed within the specified number of days
	 *
	 * [--schedule_time=<time>]
	 * : The time that you want to execute the backup job
	 *
	 * [--schedules=<list-of-schedules>]
	 * : The schedules that you want this backup job will be executed (json format)
	 *
	 * [--enabled=<enabled>]
	 * : Set backup job enabled or disabled
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageBackupJob --id=24 --name="Daily Backup" --enabled=0
	 *     wp jetbackup manageBackupJob --name="Weekly Backup" --type=1 --destinations='[2,4,5]' --schedules='[{"_id":1,"retain":10}]' --excludes='["/test","/test2"]' --schedule_time=13:00 --backup_contains=3
	 *
	 * @when after_wp_load
	 */
	public function manageBackupJob($args, $flags) {
		if (isset($flags[BackupJob::DESTINATIONS])) $flags[BackupJob::DESTINATIONS] = self::_argsToArray($flags[BackupJob::DESTINATIONS]);
		if (isset($flags[BackupJob::SCHEDULES])) $flags[BackupJob::SCHEDULES] = self::_argsToArray($flags[BackupJob::SCHEDULES]);
		if (isset($flags[BackupJob::EXCLUDES])) $flags[BackupJob::EXCLUDES] = self::_argsToArray($flags[BackupJob::EXCLUDES]);
		self::_command(__FUNCTION__, $args, $flags);
	}

    /**
     *  Manage destination
     *
     * ## OPTIONS
     *
     * [--id=<destination-id>]
     * : the id of destination to modify.
     *
     * [--name=<destination-name>]
     * : the destination name
     *
     * [--type=<type>]
     * : the destination type (e.g. Local,SFTP,GoogleDrive,S3,OneDrive,Dropbox,Box,pCloud)
     *
     * [--read_only=<read_only>]
     * : Set read_only option , In a read-only destination you only will be able to restore and download existing backups. No write to this destination will be allowed
     *
     * [--chunk_size=<chunk_size>]
     * : Set the default read/write chunk size. Smaller chunks suit small files or slow connections, while larger chunks are better for big files on fast, stable networks.
     *
     * [--notes=<notes>]
     * : Add internal notes to help identify or describe this destination.
     *
     * [--free_disk=<free_disk>]
     * : This option will check if destination disk space reached the specified limit before it performs the backup. If you enable this option and available disk space is less than the amount specified, the system will not perform the backup.
     *
     * [--backup_path=<path>]
     * : Define the directory path on the remote server for storing backups. Make sure it’s writable and uniquely identifies the domain (e.g., /backups/domain.com/).
     *
     * [--enabled=<enabled>]
     * : Set destination enabled or disabled
     *
     * [--export_config=<export_config>]
     * : Set export config enabled or disabled
     *
     * [--options=<json-options>]
     * : Provide connection details and other advanced options in JSON format.
     * Example: '{"host":"sftp_host","username":"sftp_user","password":"sftp_pass","port":22,"timeout":60,"retries":5}'
     *
     * ## EXAMPLES
     *
     *     wp jetbackup manageDestination --id=5 --name="sftp" --enabled=0 --export_config=0 --chunk_size=1 --free_disk=0 --backup_path=/foo/boo --notes="This is a note" --options='{"host":"sftp_host","username":"sftp_user","password":"sftp_pass","port":22,"timeout":60,"retries":5}'
     *     wp jetbackup manageDestination --id=5 --name="google" --type=GoogleDrive --backup_path=/foo/boo --options='{"access_code":"YOU_ACCESS_CODE_HERE"}'
     *
     * @when after_wp_load
     */
	public function manageDestination($args, $flags) {
		if (isset($flags[Destination::OPTIONS])) $flags[Destination::OPTIONS] = self::_argsToArray($flags[Destination::OPTIONS]);

		self::_command(__FUNCTION__, $args, $flags);
	}

    /**
     *  Manage schedule
     *
     * ## OPTIONS
     *
     * [--id=<schedule-id>]
     * : the id of schedule to modify.
     *
     * [--name=<schedule-name>]
     * : the schedule name
     *
     * [--backup_id=<backup-id>]
     * : Use this option to specify the job ID when the type is set to "type = 6 ( after backup job done )"
     *
     * [--type=<type>]
     * : the schedule type (for , hourly = 1, daily = 2 ,weekly = 3 , monthly = 4 , after backup job done = 6 ,manually = 5)
     *
     * [--intervals=<intervals>]
     * : Set the intervals (array format)
     *
     * ## EXAMPLES
     *
     *     wp jetbackup manageSchedule --name="hourly schedule" --id=3 --intervals=[1]
     *     wp jetbackup manageSchedule --name="after job done schedule" --type=6 --backup_id=1
     *
     * @when after_wp_load
     */
	public function manageSchedule($args, $flags) {
		// If 'intervals' is provided, convert to json if type daily or monthly
		if (isset($flags[Schedule::INTERVALS])) $flags[Schedule::INTERVALS] = self::_argsToArray($flags[Schedule::INTERVALS]);
		self::_command(__FUNCTION__, $args, $flags);
	}

	/**
	 *  Manage automation settings
	 *
	 * ## OPTIONS
	 *
	 * [--heartbeat=<heartbeat>]
	 * : Enable (1) or disable (0) the queued tasks by the admin AJAX heartbeat call.
	 *
	 * [--heartbeat_ttl=<heartbeat_ttl>]
	 * : set the heartbeat TTL interval (time-to-live in seconds).
	 *
	 * [--crons=<crons>]
	 * : Enable (1) or disable (0) the scheduled cron system.
	 *
	 *
	 * [--cron_status=<cron_status>]
	 * : Enable (1) or disable (0) the Crontab Automation (will try to register a crontab entry in your system).
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageSettingsAutomation --heartbeat=1 --heartbeat_ttl=1 --crons=1 --cron_status=1
	 *
	 * @when after_wp_load
	 */

	public function manageSettingsAutomation($args, $flags) {self::_command(__FUNCTION__, $args, self::_keyToUpper($flags));}
    /**
     *  Manage general settings for jetbackup plugin
     *
     * ## OPTIONS
     *
     * [--license_key=<license-key>]
     * : Enter your backup plugin license key here to activate premium features, including enhanced backup options and priority support. Ensure your license key is valid and active to maintain uninterrupted access to all benefits.
     *
     * [--timezone=<timezone>]
     * : Set timezone for jetbackup plugin
     *
     * [--community_languages=<bool>]
     * : Enable/Disable community languages delivered by our languages CDN.
     *
     * [--jetbackup_integration=<bool>]
     * : Enable/Disable API to server level JetBackup (Restore backups generated by your hosting provider's JetBackup).
     *
     * [--admin_top_menu_integration=<bool>]
     * : Enable/Disable Admin top menu bar integration.).
     *
     * [--manual_backups_retention=<int>]
     * : This setting determines how many manual backups to keep per destination. Set to 0 to disable retention and never delete backups.
     *
     * [--alternate_wp_config_location=<path>]
     * : Use this if your hosting provider uses a non-default wp-config location
     *
     * [--php_cli_location=<path>]
     * : JetBackup relies on PHP CLI for background operations, here you can set specific path for php binary.
     *
     * [--mysql_default_port=<int>]
     * : JetBackup will attempt to automatically detect the MySQL port from your system settings. If it cannot be determined, this value will be used as the default fallback.
     *
     * ## EXAMPLES
     *
     *     wp jetbackup manageSettingsGeneral --license_key=xxxx --timezone=UTC --jetbackup_integration=1 --manual_backups_retention=0 --alternate_wp_config_location /home/user/config/wp-config.php --php_cli_location=/usr/bin/php --mysql_default_port=3306
     *
     * @when after_wp_load
     */

	public function manageSettingsGeneral($args, $flags) { self::_command(__FUNCTION__, $args, self::_keyToUpper($flags)); }

	/**
	 *  Manage log settings
	 *
	 * ## OPTIONS
	 *
	 * [--debug=<debug>]
	 * : enable or disable debug logging.
	 *
	 * [--log_rotate=<log_rotate>]
	 * : specify the number of days to retain log files. older logs beyond this duration will be automatically deleted. set this value to 0 to keep logs indefinitely.
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageSettingsLogging --debug=1 --log_rotate=7
	 *
	 * @when after_wp_load
	 */
	public function manageSettingsLogging($args, $flags) { self::_command(__FUNCTION__, $args, self::_keyToUpper($flags)); }


	/**
	 *  Manage maintenance settings
	 *
	 * ## OPTIONS
	 *
	 * [--maintenance_queue_hours_ttl=<maintenance_queue_hours_ttl>]
	 * : clear completed items from the queue table after a specified number of hours. set to 0 to disable.
	 *
	 * [--maintenance_download_items_ttl=<maintenance_download_items_ttl>]
	 * : After the specified number of hours, expired items will be automatically deleted. Enter a value in hours. set to 0 to disable.
	 *
	 * [--maintenance_download_limit=<maintenance_download_items_ttl>]
	 * : Sets the max active downloads. If the limit is reached, clear one to start a new download. set to 0 to disable.
	 *
	 * [--maintenance_queue_alerts_ttl=<maintenance_queue_alerts_ttl>]
	 * : clear system alerts after a specified number of hours. set to 0 to disable.
	 *
	 * [--config_export_rotate=<config_export_rotate>]
	 * : specify the number of exported config backups to keep. older exports will be deleted. set to 0 to keep indefinitely.
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageSettingsMaintenance --maintenance_queue_hours_ttl=24 --maintenance_download_limit=5 --maintenance_download_items_ttl=72 --maintenance_queue_alerts_ttl=72 --config_export_rotate=2
	 *
	 * @when after_wp_load
	 */
	public function manageSettingsMaintenance($args, $flags) { self::_command(__FUNCTION__, $args, self::_keyToUpper($flags)); }


	/**
	 *  Manage notification settings
	 *
	 * ## OPTIONS
	 *
	 * [--emails=<emails>]
	 * : enable or disable email notifications.
	 *
	 * [--alternate_email=<alternate_email>]
	 * : set an alternate email address for notifications instead of the default admin email.
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageSettingsNotifications --emails=1 --alternate_email=youremail@gmail.com
	 *
	 * @when after_wp_load
	 */
	public function manageSettingsNotifications($args, $flags) { self::_command(__FUNCTION__, $args, self::_keyToUpper($flags)); }


	/**
	 *  Manage performance settings
	 *
	 * ## OPTIONS
	 *
	 * [--read_chunk_size=<read_chunk_size>]
	 * : set the chunk size for file uploads. affects upload speed and stability.
	 *
	 * [--execution_time=<execution_time>]
	 * : define the maximum execution time (in seconds) for queued tasks. applies only to web-based tasks.
	 *
	 * [--sql_cleanup_revisions=<sql_cleanup_revisions>]
	 * : enable or disable cleaning up old revisions before dumping databases.
	 *
	 * [--use_default_excludes=<use_default_excludes>]
	 * : enable or disable the default file exclude list for backups.
	 *
	 * [--exclude_nested_sites=<exclude_nested_sites>]
	 * : exclude WordPress installations within the first subdirectory level.
	 *
	 * [--use_default_db_excludes=<use_default_db_excludes>]
	 * : exclude known temporary database tables.
	 *
	 * [--gzip_compress_archive=<gzip_compress_archive>]
	 * : enable or disable gzip compression for backup archives.
	 *
	 * [--gzip_compress_db=<gzip_compress_db>]
	 * : enable or disable gzip compression for database backups.
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageSettingsPerformance --read_chunk_size=2097152 --execution_time=10 --sql_cleanup_revisions=1 --use_default_excludes=1 --gzip_compress_archive=1
	 *
	 * @when after_wp_load
	 */
	public function manageSettingsPerformance($args, $flags) { self::_command(__FUNCTION__, $args, self::_keyToUpper($flags)); }


	/**
	 *  Manage restore settings
	 *
	 * ## OPTIONS
	 *
	 * [--restore_compatibility_check=<restore_compatibility_check>]
	 * : enable or disable checking system compatibility before restoring a backup.
	 *
	 * [--restore_allow_cross_domain=<bool>]
	 * : allow (1) or disallow (0) restoring backups across different domains.
	 *
	 * [--restore_alternate_path=<bool>]
	 * : enable (1) or disable (0) using alternate public restore path
	 *
	 *  [--restore_wp_content_only=<bool>]
	 *  : enable (1) or disable (0) limit restore to wp-content folder only
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageSettingsRestore --restore_compatibility_check=1 --restore_allow_cross_domain=1 --restore_alternate_path=1 --restore_wp_content_only=1
	 *
	 * @when after_wp_load
	 */
	public function manageSettingsRestore($args, $flags) { self::_command(__FUNCTION__, $args, self::_keyToUpper($flags)); }

	/**
	 *  Manage Integration settings
	 *
	 * ## OPTIONS
	 *
	 * [--integrations=<array>]
	 * : Enable integrations
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageSettingsIntegrations --integrations='["Elementor","Supercache", "Woocommerce"]'
	 *
	 * @when after_wp_load
	 */
	public function manageSettingsIntegrations($args, $flags) {
		if (isset($flags[Integrations::INTEGRATIONS])) $flags[Integrations::INTEGRATIONS] = self::_argsToArray($flags[Integrations::INTEGRATIONS]);
		self::_command(__FUNCTION__, $args, $flags);
	}


	/**
	 *  Manage security settings
	 *
	 * ## OPTIONS
	 *
	 * [--mfa_enabled=<bool>]
	 * : enable (1) or disable (0) two-factor authentication for additional security.
	 *
	 * [--alternate_data_folder=<alternate_data_folder>]
	 * : specify a custom data directory for storing configuration and backup data.
	 *
	 * [--daily_checksum_check=<daily_checksum_check>]
	 * : enable or disable daily checksum verification of WordPress core files.
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageSettingsSecurity --mfa_enabled=1 --daily_checksum_check=1 --alternate_data_folder=/custom/path
	 *
	 * @when after_wp_load
	 */
	public function manageSettingsSecurity($args, $flags) { self::_command(__FUNCTION__, $args, self::_keyToUpper($flags)); }


	/**
	 *  Manage update settings
	 *
	 * ## OPTIONS
	 *
	 * [--update_tier=<update_tier>]
	 * : set the update tier (release, rc, edge, or alpha) to determine how frequently updates are received.
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup manageSettingsUpdates --update_tier=release
	 *
	 * @when after_wp_load
	 */
	public function manageSettingsUpdates($args, $flags) { self::_command(__FUNCTION__, $args, self::_keyToUpper($flags)); }

	/**
     *  Manage sendTestEmail settings
     *
     * ## OPTIONS
     *
     * --alternate_email=<alternate_email>
     * : Set alternate email (email notifications will be sent by default to the "Admin Email" (set in Settings > General), this will override this option)
     *
     * ## EXAMPLES
     *
     *     wp jetbackup sendTestEmail --alternate_email=youremail@gmail.com
     *
     * @when after_wp_load
     */
	public function sendTestEmail($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     *  Start over queue item
     *
     * ## OPTIONS
     *
     * --id=<id>
     * : id of the queue item
     *
     * ## EXAMPLES
     *
     *     wp jetbackup startOverQueueItem --id=5
     *
     * @when after_wp_load
     */

    public function startOverQueueItem($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

    /**
     *  Validate destination
     *
     * ## OPTIONS
     *
     * --id=<id>
     * : id of the destination to validate
     *
     * ## EXAMPLES
     *
     *     wp jetbackup validateDestination --id=5
     *
     * @when after_wp_load
     */
    public function validateDestination($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 *  Execute JetBackup's internal cron scheduler
	 *
	 * ## OPTIONS
	 *
	 * [--debug]
	 * : Add more debug verbosity to output
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup executeCron --debug
	 *
	 * @when after_wp_load
	 */
	public function executeCron($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }


	/**
	 *  Clears Complete queue items
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup clearCompletedQueueItems
	 *
	 * @when after_wp_load
	 */
	public function clearCompletedQueueItems($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	/**
	 *  Clear All Alerts
	 *
	 * ## EXAMPLES
	 *
	 *     wp jetbackup clearAlerts
	 *
     * @when after_wp_load
	 */
	public function clearAlerts($args, $flags) { self::_command(__FUNCTION__, $args, $flags); }

	// Some flags such as --path are taken by wpcli, so we need to use alternate names and translate back
	private static function translateFlags (array $flags) : array {
		if (empty($flags)) return [];
		foreach ($flags as $key => $value) {
			if (isset(self::FLAGS[$key])) {
				$flags[self::FLAGS[$key]] = $value;
				unset($flags[$key]);
			}
		}
		return $flags;
	}

	private static function _command($func, $args, $flags) {

		$method = "\JetBackup\Ajax\Calls\\" . ucfirst($func);

		$output = isset($flags['output']) && in_array($flags['output'], CLI::OUTPUT_TYPES) ? $flags['output'] : CLI::OUTPUT_TYPE_TABLE;
		if(isset($flags['output'])) unset($flags['output']);

		if(isset($flags['sort']) && $flags['sort'] == 'desc') $flags['sort'] = ['_id' => 'desc'];
		else $flags['sort'] = ['_id' => 'asc'];

		$flags = self::translateFlags($flags);

		/** @var iAjax $call */
		$call = new $method();
		$call->setData($flags);
		$call->setCLI(true);

		try {
			if (Factory::getSettingsSecurity()->isMFAEnabled() && !Factory::getSettingsSecurity()->isMFAAllowCLI()) throw new AjaxException('CLI Mode is not available when MFA Enabled, you override this through the Settings -> Security');
			$call->execute();
		} catch (AjaxException $e) {
			$message = $e->getMessage();
			if($e->getData()) $message = vsprintf($message, $e->getData());
			self::error($message);
		}

		if($call->getResponseMessage()) self::line($call->getResponseMessage());
		if($list = $call->getResponseData()) {
			if(!isset($list[0])) $list = [$list];
			self::output($list, array_keys($list[0]), $output);
		}
	}

	private static function output($data, $headers, $type) {
		error_reporting(E_ALL & ~E_DEPRECATED); // issue 247
		WP_CLI\Utils\format_items($type, $data, $headers);
	}

	private static function line($text) {
		WP_CLI::line($text);
	}

	private static function error($text) {
		WP_CLI::error($text);
	}

	private static function success($text) {
		WP_CLI::success($text);
	}
}
