<?php

namespace JetBackup;

use JetBackup\Config\Config;
use JetBackup\Config\Locations;
use JetBackup\Settings\Automation;
use JetBackup\Settings\General;
use JetBackup\Settings\Integrations;
use JetBackup\Settings\Logging;
use JetBackup\Settings\Maintenance;
use JetBackup\Settings\Notifications;
use JetBackup\Settings\Performance;
use JetBackup\Settings\Restore;
use JetBackup\Settings\Security;
use JetBackup\Settings\Updates;
use JetBackup\Wordpress\Helper;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Factory {
	
	private function __construct() {}

	/**
	 * @return Config
	 */
	public static function getConfig():Config {
		static $i;
		if(!$i) $i = new Config();
		return $i;
	}

	public static function getSettingsAutomation():Automation {
		static $i;
		if(!$i) $i = new Automation();
		return $i;
	}

	public static function getSettingsGeneral($reload=false):General {
		static $i;
		if(!$i || $reload) $i = new General();
		return $i;
	}

	public static function getSettingsSecurity():Security {
		static $i;
		if(!$i) $i = new Security();
		return $i;
	}

	public static function getSettingsPerformance():Performance {
		static $i;
		if(!$i) $i = new Performance();
		return $i;
	}

	public static function getSettingsLogging():Logging {
		static $i;
		if(!$i) $i = new Logging();
		return $i;
	}

	public static function getSettingsNotifications():Notifications {
		static $i;
		if(!$i) $i = new Notifications();
		return $i;
	}

	public static function getSettingsMaintenance():Maintenance {
		static $i;
		if(!$i) $i = new Maintenance();
		return $i;
	}

	public static function getSettingsUpdates():Updates {
		static $i;
		if(!$i) $i = new Updates();
		return $i;
	}

	public static function getSettingsRestore():Restore {
		static $i;
		if(!$i) $i = new Restore();
		return $i;
	}

	public static function getSettingsIntegrations():Integrations {
		static $i;
		if(!$i) $i = new Integrations();
		return $i;
	}

	/**
	 * @return Locations
	 */
	public static function getLocations():Locations {
		static $i;
		if(!$i) $i = new Locations();
		return $i;
	}

	/**
	 * @return Helper
	 */
	public static function getWPHelper():Helper {
		static $i;
		if(!$i) $i = new Helper();
		return $i;
	}
}
