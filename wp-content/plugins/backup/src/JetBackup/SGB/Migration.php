<?php

namespace JetBackup\SGB;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use Exception;
use JetBackup\Alert\Alert;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Destination\Destination;
use JetBackup\Destination\ScanDirIterator;
use JetBackup\Destination\Vendors\Box\Box;
use JetBackup\Destination\Vendors\DropBox\DropBox;
use JetBackup\Destination\Vendors\FTP\FTP;
use JetBackup\Destination\Vendors\GoogleDrive\GoogleDrive;
use JetBackup\Destination\Vendors\OneDrive\Client\Client;
use JetBackup\Destination\Vendors\OneDrive\OneDrive;
use JetBackup\Destination\Vendors\pCloud\pCloud;
use JetBackup\Destination\Vendors\S3\S3;
use JetBackup\Destination\Vendors\SFTP\SFTP;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\IOVanishedException;
use JetBackup\Exception\QueueException;
use JetBackup\Exception\SGBMigrationException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\License\License;
use JetBackup\Schedule\Schedule;
use JetBackup\Schedule\ScheduleItem;
use JetBackup\Wordpress\MySQL;
use JetBackup\Wordpress\Wordpress;
use phpseclib3\Crypt\AES;
use SleekDB\Exceptions\InvalidArgumentException;
use stdClass;

class Migration  {

    const BG_SCHEDULE_INTERVAL_HOURLY = 0;
    const BG_SCHEDULE_INTERVAL_DAILY = 1;
    const BG_SCHEDULE_INTERVAL_WEEKLY = 2;
    const BG_SCHEDULE_INTERVAL_MONTHLY = 3;

    const BG_FIRST_DAY_OF_MONTH = 1;
    const BG_MIDDLE_OF_MONTH = 2;
    const BG_LAST_DAY_OF_MONTH = 3;


    const SG_STORAGE_FTP = 1;
	const SG_STORAGE_DROPBOX = 2;
	const SG_STORAGE_GOOGLE_DRIVE = 3;
	const SG_STORAGE_AMAZON = 4;
	const SG_STORAGE_ONE_DRIVE = 5;
	const SG_STORAGE_P_CLOUD = 7;
	const SG_STORAGE_BOX = 8;
	
	const SG_CONFIG = 'sg_config';
	const SG_PRO_NAMES = [ 'backup-guard-gold', 'backup-guard-platinum', 'backup-guard-silver'];

    private MySQL $_db;
	private string $_prefix;
	private array $_settings = [];
	private array $_destinations = [];
	/** @var Schedule[] */
    private array $_schedules = [];


    // Mapping V2 day intervals to corresponding V3 schedule days
    private array $bgDayIntervalMap = [
        self::BG_FIRST_DAY_OF_MONTH   => 1,
        self::BG_MIDDLE_OF_MONTH      => 14,
        self::BG_LAST_DAY_OF_MONTH    => 28
    ];

	/**
	 * 
	 */
    public function __construct() {
		$this->_db = new MySQL();
        $this->_prefix = $this->_db->getPrefix();
    }

