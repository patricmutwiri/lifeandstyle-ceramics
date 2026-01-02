<?php

namespace JetBackup\Wordpress;

use JetBackup\Exception\HttpRequestException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Settings\Updates;
use JetBackup\Web\JetHttp;
use stdClass;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Update {
	const REPO_URL = 'https://repo.jetlicense.com';
	const REPO_EXTENSIONS_URL = self::REPO_URL . '/extensions/%s';
	const REPO_EXTENSIONS_DATA_URL = self::REPO_EXTENSIONS_URL . '/repodata_v2';
	const TRANSIENT = 'jetbackup_plugin_updates';
	
	const WP_ASSETS_URL = 'https://ps.w.org/' . JetBackup::PLUGIN_NAME . '/assets';

	private function __construct() {}

	/**
	 * @return array|null
	 */
	private static function _fetchRepoData():?array {

		$tier = Factory::getSettingsUpdates()->getUpdateTier();

		try {
			$response = JetHttp::request()
				->setReturnTransfer()
				->setSSLVerify(0, 0)
				->exec(sprintf(self::REPO_EXTENSIONS_DATA_URL, $tier));

			if($response->getHeaders()->getCode() != 200) return null;
			$data = json_decode($response->getBody());
			return (array) ($data !== false ? $data : []);
		} catch(HttpRequestException $e) {
			return null;
		}
	}

	/**
	 * NOTE: WordPress may pass a boolean (false) if the update_plugins transient is not set or has expired.
	 *
	 * @param object|null $transient
	 *
	 * @return object
	 */
	public static function check($transient):object {

		if (!$transient || !is_object($transient)) {
			$transient = new stdClass();
			$transient->response = [];
			$transient->no_update = [];
		}

		$tier = Factory::getSettingsUpdates()->getUpdateTier();
		if($tier == Updates::TIER_RELEASE) return $transient;
		
		$repo_data =  Wordpress::getTransient(self::TRANSIENT);

		if(!$repo_data || !isset($repo_data->tier) || $repo_data->tier != $tier || !isset($repo_data->last_check) || $repo_data->last_check < (time() - 86400)) {
			if(!($repo_data = self::_fetchRepoData())) return $transient;

			if(is_array($repo_data)) {
				
				$newest_package = new stdClass();
				foreach($repo_data as $package) {
					if(!isset($newest_package->version) || (isset($package->version) && version_compare($newest_package->version, $package->version, '<'))) $newest_package = $package;
				}
				
				$repo_data = $newest_package;
			}

			// Check if we found any version in our repo
			if(!isset($repo_data->version)) return $transient;

			$repo_data->tier = $tier;
			$repo_data->last_check = time();
			Wordpress::setTransient(self::TRANSIENT, $repo_data);
		}

		$current = new stdClass();
		if(isset($transient->response[JetBackup::PLUGIN_SLUG])) $current = $transient->response[JetBackup::PLUGIN_SLUG];
		if(isset($transient->no_update[JetBackup::PLUGIN_SLUG])) $current = $transient->no_update[JetBackup::PLUGIN_SLUG];
		
		$object = (object) [
			'id'            => 'w.org/plugins/' . JetBackup::PLUGIN_NAME,
			'slug'          => JetBackup::PLUGIN_NAME,
			'plugin'        => JetBackup::PLUGIN_SLUG,
			'new_version'   => $repo_data->version,
			'url'           => $current->url ?? 'https://wordpress.org/plugins/' . JetBackup::PLUGIN_NAME . '/',
			'icons'         => $current->icons ?? [
				'1x'            => self::WP_ASSETS_URL . '/icon-128x128.png?rev=3113473',
			],
			'banners'       => $current->banners ?? [
				'2x'            => self::WP_ASSETS_URL . '/banner-1544x500.png?rev=2858586',
				'1x'            => self::WP_ASSETS_URL . '/banner-772x250.png?rev=2858590',
			],
			'banners_rtl'   => $current->banners_rtl ?? [],
			'package'       => sprintf(self::REPO_EXTENSIONS_URL, $tier) . '/' . $repo_data->package,
			'requires'      => $repo_data->min_version,
			'tested'        => $repo_data->tested_on ?? JetBackup::TESTED_ON_WP_VERSION,
			'requires_php'  => $repo_data->requires_php ?? JetBackup::MINIMUM_PHP_VERSION
		];
		
		if(version_compare($repo_data->version, JetBackup::VERSION, '>')) {
			$transient->response[JetBackup::PLUGIN_SLUG] = $object;
			if(isset($transient->no_update[JetBackup::PLUGIN_SLUG])) unset($transient->no_update[JetBackup::PLUGIN_SLUG]);
		} else {
			$transient->no_update[JetBackup::PLUGIN_SLUG] = $object;
			if(isset($transient->response[JetBackup::PLUGIN_SLUG])) unset($transient->response[JetBackup::PLUGIN_SLUG]);
		}

		return $transient;
	}
}