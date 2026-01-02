<?php

namespace JetBackup\Wordpress;

use JetBackup\Alert\Alert;
use JetBackup\Config\Config;
use JetBackup\Crontab\Crontab;
use JetBackup\Destination\Destination;
use JetBackup\Exception\DBException;
use JetBackup\Exception\QueueException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use Plugin_Upgrader;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Installer {
	
	private function __construct() {} // only static method

	public static function install(): void {

		try {

			if (!class_exists('PDO')) {
				throw new \Exception(
					__('The PHP PDO extension is not installed or enabled on your server. JetBackup requires PDO to communicate with databases. Please enable the PDO extension in your PHP configuration and try again.', 'text-domain')
				);
			}

			if (version_compare(PHP_VERSION, JetBackup::MINIMUM_PHP_VERSION, '<')) {
				throw new \Exception(
					sprintf(
						__('PHP version %s or higher is required. Current version: %s', 'text-domain'),
						JetBackup::MINIMUM_PHP_VERSION,
						PHP_VERSION
					)
				);
			}

			if (version_compare(Wordpress::getVersion(), JetBackup::MINIMUM_WP_VERSION, '<')) {
				throw new \Exception(
					sprintf(
						__('WordPress version %s or higher is required. Current version: %s', 'text-domain'),
						JetBackup::MINIMUM_WP_VERSION,
						Wordpress::getVersion()
					)
				);
			}

			$plugins = Wordpress::getPlugins();
			foreach (JetBackup::PLUGIN_CONFLICTS as $plugin) {
				if (!isset($plugins[$plugin]) || !Wordpress::isPluginActive($plugin)) continue;

				throw new \Exception(
					sprintf(
						__('Conflicting plugin detected: %s. Using this plugin alongside JetBackup may cause unexpected behavior. Please disable it before activating JetBackup.', 'text-domain'),
						$plugins[$plugin]['Name']
					)
				);
			}

		} catch (\Exception $e) {
			wp_die(
				sprintf(__('This plugin cannot be activated. %s', 'text-domain'), $e->getMessage()),
				__('Plugin Activation Error', 'text-domain'),
				['back_link' => true]
			);
		}
	}


	public static function uninstall():void {
		Wordpress::deleteOption(Config::WP_DB_CONFIG_PREFIX);
		$cron = new Crontab();
		$cron->removeCrontab();
	}

	public static function deactivate():void {
		Wordpress::deleteOption(Config::WP_DB_CONFIG_PREFIX);
		$cron = new Crontab();
		$cron->removeCrontab();
	}

	public static function update($upgrader):void {

		if (
			!isset($upgrader->new_plugin_data) ||
			!is_array($upgrader->new_plugin_data) ||
			!isset($upgrader->new_plugin_data['Name']) ||
			$upgrader->new_plugin_data['Name'] != JetBackup::PLUGIN_EXT_NAME
		) {
			return;
		}

		
		$config = Factory::getConfig();

		/*
			// PLACEHOLDER - in case we need changes between versions, this is an example

			$current_version = $config->getCurrentVersion();
			if(!$current_version || version_compare($current_version, '3.1', '<')) {

				try {


				} catch (\Exception $e) {
					Alert::add(
						"JetBackup encountered an error during the update process",
						"Error: " . $e->getMessage(),
						Alert::LEVEL_WARNING
					);
					return;
				}

			}
		*/

		$config->setCurrentVersion(JetBackup::VERSION);
		$config->save();
	}
}
