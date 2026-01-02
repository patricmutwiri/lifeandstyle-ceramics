<?php

namespace JetBackup\Wordpress;

use JetBackup\Data\ArrayData;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Blog extends ArrayData {

	const ID = 'id';
	const DOMAIN = 'domain';
	const DATABASE_TABLES = 'database_tables';

	const MAIN_BLOG_ID = 1;
	
	public function __construct($data=[]) {
		$this->setData($data);
	}

	public function setId(int $id):void { $this->set(self::ID, $id); }
	public function getId():int { return $this->get(self::ID, 0); }

	public function setDomain(string $domain):void { $this->set(self::DOMAIN, $domain); }
	public function getDomain():string { return $this->get(self::DOMAIN); }

	public function setDatabaseTables(array $tables):void { $this->set(self::DATABASE_TABLES, $tables); }
	public function getDatabaseTables():array { return $this->get(self::DATABASE_TABLES, []); }
	public function addDatabaseTable(string $table):void { 
		$tables = $this->getDatabaseTables();
		$tables[] = $table;
		$this->setDatabaseTables($tables);
	}

	public function getPaths():array { 
		if(!$this->isMain()) return [];
		return [
			"wp-content/blogs.dir/{$this->getId()}/files", // Legacy
			"wp-content/uploads/sites/{$this->getId()}",
			"wp-content/uploads/{$this->getId()}",
			"wp-content/{$this->getId()}",
		]; 
	}

	public function isMain():bool { return $this->getId() == self::MAIN_BLOG_ID; }
}