	/**
	 * @return void
	 * @throws DBException
	 * @throws IOException
	 * @throws QueueException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function migrate():void {

		$settings = Factory::getConfig();
		if($settings->isSGBLegacyConverted()) return;



		try {

			$this->_migrateLegacyPluginDirectory();
			
			if(in_array($this->_prefix . self::SG_CONFIG, $this->_db->listTables())) {
				$this->_migrateConfig();
				$this->_migrateDestinations();
				$this->_migrateSchedules();
				$this->_migrateBackupJob();
				$this->_migrateBackups();
				$this->_addReindexDestinations();
			}

		} catch(Exception $e) {
			// We are stopping after one try or else it will flood the system
			Alert::add("Migration failed", "The migration for version 3.x has been failed with the following error: " . $e->getMessage(), Alert::LEVEL_CRITICAL);
		}

		$settings->setSGBLegacyConverted(true);
		$settings->save();
	}

	private function _addReindexDestinations():void {
		$list = Destination::query()->getQuery()->fetch();

		foreach($list as $destination_details) {
			$destination = new Destination($destination_details[JetBackup::ID_FIELD]);
			$destination->addToQueue();
		}
	}
	
	private static function _decrypt($encrypted, $key) {
		if (strlen($encrypted) < 16)
			throw new \Exception("Invalid encrypted data: Insufficient length.");

		$iv   = substr($encrypted, 0, 16);
		$data = substr($encrypted, 32);

		$cipher = new AES('cbc');
		$cipher->setKeyLength(256);
		$cipher->setKey(hex2bin(hash('sha256', $key)));
		$cipher->setIV($iv);

		try {
			$decrypted = $cipher->decrypt($data);
		} catch (\Exception $e) {
			throw new \Exception("Decryption failed: Invalid key or corrupted data.");
		}
		return $decrypted;
	}

	/**
	 * @return array
	 * @throws DBException
	 */
	private function _getSettings():array {

		if(!$this->_settings) {
			$sql = "SELECT *
				FROM `$this->_prefix" . self::SG_CONFIG . "`
				WHERE ckey LIKE '%%SG_%%'";
			$results = $this->_db->query($sql);

			if (empty($results)) return [];
			foreach($results as $data) $this->_settings[$data->ckey] = $data->cvalue;
		}

		return $this->_settings;
	}

	/**
	 * @return void
	 */
    private function _migrateLegacyPluginDirectory():void {

        $plugins_path = Factory::getWPHelper()->getWordPressHomedir() . Wordpress::WP_CONTENT . JetBackup::SEP . Wordpress::WP_PLUGINS;

        foreach(self::SG_PRO_NAMES as $name) {
            $name_path = $plugins_path . JetBackup::SEP . $name . JetBackup::SEP . 'backup-guard-pro.php';
            if (!file_exists($name_path)) continue;

            rename($name_path, $name_path . '_disabled');
            
			$legacy_cron_path = $plugins_path  . JetBackup::SEP . $name . JetBackup::SEP . 'public' . JetBackup::SEP . 'cron';
            $new_cron_path = $plugins_path . JetBackup::SEP . JetBackup::PLUGIN_NAME . JetBackup::SEP . 'public' . JetBackup::SEP . 'cron';

            if (!file_exists($legacy_cron_path) || !file_exists($new_cron_path) || is_link($legacy_cron_path)) continue;

            rename($legacy_cron_path, $legacy_cron_path . '_disabled');
            if (function_exists('symlink')) {
	            symlink($new_cron_path, $legacy_cron_path);
	            Alert::add('Legacy Plugin Found', 'Legacy JetBackup found, data imported and converted', Alert::LEVEL_INFORMATION);
            } else {
	            Alert::add('Legacy Plugin Convert Error', "Failed created symlink from $legacy_cron_path to $new_cron_path", Alert::LEVEL_CRITICAL);
            }


        }
    }

