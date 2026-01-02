<?php
/**
 * Plugin Name:       JetBackup
 * Plugin URI:        https://www.jetbackup.com/jetbackup-for-wordpress
 * Description:       JetBackup is the most complete WordPress site backup and restore plugin. We offer the easiest way to backup, restore or migrate your site. You can backup your files, database or both.
 * Version:           3.1.16.1
 * Author:            JetBackup
 * Author URI:        https://www.jetbackup.com/jetbackup-for-wordpress
 * License:           GPLv2 or later
 *
 */

use JetBackup\Entities\Util;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;

if (!defined('WPINC')) die('Direct access is not allowed');

if(!defined('__JETBACKUP__')) define('__JETBACKUP__', true);
if(!defined('JB_ROOT')) define('JB_ROOT', dirname(__FILE__));
if(!defined('WP_ROOT')) define('WP_ROOT', rtrim(ABSPATH, DIRECTORY_SEPARATOR));

require_once(JB_ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'JetBackup' . DIRECTORY_SEPARATOR . 'autoload.php');

// Installation procedures
register_activation_hook(   __FILE__,       ['\JetBackup\Wordpress\Installer', 'install']);
register_uninstall_hook(    __FILE__,       ['\JetBackup\Wordpress\Installer', 'uninstall']);
register_deactivation_hook( __FILE__,       ['\JetBackup\Wordpress\Installer', 'deactivate']);

add_action('init',                          ['\JetBackup\Wordpress\Init', 'actionInit']);

// Anything after init will not check for user permissions !
add_action('init',                          ['\JetBackup\Wordpress\Init', 'actionCLI']);
add_action('upgrader_process_complete',     ['\JetBackup\Wordpress\Installer', 'update']);
add_filter('admin_body_class',              ['\JetBackup\Wordpress\Init', 'filterAdminBodyClass']);
add_filter('admin_bar_menu',              ['\JetBackup\Wordpress\UI', 'addTopMenuBarIntegration'], 100);
add_filter('plugin_action_links_backup/backup.php',              ['\JetBackup\Wordpress\UI', 'addActionLinks']);
add_filter('plugin_row_meta',              ['\JetBackup\Wordpress\UI', 'addRowMeta'], 10, 2);

//WordPress will register the callback function in the database and will try to call it during uninstall
//At this point class doesn't exist anymore, so we have to use wrapper function
add_filter('site_transient_update_plugins', function($transient) {
	if (!class_exists('\JetBackup\Wordpress\Update')) return $transient;
	return \JetBackup\Wordpress\Update::check($transient);
});