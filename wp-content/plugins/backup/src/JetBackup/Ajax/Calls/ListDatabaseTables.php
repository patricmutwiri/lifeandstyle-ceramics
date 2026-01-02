<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Exception\DBException;
use JetBackup\Wordpress\Wordpress;

/*
 * List local db tables
 */
class ListDatabaseTables extends aAjax {

	/**
	 * @return void
	 * @throws DBException
	 */
	public function execute(): void {

		$tables = Wordpress::getDB()->listTables();
		$output = $this->isCLI() ? [] : [ 'tables' => $tables, 'total' => count($tables) ];
		$this->setResponseData($output);

	}
}