	/**
	 * @return void
	 * @throws DestinationException
	 * @throws DBException
	 * @throws Exception
	 */
	private function _migrateDestinations():void {

        $config = $this->_getSettings();
		$legacy_chunk_size = $config['SG_BACKUP_CLOUD_UPLOAD_CHUNK_SIZE'] ?? 1;

		// Create S3 destination
	    if (isset($config['SG_AMAZON_KEY']) && $config['SG_AMAZON_KEY'] != '') {

			$region = $config['SG_AMAZON_BUCKET_REGION'];
		    if(preg_match('/s3\.([a-z0-9-]+)\.amazonaws\.com/', $region, $matches)) $region = $matches[1];
			
			$destination = new Destination();
		    $destination->setName('S3 Legacy Converted');
		    $destination->setDefault(false);
		    $destination->setType(S3::TYPE);
		    $destination->setPath('/' . trim($config['SG_STORAGE_BACKUPS_FOLDER_NAME'], '/.'));
		    $destination->setChunkSize(max($legacy_chunk_size, 5));
		    $destination->setNotes('Legacy Converted');
		    $destination->setOptions((object) [
				'access_key'            => $config['SG_AMAZON_KEY'],
				'secret_key'            => $config['SG_AMAZON_SECRET_KEY'],
				'region'                => $region,
				'bucket'                => $config['SG_AMAZON_BUCKET'],
				'endpoint'              => 's3.{region}.amazonaws.com',
				'verifyssl'             => false,
				'retries'               => 5,
				'extrafields'           => new stdClass(),
				'keepalive_timeout'     => 60,
				'keepalive_requests'    => 100,
		    ]);
			$destination->save();
			
			$this->_destinations[self::SG_STORAGE_AMAZON] = $destination->getId();
	    }

		// Create FTP destination
		if (isset($config['SG_STORAGE_FTP_CONNECTED']) && $config['SG_STORAGE_FTP_CONNECTED'] == 1 && isset($config['SG_STORAGE_CONNECTION_METHOD']) && $config['SG_STORAGE_CONNECTION_METHOD'] == 'ftp') {

			$destination = new Destination();
			$destination->setName('FTP Legacy Converted');
			$destination->setDefault(false);
			$destination->setType(FTP::TYPE);
			$destination->setPath('./' . trim(trim($config['SG_FTP_ROOT_FOLDER'], '/.') . '/' . trim($config['SG_STORAGE_BACKUPS_FOLDER_NAME'], '/.'), '/'));
			$destination->setChunkSize($legacy_chunk_size);
			$destination->setNotes('Legacy Converted');
			$destination->setOptions((object) [
				'server'                => $config['SG_FTP_HOST'],
				'port'                  => $config['SG_FTP_PORT'],
				'username'              => $config['SG_FTP_USER'],
				'password'              => self::_decrypt(base64_decode($config['SG_FTP_PASSWORD']),NONCE_SALT) ?? 'dummy_password',
				'timeout'               => 60,
				'retries'               => 5,
				'ssl'                   => false,
				'ignore_self_signed'    => false,
				'passive_mode'          => $config['SG_FTP_PASSIVE_MODE'],
			]);
			$destination->save();

			$this->_destinations[self::SG_STORAGE_FTP] = $destination->getId();
		}

		// Create DropBox destination
		if (isset($config['SG_DROPBOX_REFRESH_TOKEN']) && $config['SG_DROPBOX_REFRESH_TOKEN'] != '') {

			$destination = new Destination();
			$destination->setName('DropBox Legacy Converted');
			$destination->setDefault(false);
			$destination->setType(DropBox::TYPE);
			$destination->setPath('/' . trim($config['SG_STORAGE_BACKUPS_FOLDER_NAME'], '/.'));
			$destination->setChunkSize($legacy_chunk_size);
			$destination->setNotes('Legacy Converted');
			$destination->setOptions((object) [
				'retries'               => 5,
				'access_token'          => $config['SG_DROPBOX_ACCESS_TOKEN'] ?? 'dummy',
				'refresh_token'         => $config['SG_DROPBOX_REFRESH_TOKEN'],
				'token_fetch_time'      => time(),
				'access_token_expiry'   => 253386463868,
				
				// This is for legacy migration only
				'client_id'             => 'backup-guard',
				'client_secret'         => 's8crjkls7f9wqtd',
			]);
			$destination->save();

			$this->_destinations[self::SG_STORAGE_DROPBOX] = $destination->getId();
		}

		// Create Box destination
	    if (isset($config['SG_BOX_REFRESH_TOKEN']) && $config['SG_BOX_REFRESH_TOKEN'] != '') {

		    $destination = new Destination();
		    $destination->setName('Box Legacy Converted');
		    $destination->setDefault(false);
		    $destination->setType(Box::TYPE);
		    $destination->setPath('/' . trim($config['SG_STORAGE_BACKUPS_FOLDER_NAME'], '/.'));
		    $destination->setChunkSize(max($legacy_chunk_size, 20));
		    $destination->setNotes('Legacy Converted');
		    $destination->setOptions((object) [
			    'retries'               => 5,
			    'token'                 => 'dummy',
			    'refresh_token'         => $config['SG_BOX_REFRESH_TOKEN'],
			    'token_expires'         => 0,
		    ]);
		    $destination->save();

		    $this->_destinations[self::SG_STORAGE_BOX] = $destination->getId();
	    }
		
		// Create pCloud destination
		if (isset($config['SG_P_CLOUD_ACCESS_TOKEN']) && $config['SG_P_CLOUD_ACCESS_TOKEN'] != '') {

			$destination = new Destination();
			$destination->setName('pCloud Legacy Converted');
			$destination->setDefault(false);
			$destination->setType(pCloud::TYPE);
			$destination->setPath('/' . trim($config['SG_STORAGE_BACKUPS_FOLDER_NAME'], '/.'));
			$destination->setChunkSize($legacy_chunk_size);
			$destination->setNotes('Legacy Converted');
			$destination->setOptions((object) [
				'retries'               => 5,
				'token'                 => $config['SG_P_CLOUD_ACCESS_TOKEN'],
				'api_url'               => isset($config['SG_P_CLOUD_LOCATION_ID']) && $config['SG_P_CLOUD_LOCATION_ID'] == 2 ? 'eapi.pcloud.com' : 'api.pcloud.com',
			]);
			$destination->save();

			$this->_destinations[self::SG_STORAGE_P_CLOUD] = $destination->getId();
		}

		// Create OneDrive destination
		if (isset($config['SG_ONE_DRIVE_REFRESH_TOKEN']) && $config['SG_ONE_DRIVE_REFRESH_TOKEN'] != '') {

			$destination = new Destination();
			$destination->setName('OneDrive Legacy Converted');
			$destination->setDefault(false);
			$destination->setType(OneDrive::TYPE);
			$destination->setPath('/' . trim($config['SG_STORAGE_BACKUPS_FOLDER_NAME'], '/.'));
			$destination->setChunkSize($legacy_chunk_size);
			$destination->setNotes('Legacy Converted');
			$destination->setOptions((object) [
				'retries'               => 5,
				'http_version'          => Client::HTTP_VERSION_DEFAULT,
				'access_token'          => 'dummy',
				'refresh_token'         => $config['SG_ONE_DRIVE_REFRESH_TOKEN'],
				'token_fetch_time'      => 0,
				'access_token_expiry'   => 0,

				// This is for legacy migration only
				'client_id'             => '00652d36-9155-48cb-aac9-c31579261ba6',
				'client_secret'         => 'I.K8Q~qOAMv1LLVMcK_pJpPQQPuekhUtp12Jqa7.',
			]);
			$destination->save();

			$this->_destinations[self::SG_STORAGE_ONE_DRIVE] = $destination->getId();
		}

		// Create SFTP destination
        if(isset($config['SG_STORAGE_FTP_CONNECTED']) && $config['SG_STORAGE_FTP_CONNECTED'] == 1 && isset($config['SG_STORAGE_CONNECTION_METHOD']) && $config['SG_STORAGE_CONNECTION_METHOD'] == 'sftp') {

	        $destination = new Destination();
	        $destination->setName('SFTP Legacy Converted');
	        $destination->setDefault(false);
	        $destination->setType(SFTP::TYPE);
	        $destination->setPath('./' . trim(trim($config['SG_FTP_ROOT_FOLDER'], '/.') . '/' . trim($config['SG_STORAGE_BACKUPS_FOLDER_NAME'], '/.'), '/'));
	        $destination->setChunkSize($legacy_chunk_size);
	        $destination->setNotes('Legacy Converted');
	        $destination->setOptions((object) [
		        'host'                  => $config['SG_FTP_HOST'],
		        'port'                  => $config['SG_FTP_PORT'],
		        'username'              => $config['SG_FTP_USER'],
		        'password'              => self::_decrypt(base64_decode($config['SG_FTP_PASSWORD']),NONCE_SALT) ?? 'dummy_password',
		        'timeout'               => 60,
		        'retries'               => 5,
		        'privatekey'            => '',
		        'passphrase'            => '',
	        ]);
	        $destination->save();

	        $this->_destinations[self::SG_STORAGE_FTP] = $destination->getId();
        }

		// Create googleDrive destination
        if(isset($config['SG_GOOGLE_DRIVE_REFRESH_TOKEN']) && $config['SG_GOOGLE_DRIVE_REFRESH_TOKEN'] != '') {

	        $destination = new Destination();
	        $destination->setName('GoogleDrive Legacy Converted');
	        $destination->setDefault(false);
	        $destination->setType(GoogleDrive::TYPE);
	        $destination->setPath('/' . trim($config['SG_STORAGE_BACKUPS_FOLDER_NAME'], '/.'));
	        $destination->setChunkSize($legacy_chunk_size);
	        $destination->setNotes('Legacy Converted');
	        $destination->setOptions((object) [
		        'retries'               => 5,
		        'token'                 => json_encode([ 'created' => 0, 'expires_in' => 0 ]),
		        'refresh_token'         => $config['SG_GOOGLE_DRIVE_REFRESH_TOKEN'],

		        // This is for legacy migration only
		        'client_id'             => '1030123017859-vfdlqkjhiuuu5n36pbov93v9ruo6jpj5.apps.googleusercontent.com',
		        'client_secret'         => 'oUcZwC17q5ZSbYahnQkGYpyH',
	        ]);
	        $destination->save();

	        $this->_destinations[self::SG_STORAGE_GOOGLE_DRIVE] = $destination->getId();
        }
    }

