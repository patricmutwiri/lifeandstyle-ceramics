<?php

namespace JetBackup\Export\Vendor;

use JetBackup\Cron\Task\Task;
use JetBackup\Data\ArrayData;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

abstract class Vendor extends ArrayData {

	const TYPE_CPANEL = 1;
	const TYPE_DIRECT_ADMIN = 2;

	const ALL_VENDORS = [
		self::TYPE_CPANEL,
		self::TYPE_DIRECT_ADMIN
	];
	//const TYPE_PLESK = 3;
	
	const USERNAME = 'username';
	const PASSWORD = 'password';
	const DOMAIN = 'domain';
	const DATABASE = 'database';
	const DATABASE_USER = 'database_user';
	const DATABASE_TABLES = 'database_tables';
	const HOMEDIR = 'homedir';
	const EMAIL = 'email';
	const DESTINATION = 'destination';
	public const PANEL_TYPE = 'panel_type';

	const DATABASE_NAME = '%s_wpdb';
	const DATABASE_USER_NAME = '%s_wpuser';
	const DB_HOST = 'localhost';
	
	private Task $_task;

	public function __construct(Task $task) {
		$this->_task = $task;
	}
	
	public function getUsername():string { return $this->get(self::USERNAME); }
	private function _setUsername(string $username):void { $this->set(self::USERNAME, $username); }
	public function getHashedPassword():string { return strtoupper(bin2hex(sha1(sha1($this->getPassword(), true), true))); }
	public function getPassword():string { return $this->get(self::PASSWORD); }
	public function setPassword(string $password):void { $this->set(self::PASSWORD, $password); }

	public function getDomain():string { return $this->get(self::DOMAIN); }

	/**
	 * @param string $domain
	 * @return void
	 *
	 * Sets the full domain retrieved from "Wordpress::getSiteURL()"
	 * prefix (http) is removed during set or not set at all
	 */
	public function setDomain(string $domain):void { $this->set(self::DOMAIN, $domain); }

	/**
	 * Extract clean domain, even if WordPress is nested inside subfolder.
	 * Example: http://mydomain.com/subfolder1/subfolder2 -> mydomain.com
	 * We have to add 'http' and ending '/' to trigger parse_url
	 */
	public function getDomainOnly() : string {
		$domain = 'http://' . trim($this->getDomain(), '/') . '/'; // mydomain.com -> http://mydomain.com/
		return parse_url($domain, PHP_URL_HOST);
	}

	/**
	 * If WordPress is nested inside a subfolder - extract it
	 * Using php core 'parse_url' for this, added'/' suffix just to trigger parse_url (if not ending with "/" will return null)
	 * Example: http://mydomain.com/subfolder1 -> subfolder1
	 */
	public function getDomainSubFolder() : ?string {
		$domain = 'http://' . trim($this->getDomain(), '/') . '/'; // mydomain.com -> http://mydomain.com/
		return trim(parse_url($domain, PHP_URL_PATH), '/');
	}

	public function getDatabase():string { return $this->get(self::DATABASE); }
	private function _setDatabase(string $database):void { $this->set(self::DATABASE, $database); }
	public function getDatabaseUser():string { return $this->get(self::DATABASE_USER); }
	private function _setDatabaseUser(string $user):void { $this->set(self::DATABASE_USER, $user); }
	public function getDatabaseTables():array { return $this->get(self::DATABASE_TABLES, []); }
	public function setDatabaseTables(array $tables):void { $this->set(self::DATABASE_TABLES, $tables); }
	public function getHomedir():string { return $this->get(self::HOMEDIR); }
	public function setHomedir(string $homedir):void { $this->set(self::HOMEDIR, $homedir); }
	public function getEmailAddress():string { return $this->get(self::EMAIL); }
	public function setEmailAddress(string $email):void { $this->set(self::EMAIL, $email); }
	public function getDestination():string { return $this->get(self::DESTINATION); }
	public function setDestination(string $destination):void { $this->set(self::DESTINATION, $destination); }
	public function getTask():Task { return $this->_task; }
	public function getLogController():LogController { return $this->getTask()->getLogController(); }

	abstract public function _build():string;
	
	public function build():string {

		// If domain is IP address, Change it to temporary domain
		if(!$this->getDomain() || filter_var($this->getDomain(), FILTER_VALIDATE_IP)) {
			$this->getLogController()->logMessage("Invalid domain (domain: {$this->getDomain()}), Setting temporary domain 'tempdomain.loc'");
			$this->setDomain('tempdomain.loc');
		}
		
		// Generate username
		$this->_setUsername($this->getTask()->func(function() {
			
			$this->getLogController()->logMessage("Generating username");
			$username = preg_replace('/[^a-z0-9]/', '', strtolower($this->getDomain()));
			if(substr($username, 0, 4) == 'test') $username = 'user' . $username;
			$username = substr($username, 0, 8);

			$this->getLogController()->logMessage("Username: $username");

			return $username;
		}, [], 'generate_username'));

		// Set the new database name
		$this->_setDatabase(sprintf(self::DATABASE_NAME, $this->getUsername()));
		// Set the new database user name
		$this->_setDatabaseUser(sprintf(self::DATABASE_USER_NAME, $this->getUsername()));

		// If Email address not exists or invalid, set default Email address
		if(!$this->getEmailAddress() || !filter_var($this->getEmailAddress(), FILTER_VALIDATE_EMAIL)) {
			$email = $this->getUsername() . '@' . $this->getDomainOnly();
			$this->getLogController()->logMessage("Invalid Email address (Email: {$this->getEmailAddress()}), Setting temporary email address '$email'");
			$this->setEmailAddress($email);
		}
		
		// Change database and database user names
		$this->getTask()->func(function() {

			$this->getLogController()->logMessage("Changing database and database user names in wp-config.php file");

			$config = $this->getHomedir() . JetBackup::SEP . 'wp-config.php';
			$config_new = $config . '.new';

			$fd = fopen($config_new, 'w');

			$this->getTask()->fileRead($config, function($line) use ($fd) {
				switch(true) {
					case preg_match("/^define\(\s*'DB_NAME'\s*,\s*'.*?'\s*\);/", $line):
						$line = "define('DB_NAME', '" . $this->getDatabase() . "');" . PHP_EOL;
						break;
					case preg_match("/^define\(\s*'DB_USER'\s*,\s*'.*?'\s*\);/", $line):
						$line = "define('DB_USER', '" . $this->getDatabaseUser() . "');" . PHP_EOL;
						break;
					case preg_match("/^define\(\s*'DB_PASSWORD'\s*,\s*'.*?'\s*\);/", $line);
						$line = "define('DB_PASSWORD', '" . $this->getPassword() . "');" . PHP_EOL;
						break;
					case preg_match("/^define\(\s*'WP_CONTENT_DIR'\s*,\s*'.*?'\s*\);/", $line): return;
					case preg_match("/^define\(\s*'DB_HOST'\s*,\s*'.*?'\s*\);/", $line):
						$line = "define('DB_HOST', '" . self::DB_HOST . "');" . PHP_EOL;
						break;
				}
				fwrite($fd, $line);
			});

			fclose($fd);
			unlink($config);
			rename($config_new, $config);

		}, [], 'change_db_name');
		
		return $this->_build();
	}
}