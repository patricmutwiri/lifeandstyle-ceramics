<?php

namespace JetBackup\Export;

use JetBackup\Cron\Task\Task;
use JetBackup\Exception\IOException;
use JetBackup\Export\Vendor\CPanel;
use JetBackup\Export\Vendor\DirectAdmin;
use JetBackup\Export\Vendor\Vendor;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Export {

	private Task $_task;
	
	public function __construct(Task $task) {
		$this->_task = $task;
	}
	
	public function build(int $type, string $homedir, array $database_tables, string $destination):string {

		if(!is_dir($destination)) throw new IOException("The provided destination not exists");

		switch($type) {
			default: throw new IOException("Invalid type provided");
			case Vendor::TYPE_CPANEL: $obj = new CPanel($this->_task); break;
			case Vendor::TYPE_DIRECT_ADMIN: $obj = new DirectAdmin($this->_task); break;
		}
		
		$obj->setPassword(DB_PASSWORD);
		$obj->setDomain(Wordpress::getSiteDomain());
		$obj->setEmailAddress(Helper::getUserEmail());
		$obj->setDestination($destination);
		$obj->setHomedir($homedir);
		$obj->setDatabaseTables($database_tables);
		
		return $obj->build();
	}
}