	/**
	 * @return void
	 * @throws SGBMigrationException
	 */
    private function _migrateSchedules():void {

        try {
			$sql = "SELECT *
					FROM `{$this->_prefix}sg_schedule`";
            if (!($results = $this->_db->query($sql))) return;

            foreach($results as $res) {

                $options = json_decode($res->schedule_options);

                $schedule = new Schedule();
                $schedule->setName('Legacy_converted_' . $res->label);

                switch ($options->interval) {
                    case self::BG_SCHEDULE_INTERVAL_HOURLY:
                        $schedule->setType(Schedule::TYPE_HOURLY);
                        $schedule->setIntervals(1);
					break;

                    case self::BG_SCHEDULE_INTERVAL_DAILY:
                        $schedule->setType(Schedule::TYPE_DAILY);
                        $schedule->setIntervals([1]);
                    break;

                    case self::BG_SCHEDULE_INTERVAL_WEEKLY:
                        $schedule->setType(Schedule::TYPE_WEEKLY);
                        $schedule->setIntervals(!empty($options->dayOfInterval) ? $options->dayOfInterval : 1);
                    break;

                    case self::BG_SCHEDULE_INTERVAL_MONTHLY:
                        $schedule->setType(Schedule::TYPE_MONTHLY);
                        $schedule->setIntervals([$this->bgDayIntervalMap[!empty($options->dayOfInterval) ? $options->dayOfInterval : 1]]);
                        break;
                }

				$schedule->setHidden(false);
				$schedule->setDefault(false);
				$schedule->save();

                $this->_schedules[] = $schedule;
            }


        } catch(Exception $e) {
            throw new SGBMigrationException($e->getMessage(), $e->getCode());
        }
    }

