<?php

use JetBackup\JetBackup;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

spl_autoload_register(function(string $className) {

	$parts = explode('\\', $className);
	if(!isset($parts[0]) || $parts[0] != 'Mysqldump') return false;

	$path = JetBackup::TRDPARTY_PATH . JetBackup::SEP . implode(JetBackup::SEP, $parts) . '.php';
	if(!file_exists($path)) return false;

	require_once($path);
	return true;
});
