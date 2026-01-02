<?php

use JetBackup\JetBackup;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

spl_autoload_register(function ($className) {

	$parts = explode('\\', $className);
	if (!isset($parts[0]) || !in_array($parts[0], ['JetBackup','OLD'])) return false;

	$path = JB_ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
	if (!file_exists($path)) return false;

	require_once($path);
	return true;
});

require_once(JetBackup::TRDPARTY_PATH . JetBackup::SEP . 'autoload.php');
require_once(JetBackup::SRC_PATH . JetBackup::SEP . 'functions.php');