	/**
	 * @throws IOVanishedException
	 */
	private function _migrateBackups():void {
		

		$legacy_upload_folder = Factory::getWPHelper()->getWordPressHomedir() . Factory::getWPHelper()->getUploadDir() . JetBackup::SEP . 'jetbackup';
		if(!file_exists($legacy_upload_folder)) return;
		
		$scan = new ScanDirIterator($legacy_upload_folder);

		while($file = $scan->next()) {
			$path = $file->getFullPath();
			if(!str_ends_with($path, BackupJob::SNAPSHOT_SGBP_SUFFIX)) continue;
			$target = Factory::getLocations()->getBackupsDir() . JetBackup::SEP . basename($path);
			rename($path, $target);
			chmod($target, 0600);
		}
	}
	
	/**
	 * @return void
	 * @throws SGBMigrationException
	 */
	private function _migrateBackupJob():void {

        try {
			$sql = "SELECT *
					FROM `{$this->_prefix}sg_schedule`";
            $results = $this->_db->query($sql);
	        if(!$results) return;

            $config = $this->_getSettings();
			
            foreach ($results as $res) {
                $schedule_options = json_decode($res->schedule_options);
                $backup_options = json_decode($res->backup_options);

	            $contains = 0;
                if ($backup_options->SG_ACTION_BACKUP_DATABASE_AVAILABLE) $contains |= BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE;
                if ($backup_options->SG_ACTION_BACKUP_FILES_AVAILABLE) $contains |= BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR;

				$destinations = [];	            
	            if($backup_options->SG_BACKUP_UPLOAD_TO_STORAGES) {
		            $legacy_storage = explode(',', $backup_options->SG_BACKUP_UPLOAD_TO_STORAGES);
					foreach($legacy_storage as $storage) {
						if(!isset($this->_destinations[$storage])) continue;
						$destinations[] = $this->_destinations[$storage];
					}
	            } 
				
				if(!sizeof($destinations)) {
					$destination = Destination::createDefaultDestination();
		            $destinations[] = $destination->getId();
	            }
				
				$schedule = $this->_schedules[0] ?? Schedule::getDefaultConfigSchedule();

	            $schedule_item = new ScheduleItem();
	            $schedule_item->setId($schedule->getId());
	            $schedule_item->setType($schedule->getType());
	            $schedule_item->setRetain(isset($config['SG_AMOUNT_OF_BACKUPS_TO_KEEP']) ? (int) $config['SG_AMOUNT_OF_BACKUPS_TO_KEEP'] : 30);

	            $job = new BackupJob();
	            $job->setName('Legacy Job');
	            $job->setType(BackupJob::TYPE_ACCOUNT);
				$job->setContains($contains);
	            $job->setEnabled(true);
	            $job->setHidden(false);
				$job->setDefault(false);
                $job->setScheduleTime(!empty($schedule_options->intervalHour) ? $schedule_options->intervalHour.':00' : 1 . ':00');
                $job->setDestinations($destinations);
	            $job->setSchedules([$schedule_item]);
	            $job->setExcludeDatabases(isset($config['SG_TABLES_TO_EXCLUDE']) ? explode(',', $config['SG_TABLES_TO_EXCLUDE']) : []);
	            $job->setExcludes(isset($config['SG_PATHS_TO_EXCLUDE']) ? explode(',', $config['SG_PATHS_TO_EXCLUDE']) : []);
	            $job->save();
            }

        } catch (Exception $e) {
            throw new SGBMigrationException($e->getMessage(), $e->getCode());
        }
    }

