<?php
/*
*
* JetBackup @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/

use JetBackup\JetBackup;

spl_autoload_register(function($className) {

	$parts = explode('\\', $className);
	if(!isset($parts[0]) || $parts[0] != 'JetBackup' || $parts[1] != 'SocketAPI') return false;
	unset($parts[0]);

	$path = JetBackup::TRDPARTY_PATH . JetBackup::SEP . implode(JetBackup::SEP, $parts) . '.php';
	if(!file_exists($path)) return false;

	require_once($path);
	return true;
});
