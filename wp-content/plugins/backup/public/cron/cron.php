<?php
if (function_exists('opcache_get_status')) ini_set('opcache.enable', 0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
header('Pragma: no-cache');
header('Expires: 0');

use JetBackup\Cron\Cron;
use JetBackup\Factory;
use JetBackup\Wordpress\Wordpress;

$isWeb =  isset($_SERVER['HTTP_TE']) || isset($_SERVER['HTTP_COOKIE']) || isset($_SERVER['HTTP_ACCEPT']) ?? null;
$location = ($_SERVER['SCRIPT_FILENAME'] ?? __FILE__);
// /home/user/public_html/wp-content/plugins/backup/public/cron/cron.php

define ('WP_ROOT', dirname($location, 6));

if (!file_exists(WP_ROOT . DIRECTORY_SEPARATOR . 'wp-load.php')) {
	die('Error: Cannot locate wp-load.php. Ensure WP_ROOT is correct.');
}

// Get into WordPress ecosystem
require_once(WP_ROOT . DIRECTORY_SEPARATOR . 'wp-load.php');

$_active_plugins = is_multisite() ? array_keys(get_site_option('active_sitewide_plugins')) : Wordpress::getOption('active_plugins');
$_plugin_name = 'backup/backup.php'; // Cannot use DIRECTORY_SEPARATOR, will cause false positives with IIS
if (!in_array($_plugin_name, $_active_plugins)) die('JetBackup Plugin is inactive');

if ($isWeb) {
	$key = Factory::getConfig()->getCronToken();
	$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	if (!$key || !$token || $key != $token) die(1);
}

try {
	Cron::main();
} catch(Exception $e) {
	die($e->getMessage() . PHP_EOL);
}