	/**
	 * @return void
	 * @throws DBException
	 * @throws IOException
	 */
    private function _migrateConfig():void {

	    $settings = $this->_getSettings();
		foreach ($settings as $key=>$value) if(trim($value) == '') unset($settings[$key]);

		$config = Factory::getConfig();
		$general = Factory::getSettingsGeneral();
		$notifications = Factory::getSettingsNotifications();
		
		if(isset($settings['SG_TIMEZONE'])) $general->setTimeZone($settings['SG_TIMEZONE']);
	    if(isset($settings['SG_PHP_CLI_LOCATION'])) $general->setPHPCLILocation($settings['SG_PHP_CLI_LOCATION']);

		if(!License::isValid()) {
			if(isset($settings['SG_LICENSE_KEY'])) $config->setLicenseKey($settings['SG_LICENSE_KEY']);
			if(isset($settings['SG_LOCAL_KEY'])) $config->setLicenseLocalKey($settings['SG_LOCAL_KEY']);
			if(isset($settings['SG_LOCAL_KEY_LAST_CHECK_TS'])) $config->setLicenseLastCheck((int) $settings['SG_LOCAL_KEY_LAST_CHECK_TS']);
			if(isset($settings['SG_LOCAL_KEY_NEXT_CHECK_TS'])) $config->setLicenseNextCheck((int) $settings['SG_LOCAL_KEY_NEXT_CHECK_TS']);
		}

		if(isset($settings['SG_BACKUP_CURRENT_KEY'])) $config->setCronToken($settings['SG_BACKUP_CURRENT_KEY']);
		if(isset($settings['SG_NOTIFICATIONS_ENABLED'])) $notifications->setEmailsEnabled(!!$settings['SG_NOTIFICATIONS_ENABLED']);

		$config->save();
	    $general->save();
		$notifications->save();
    }
}


