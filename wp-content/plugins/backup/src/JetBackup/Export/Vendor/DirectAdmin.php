<?php

namespace JetBackup\Export\Vendor;

use JetBackup\Archive\Archive;
use JetBackup\Archive\Gzip;
use JetBackup\DirIterator\DirIterator;
use JetBackup\Entities\Util;
use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\DirIteratorFileVanishedException;
use JetBackup\Exception\ExportException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class DirectAdmin extends Vendor {

	private string $_destination;
	private string $_target;
	
	public function _build():string {

		$this->_destination = $this->getDestination() . JetBackup::SEP . 'tmp';
		$this->_target = $this->getDestination() . JetBackup::SEP . $this->getUsername() . Archive::ARCHIVE_EXT;

		if(!file_exists($this->_destination)) mkdir($this->_destination, 0700);

		/**
		 * Structure
		 * Filename: username.tar.gz
		 * 
		 * |-- backup/
		 * |   |-- username_database.conf
		 * |   |-- username_database.sql
		 * |   |-- apache_owned_files.list
		 * |   |-- backup_options.list
		 * |   |-- crontab.conf
		 * |   |-- ticket.conf
		 * |   |-- user.conf
		 * |   |-- user.usage
		 * |   +-- domain.com/
		 * |       +-- domain.conf  
		 * +-- domains/
		 *     +-- domain.com/
		 *         |-- public_html/
		 *         +-- private_html -> ./public_html
		 */
		$this->getTask()->func([$this, '_createSkeleton']);
		$this->getTask()->func([$this, '_createHomedir']);
		$this->getTask()->func([$this, '_createJetBackupPlugin']);
		$this->getTask()->func([$this, '_createUserConf']);
		$this->getTask()->func([$this, '_createUserUsage']);
		$this->getTask()->func([$this, '_createTicketConf']);
		$this->getTask()->func([$this, '_createCrontabConf']);
		$this->getTask()->func([$this, '_createBackupOptionsList']);
		$this->getTask()->func([$this, '_createApacheOwnedFiles']);
		$this->getTask()->func([$this, '_createDomainConf']);
		$this->getTask()->func([$this, '_createDatabaseConf' ]);
		$this->getTask()->func([$this, '_createMySQLDump']);
		$this->getTask()->func([$this, '_archive']);
		$this->getTask()->func([$this, '_compress']);
		
		return $this->_target . '.gz';
	}

	public function _archive():void {

		$archive = new Archive($this->_target, false, Archive::OPT_SPARSE, 0, $this->getDestination());
		$archive->setLogController($this->getLogController());

		$this->getTask()->scan($this->_destination, function(DirIterator $scan, $data) use ($archive) {

			if (!$data->total_size) throw new ArchiveException('Invalid total tree size');

			$archive->setAppend(!($data->total_size == $data->current_pos));

			try {
				$fd = $archive->getFileFD();
				$current_file = $scan->next($fd ? $fd->tell() : 0);
			} catch (DirIteratorFileVanishedException $e) {
				$this->getLogController()->logMessage('[ WARNING ] File Vanished : ' . $e->getMessage());
				return;
			}

			if($scan->getSource() == $current_file->getName()) return;

			$this->getLogController()->logMessage("[". ($data->total_size - $data->current_pos)."/$data->total_size] Archiving: {$current_file->getName()}");

			try {

				$file = substr($current_file->getName(), strlen($this->_destination)+1);
				$archive->appendFileChunked($current_file, $file, function() use ($data) {

					$progress = $this->getTask()->getQueueItem()->getProgress();
					$progress->setMessage("Building DirectAdmin Backup Archive");
					$progress->setSubMessage("");
					$progress->setTotalSubItems($data->total_size);
					$progress->setCurrentSubItem($data->total_size - $data->current_pos);
					$this->getTask()->getQueueItem()->save();

					$this->getTask()->checkExecutionTime(function() use ($data) {

						$progress = $this->getTask()->getQueueItem()->getProgress();
						$progress->setMessage("Waiting for next cron iteration");
						$progress->setTotalSubItems($data->total_size);
						$progress->setCurrentSubItem($data->total_size - $data->current_pos);
						$this->getTask()->getQueueItem()->save();

					});

					return false;

				}, Factory::getSettingsPerformance()->getReadChunkSizeBytes());
			} catch(\Exception|ArchiveException $e) {
				//this will throw exception if the file has been changed more than 3 times
				$this->getLogController()->logError('[Export/DirectAdmin] Error while trying to archive: ' . $e->getMessage());
			}
		});

		$archive->save();

	}
	
	public function _compress():void {
		Gzip::compress(
			$this->_target,
			Gzip::DEFAULT_COMPRESS_CHUNK_SIZE,
			Gzip::DEFAULT_COMPRESSION_LEVEL,
			function() { $this->getTask()->checkExecutionTime(); }
		);
	}
	
	public function _createMySQLDump():void {

		$this->getLogController()->logMessage("Creating mysql dump file");

		$this->getTask()->foreach($this->getDatabaseTables(), function($i, $table_path) {

			$total_size = sizeof($this->getDatabaseTables());
			$progress = $this->getTask()->getQueueItem()->getProgress();
			$progress->setMessage("Building SQL dump");
			$progress->setSubMessage("Adding " .  basename($table_path));
			$progress->setTotalSubItems($total_size);
			$progress->setCurrentSubItem($i + 1); // Increment progress
			$this->getTask()->getQueueItem()->save();

			$this->getTask()->checkExecutionTime(function() use ($table_path, $total_size, $i) {

				$progress = $this->getTask()->getQueueItem()->getProgress();
				$progress->setSubMessage("Waiting for next cron iteration");
				$progress->setTotalSubItems($total_size);
				$progress->setCurrentSubItem($i + 1); // Maintain progress state
				$this->getTask()->getQueueItem()->save();

			});
			$this->getLogController()->logDebug("[_createMySQLDump] $i/$total_size table_path: $table_path");
			$this->getLogController()->logMessage("\t- Adding table " . basename($table_path) . " to dump file");
			$dump_file = $this->_destination . JetBackup::SEP . 'backup' . JetBackup::SEP . $this->getDatabase() . '.sql';
			$this->getTask()->fileMerge($table_path, $dump_file, 'combine_table_' . basename($table_path));
		}, 'combine_tables');
	}

	public function _createDatabaseConf():void {

		$this->getLogController()->logMessage("Creating database configuration file");

		$conf_params = [
			'alter_priv'            => 'Y',
			'alter_routine_priv'    => 'Y',
			'create_priv'           => 'Y',
			'create_routine_priv'   => 'Y',
			'create_tmp_table_priv' => 'Y',
			'create_view_priv'      => 'Y',
			'delete_priv'           => 'Y',
			'drop_priv'             => 'Y',
			'event_priv'            => 'Y',
			'execute_priv'          => 'Y',
			'grant_priv'            => 'N',
			'index_priv'            => 'Y',
			'insert_priv'           => 'Y',
			'lock_tables_priv'      => 'Y',
			'passwd'                => '*' . $this->getHashedPassword(),
			'references_priv'       => 'Y',
			'select_priv'           => 'Y',
			'show_view_priv'        => 'Y',
			'trigger_priv'          => 'Y',
			'update_priv'           => 'Y',
		];
		
		$rows = [];
		foreach ($conf_params as $key => $value) $rows[] = str_replace('_', '%5F', $key) . '=' . urlencode($value);
		$conf = $this->getUsername() . "=" . implode("&", $rows) . "\n";
		$conf .= $this->getDatabaseUser() . "=" . implode("&", $rows) . "\n";
		$conf .= "accesshosts=0=localhost\n";
		$conf .= "db_collation=CATALOG_NAME=def&DEFAULT_CHARACTER_SET_NAME=utf8&DEFAULT_COLLATION_NAME=utf8_general_ci&SCHEMA_COMMENT=&SCHEMA_NAME={$this->getDatabase()}&SQL_PATH=\n";

		file_put_contents($this->_destination . JetBackup::SEP . 'backup' . JetBackup::SEP . $this->getDatabase() . '.conf', $conf);
	}

	public function _createHomedir():void {

		$this->getLogController()->logMessage("Moving homedir to location");
		$public_folder = JetBackup::SEP . 'domains' . JetBackup::SEP . $this->getDomainOnly() . JetBackup::SEP . 'public_html';
		if($nested_folder = $this->getDomainSubFolder()) $public_folder .= JetBackup::SEP . $nested_folder;

		if (!file_exists(dirname($this->_destination . $public_folder))) mkdir(dirname($this->_destination . $public_folder), 0755, true);
		chmod($this->getHomedir(), 0755);
		rename($this->getHomedir(), $this->_destination . $public_folder);

		chdir($this->_destination);
		if (!file_exists('public_html') && !is_link('public_html')) {
			if (function_exists('symlink')) {
				symlink('.' .$public_folder, 'public_html');
			} else {
				$this->getLogController()->logError("Failed to link $public_folder to 'public_html' (PHP function symlink disabled)" );
				$this->getTask()->getQueueItem()->addError();
			}
		}

	}

	/**
	 * @return void
	 * @throws ExportException
	 *
	 * By default, JetBackup plugin is excluded during backup (to prevent issues during restore, not to 'cut our own branch')
	 * For an export package, we want to include JetBackup plugin as part of the package
	 *
	 */
	public function _createJetBackupPlugin():void {

		$this->getLogController()->logMessage("Adding JetBackup plugin to the package");
		$public_folder = $this->_destination . JetBackup::SEP . 'domains' . JetBackup::SEP . $this->getDomainOnly() . JetBackup::SEP . 'public_html';
		if($nested_folder = $this->getDomainSubFolder()) $public_folder .= JetBackup::SEP . $nested_folder;

		$plugin_local_path = Factory::getWPHelper()->getWordPressHomedir() .
		                     Wordpress::WP_CONTENT . JetBackup::SEP .
		                     Wordpress::WP_PLUGINS . JetBackup::SEP .
		                     JetBackup::PLUGIN_NAME;

		$public_folder .= JetBackup::SEP .  Wordpress::WP_CONTENT . JetBackup::SEP .
		                  Wordpress::WP_PLUGINS . JetBackup::SEP .
		                  JetBackup::PLUGIN_NAME;

		if (!file_exists($plugin_local_path)) throw new ExportException("Plugin local path not found [$plugin_local_path]");
		if (!file_exists($public_folder)) mkdir($public_folder, 0755, true);

		try {
			Util::cp($plugin_local_path, $public_folder, 0755, ['config.php']);
		} catch (Exception $e) {
			throw new ExportException($e->getMessage());
		}

	}

	public function _createSkeleton():void {

		$this->getLogController()->logMessage("Creating skeleton");

		$skeleton = [
			'backup'                                            => 0700,
			'backup' . JetBackup::SEP . $this->getDomainOnly()      => 0755,
			'domains'                                           => 0711,
			'domains' . JetBackup::SEP . $this->getDomainOnly()     => 0711,
		];

		foreach($skeleton as $directory => $mode) mkdir($this->_destination . JetBackup::SEP . $directory, $mode);
	}
	
	public function _createDomainConf():void {
		$this->getLogController()->logMessage("Creating domain configuration file");
		$target = $this->_destination . JetBackup::SEP . 'backup' . JetBackup::SEP . $this->getDomainOnly() . JetBackup::SEP . 'domain.conf';

		$data = [
			'UseCanonicalName'      => 'OFF',
			'active'                => 'yes',
			'bandwidth'             => 'unlimited',
			'cgi'                   => 'OFF',
			'defaultdomain'         => 'yes',
			'domain'                => $this->getDomainOnly(),
			'ip'                    => '10.0.0.1',
			'local_domain'          => '1',
			'open_basedir'          => 'ON',
			'php'                   => 'ON',
			'private_html_is_link'  => '1',
			'quota'                 => 'unlimited',
			'safemode'              => 'OFF',
			'ssl'                   => 'ON',
			'suspended'             => 'no',
			'username'              => $this->getUsername(),
		];

		$output = [];
		foreach ($data as $key => $value) $output[] = "$key=$value";
		if(!file_exists(dirname($target))) mkdir(dirname($target), 0755, true);
		file_put_contents($target, implode("\n", $output));
	}
	
	public function _createApacheOwnedFiles():void {
		$this->getLogController()->logMessage("Creating apache owned files file");
		touch($this->_destination . JetBackup::SEP . 'backup' . JetBackup::SEP . 'apache_owned_files.list');
	}
	
	public function _createBackupOptionsList():void {
		$this->getLogController()->logMessage("Creating backup options file");

		$data = [
			'database',
			'database_data',
			'database_data_aware',
			'domain',
			'email_data_aware',
			'trash_aware',
		];

		file_put_contents($this->_destination . JetBackup::SEP . 'backup' . JetBackup::SEP . 'backup_options.list', implode("\n", $data));
	}
	
	public function _createCrontabConf():void {
		$this->getLogController()->logMessage("Creating crontab");
		file_put_contents($this->_destination . JetBackup::SEP . 'backup' . JetBackup::SEP . 'crontab.conf', 'MAILTO=' . PHP_EOL);
	}
	
	public function _createTicketConf():void {

		$this->getLogController()->logMessage("Creating ticket configuration file");

		$data = [
			'ON'        => 'yes',
			'active'    => 'no',
			'email'     => $this->getEmailAddress(),
			'html'      => '',
			'new'       => 1,
			'newticket' => 0,
		];

		$output = [];
		foreach ($data as $key => $value) $output[] = "$key=$value";
		file_put_contents($this->_destination . JetBackup::SEP . 'backup' . JetBackup::SEP . 'ticket.conf', implode("\n", $output));
	}
	
	public function _createUserUsage():void {

		$this->getLogController()->logMessage("Creating user usage file");
		
		$data = [
			'bandwidth'                     => 0.0,
			'db_quota'                      => 0,
			'domainptr'                     => 0,
			'email_deliveries'              => 0,
			'email_deliveries_incoming'     => 0,
			'email_deliveries_outgoing'     => 0,
			'email_quota'                   => 36,
			'ftp'                           => 0,
			'inode'                         => 23,
			'mysql'                         => 1,
			'nemailf'                       => 0,
			'nemailml'                      => 0,
			'nemailr'                       => 0,
			'nemails'                       => 0,
			'nsubdomains'                   => 0,
			'other_quota'                   => 0,
			'quota'                         => 0.1328,
			'quota_without_system'          => 0.0000,
			'vdomains'                      => 1,
		];

		$output = [];
		foreach ($data as $key => $value) $output[] = "$key=$value";
		file_put_contents($this->_destination . JetBackup::SEP . 'backup' . JetBackup::SEP . 'user.usage', implode("\n", $output));
	}
	
	public function _createUserConf():void {

		$this->getLogController()->logMessage("Creating user configuration file");

		$data = [
			'account'                                   => 'ON',
			'additional_bandwidth'                      => 0,
			'aftp'                                      => 'OFF',
			'api_with_password'                         => 'yes',
			'bandwidth'                                 => 'unlimited',
			'catchall'                                  => 'OFF',
			'cgi'                                       => 'OFF',
			'clamav'                                    => 'OFF',
			'creator'                                   => 'admin',
			'cron'                                      => 'ON',
			'date_created'                              => date('D M j Y'),
			'demo'                                      => 'no',
			'dnscontrol'                                => 'OFF',
			'docsroot'                                  => './data/skins/evolution',
			'skin'                                      => 'evolution',
			'domain'                                    => $this->getDomainOnly(),
			'domainptr'                                 => 'unlimited',
			'email'                                     => $this->getEmailAddress(),
			'ftp'                                       => 'unlimited',
			'git'                                       => 'OFF',
			'inode'                                     => 'unlimited',
			'jail'                                      => 'OFF',
			'language'                                  => 'en',
			'login_keys'                                => 'OFF',
			'mysql'                                     => 'unlimited',
			'name'                                      => $this->getUsername(),
			'nemailf'                                   => 'unlimited',
			'nemailml'                                  => 'unlimited',
			'nemailr'                                   => 'unlimited',
			'nemails'                                   => 'unlimited',
			'notify_on_all_question_failures'           => 'yes',
			'notify_on_all_twostep_auth_failures'       => 'yes',
			'ns1'                                       => 'ns1.' . $this->getUsername() . '.da.direct',
			'ns2'                                       => 'ns2.' . $this->getUsername() . '.da.direct',
			'nsubdomains'                               => 'unlimited',
			'package'                                   => 'default',
			'php'                                       => 'ON',
			'quota'                                     => 'unlimited',
			'redis'                                     => 'OFF',
			'security_questions'                        => 'no',
			'spam'                                      => 'OFF',
			'ssh'                                       => 'OFF',
			'ssl'                                       => 'ON',
			'suspend_at_limit'                          => 'OFF',
			'suspended'                                 => 'no',
			'sysinfo'                                   => 'OFF',
			'twostep_auth'                              => 'no',
			'username'                                  => $this->getUsername(),
			'usertype'                                  => 'user',
			'vdomains'                                  => 'unlimited',
			'wordpress'                                 => 'ON',
			'zoom'                                      => 100,
			'ip'                                        => '10.0.0.1',
		];

		$output = [];
		foreach ($data as $key => $value) $output[] = "$key=$value";
		file_put_contents($this->_destination . JetBackup::SEP . 'backup' . JetBackup::SEP . 'user.conf', implode("\n", $output));
	}
}