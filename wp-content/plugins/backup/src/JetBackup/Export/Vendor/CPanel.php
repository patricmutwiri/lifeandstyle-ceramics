<?php

namespace JetBackup\Export\Vendor;

use Exception;
use JetBackup\Archive\Archive;
use JetBackup\Archive\Gzip;
use JetBackup\DirIterator\DirIterator;
use JetBackup\Entities\Util;
use JetBackup\Exception\ArchiveException;
use JetBackup\Exception\DirIteratorFileVanishedException;
use JetBackup\Exception\ExportException;
use JetBackup\Exception\GzipException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class CPanel extends Vendor {

	const CPANEL_VERSION = '11.116.0.11';
	
	private string $_destination;
	private string $_target;
	
	public function _build():string {

		$this->_destination = $this->getDestination() . JetBackup::SEP . 'tmp';
		$this->_target = $this->getDestination() . JetBackup::SEP . 'cpmove-' . $this->getUsername() . Archive::ARCHIVE_EXT;

		if(!file_exists($this->_destination)) mkdir($this->_destination, 0700);

		/**
		 * Structure
		 * Filename: cpmove-username.tar.gz
		 *
		 * |-- apache_tls/
		 * |-- bandwidth/
		 * |-- bandwidth_db/
		 * |-- counters/
		 * |-- cp/
		 * |   +-- username
		 * |-- cron/
		 * |-- customizations/
		 * |-- dnssec_keys/
		 * |-- dnszones/
		 * |-- domainkeys/
		 * |   |-- private/
		 * |   +-- public/
		 * |-- homedir/
		 * |   +-- public_html/
		 * |-- httpfiles/
		 * |-- ips/
		 * |-- locale/
		 * |-- logs/
		 * |-- mm/
		 * |-- mma/
		 * |   |-- priv/
		 * |   +-- pub/
		 * |-- mms/
		 * |-- mysql/
		 * |   |-- database.create
		 * |   +-- database.sql
		 * |-- mysql-timestamps/
		 * |   +-- mysql
		 * |-- psql/
		 * |-- resellerconfig/
		 * |-- resellerfeatures/
		 * |-- resellerpackages/
		 * |-- ssl/
		 * |-- sslcerts/
		 * |-- sslkeys/
		 * |-- suspended/
		 * |-- suspendinfo/
		 * |-- team/
		 * |-- userconfig/
		 * |-- userdata/
		 * |-- va/
		 * |-- vad/
		 * |-- vf/
		 * |-- autossl.json
		 * |-- homedir_paths
		 * |-- mysql.sql
		 * |-- mysql.sql-auth.json
		 * |-- shadow
		 * +-- version
		 */
		$this->getTask()->func([$this, '_createSkeleton']);
		$this->getTask()->func([$this, '_createHomedir']);
		$this->getTask()->func([$this, '_createJetBackupPlugin']);
		$this->getTask()->func([$this, '_createVersion']);
		$this->getTask()->func([$this, '_createAutoSSL']);
		$this->getTask()->func([$this, '_createUserData']);
		$this->getTask()->func([$this, '_createShadow']);
		$this->getTask()->func([$this, '_createHomedirPaths']);
		$this->getTask()->func([$this, '_createMySQLTimestemp']);
		$this->getTask()->func([$this, '_createMySQLAuth']);
		$this->getTask()->func([$this, '_createMySQLAuthJson']);
		$this->getTask()->func([$this, '_createMySQLCreate']);
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
					$progress->setMessage("Building cPanel Backup Archive");
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
				$this->getLogController()->logError('[Export/cPanel] Error while trying to archive: ' . $e->getMessage());
			}
		});

		$archive->save();

	}

	/**
	 * @return void
	 * @throws GzipException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function _compress():void {

		Gzip::compress(
			$this->_target,
			Gzip::DEFAULT_COMPRESS_CHUNK_SIZE,
			Gzip::DEFAULT_COMPRESSION_LEVEL,
			function($byteRead, $totalSize) {

				$progress = $this->getTask()->getQueueItem()->getProgress();
				$progress->setSubMessage('');
				$progress->setTotalSubItems($totalSize);
				$progress->setCurrentSubItem($byteRead);

				$this->getTask()->getQueueItem()->save();

				$this->getTask()->checkExecutionTime(function() {
					$this->getTask()->getQueueItem()->getProgress()->setMessage('[ Gzip ] Waiting for next cron iteration');
					$this->getTask()->getQueueItem()->save();
				});
			}
		);

	}

	public function _createMySQLTimestemp():void {
		$this->getLogController()->logMessage("Creating MySQL timestemps file");
		file_put_contents($this->_destination . JetBackup::SEP . 'mysql-timestamps' . JetBackup::SEP . 'mysql', time());
	}

	public function _createMySQLAuthJson():void {
		$this->getLogController()->logMessage("Creating MySQL auth json file");
		file_put_contents($this->_destination . JetBackup::SEP . 'mysql.sql-auth.json', json_encode([
			$this->getDatabaseUser()        => [
				'localhost' => [
					'auth_plugin'   => "mysql_native_password",
					'pass_hash'     => $this->getHashedPassword(),
				],
			],
			$this->getUsername()            => [
				'localhost' => [
					'pass_hash'     => $this->getHashedPassword(),
					'auth_plugin'   => "mysql_native_password",
				],
			],
		], JSON_PRETTY_PRINT));
	}

	public function _createSkeleton():void {

		$this->getLogController()->logMessage("Creating skeleton");

		$skeleton = [
			'apache_tls'                                => 0700,
			'bandwidth'                                 => 0700,
			'bandwidth_db'                              => 0700,
			'counters'                                  => 0700,
			'cp'                                        => 0700,
			'cron'                                      => 0700,
			'customizations'                            => 0700,
			'dnssec_keys'                               => 0755,
			'dnszones'                                  => 0700,
			'domainkeys'                                => 0700,
			'domainkeys' . JetBackup::SEP . 'private'   => 0700,
			'domainkeys' . JetBackup::SEP . 'public'    => 0700,
			'homedir'                                   => 0711,
			'httpfiles'                                 => 0700,
			'ips'                                       => 0700,
			'locale'                                    => 0700,
			'logs'                                      => 0700,
			'mm'                                        => 0700,
			'mma'                                       => 0700,
			'mma' . JetBackup::SEP . 'priv'             => 0700,
			'mma' . JetBackup::SEP . 'pub'              => 0700,
			'mms'                                       => 0700,
			'mysql'                                     => 0700,
			'mysql-timestamps'                          => 0700,
			'psql'                                      => 0700,
			'resellerconfig'                            => 0700,
			'resellerfeatures'                          => 0700,
			'resellerpackages'                          => 0700,
			'ssl'                                       => 0700,
			'sslcerts'                                  => 0700,
			'sslkeys'                                   => 0700,
			'suspended'                                 => 0700,
			'suspendinfo'                               => 0700,
			'team'                                      => 0700,
			'userconfig'                                => 0755,
			'userdata'                                  => 0700,
			'va'                                        => 0700,
			'vad'                                       => 0700,
			'vf'                                        => 0700,
		];

		foreach($skeleton as $directory => $mode) mkdir($this->_destination . JetBackup::SEP . $directory, $mode);
	}
	
	public function _createHomedirPaths():void {
		$this->getLogController()->logMessage("Creating homedir paths file");
		file_put_contents($this->_destination . JetBackup::SEP . 'homedir_paths', JetBackup::SEP . "home" . JetBackup::SEP . $this->getUsername());
	}
	
	public function _createShadow():void {
		$this->getLogController()->logMessage("Creating shadow file");
		file_put_contents($this->_destination . JetBackup::SEP . 'shadow', crypt(Util::generateRandomString(), '$6$' . uniqid()));
	}
	
	public function _createAutoSSL():void {
		$this->getLogController()->logMessage("Creating auto SSL configuration file");
		file_put_contents($this->_destination . JetBackup::SEP . 'autossl.json', json_encode([ 'excluded_domains' => [] ]));
	}
	
	public function _createVersion():void {
		file_put_contents($this->_destination . JetBackup::SEP . 'version', "pkgacct version: 10" . PHP_EOL . "archive version: 4");
	}
	
	public function _createHomedir():void {

		$this->getLogController()->logMessage("Moving homedir to location");
		$public_folder = $this->_destination . JetBackup::SEP . 'homedir' . JetBackup::SEP . 'public_html';
		if($nested_folder = $this->getDomainSubFolder()) $public_folder .= JetBackup::SEP . $nested_folder;

		chmod($this->getHomedir(), 0750);
		rename($this->getHomedir(), $public_folder);
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
		$public_folder = $this->_destination . JetBackup::SEP . 'homedir' . JetBackup::SEP . 'public_html';
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

			$dump_file = $this->_destination . JetBackup::SEP . 'mysql' . JetBackup::SEP . $this->getDatabase() . '.sql';
			$this->getLogController()->logDebug("[_createMySQLDump] dump_file: " . $dump_file);
			$this->getTask()->fileMerge($table_path, $dump_file, 'combine_table_' . basename($table_path));
		}, 'combine_tables');
		
	}

	public function _createMySQLAuth():void {
		$this->getLogController()->logMessage("Creating MySQL auth file");

		$output = "-- cPanel mysql backup" . PHP_EOL;
		$output .= "GRANT USAGE ON *.* TO '{$this->getUsername()}'@'localhost' IDENTIFIED BY PASSWORD '*{$this->getHashedPassword()}';" . PHP_EOL;
		$output .= "GRANT ALL PRIVILEGES ON `{$this->getDatabase()}`.* TO '{$this->getUsername()}'@'localhost';" . PHP_EOL;
		$output .= "GRANT USAGE ON *.* TO '{$this->getDatabaseUser()}'@'localhost' IDENTIFIED BY PASSWORD '*{$this->getHashedPassword()}';" . PHP_EOL;
		$output .= "GRANT ALL PRIVILEGES ON `{$this->getDatabase()}`.* TO '{$this->getDatabaseUser()}'@'localhost';" . PHP_EOL;
		file_put_contents($this->_destination . JetBackup::SEP . 'mysql.sql', $output);
	}

	public function _createMySQLCreate():void {

		$this->getLogController()->logMessage("Creating MySQL create file");
		
		$dump = [];
		$dump[] = "-- MySQL dump 10.13  Distrib 8.0.36, for Linux (x86_64)";
		$dump[] = "--";
		$dump[] = "-- Host: localhost    Database: {$this->getDatabase()}";
		$dump[] = "-- ------------------------------------------------------";
		$dump[] = "-- Server version       8.0.36";
		$dump[] = "";
		$dump[] = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;";
		$dump[] = "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;";
		$dump[] = "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;";
		$dump[] = "/*!50503 SET NAMES utf8mb4 */;";
		$dump[] = "/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;";
		$dump[] = "/*!40103 SET TIME_ZONE='+00:00' */;";
		$dump[] = "/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;";
		$dump[] = "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;";
		$dump[] = "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;";
		$dump[] = "/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;";
		$dump[] = "";
		$dump[] = "--";
		$dump[] = "-- Current Database: `{$this->getDatabase()}`";
		$dump[] = "--";
		$dump[] = "";
		$dump[] = "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `{$this->getDatabase()}` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;";
		$dump[] = "";
		$dump[] = "USE `{$this->getDatabase()}`;";
		$dump[] = "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;";
		$dump[] = "";
		$dump[] = "/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;";
		$dump[] = "/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;";
		$dump[] = "/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;";
		$dump[] = "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;";
		$dump[] = "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;";
		$dump[] = "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
		$dump[] = "/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;";
		$dump[] = "\n";
		$dump[] = "-- Dump completed on " . date("Y-m-d H:i:s");

		file_put_contents($this->_destination . JetBackup::SEP . 'mysql' . JetBackup::SEP . $this->getDatabase() . '.create', implode(PHP_EOL, $dump));
	}

	public function _createUserData():void {

		$this->getLogController()->logMessage("Creating user data configuration file");

		$timestamp = time();
		$user_data = [
			'BACKUP'                        => 1,
			'BWLIMIT'                       => 31457280000,
			'CHILD_WORKLOADS'               => '',
			'CONTACTEMAIL'                  => $this->getEmailAddress(),
			'CONTACTEMAIL2'                 => '',
			'CREATED_IN_VERSION'            => self::CPANEL_VERSION,
			'DEMO'                          => 0,
			'DISK_BLOCK_LIMIT'              => 0,
			'DNS'                           => $this->getDomainOnly(),
			'FEATURELIST'                   => 'default',
			'HASCGI'                        => 0,
			'HASDKIM'                       => 0,
			'HASSPF'                        => 0,
			'LEGACY_BACKUP'                 => 0,
			'LOCALE'                        => 'en',
			'MAILBOX_FORMAT'                => 'maildir',
			'MAXADDON'                      => 0,
			'MAXFTP'                        => 'unlimited',
			'MAXLST'                        => 'unlimited',
			'MAXPARK'                       => 0,
			'MAXPASSENGERAPPS'              => 4,
			'MAXPOP'                        => 'unlimited',
			'MAXSQL'                        => 'unlimited',
			'MAXSUB'                        => 'unlimited',
			'MAX_DEFER_FAIL_PERCENTAGE'     => 100,
			'MAX_EMAILACCT_QUOTA'           => 'unlimited',
			'MAX_TEAM_USERS'                => 7,
			'MTIME'                         => $timestamp,
			'MXCHECK-' . $this->getDomainOnly() => 0,
			'OWNER'                         => 'root',
			'PLAN'                          => 'default',
			'RS'                            => 'jupiter',
			'SSL_DEFAULT_KEY_TYPE'          => 'system',
			'STARTDATE'                     => $timestamp,
			'TRANSFERRED_OR_RESTORED'       => 0,
			'USER'                          => $this->getUsername(),
			'UTF8MAILBOX'                   => 1,
		];

		$output = [];
		foreach ($user_data as $key => $value) $output[] = "$key=$value";
		file_put_contents($this->_destination . JetBackup::SEP . 'cp' . JetBackup::SEP . $this->getUsername(), implode("\n", $output));
	}
}