<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use Exception;
use JetBackup\Ajax\aAjax;
use JetBackup\Config\System;
use JetBackup\Entities\Util;
use JetBackup\Exception\LicenseException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\JetBackupLinux\JetBackupLinux;
use JetBackup\License\License;
use JetBackup\MFA\GoogleAuthenticator;
use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\UI;
use JetBackup\Wordpress\Wordpress;
use JetBackup\Showcase\Showcase;

class PanelPreload extends aAjax {

	/**
	 * @return void
	 * @throws Exception
	 */
	public function execute(): void {

		$user = Wordpress::getCurrentUser();

		$user_name = $user->data->user_nicename ?? $user->data->display_name ?? $user->data->user_login ?? 'User';
		$user_role = $user->roles[0] ?? 'Role';
		$user_profile = Wordpress::getAdminURL() . 'profile.php';


		$licenseStatus = License::STATUS_ACTIVE;
		$licenseMessage = '';

		try {
			License::checkLocalKey();
		} catch(LicenseException $e) {
			$licenseStatus = $e->getStatus();
			$licenseMessage = $e->getMessage();
		}

		$this->setResponseData([
			'mfa'                   => [
										'isEnabled' => Factory::getSettingsSecurity()->isMFAEnabled(),
										'isValid' => UI::validateMFA(),
										'isCompleted' => GoogleAuthenticator::isSetupCompleted(),
			],
			'info'                  => [
				'development'           => JetBackup::DEVELOPMENT,
				'errors'                => [],
			],
			'account'           => [
				'name'      => $user_name,
				'role'      => $user_role,
				'profile'   => $user_profile,
			],
			'language_ns'           => self::_loadNS(JetBackup::PUBLIC_PATH . '/lang/en_US'),
			'language_cdn'          => Factory::getSettingsGeneral()->isCommunityLanguages(),
			'license'               => [
				'status'                => $licenseStatus,
				'message'               => $licenseMessage,
			],
			'integration'               => [
				'installed'         => JetBackupLinux::isInstalled(),
				'enabled'           => Factory::getSettingsGeneral()->isJBIntegrationEnabled(),
			],
			'configuration'             => [
				'UI'                    => [
					'site_url' => Wordpress::getSiteURL(),
					'admin_url' => Wordpress::getAdminURL(),
					'plugin_path' => UI::getPluginPath(),
					'timezones' => Util::generateTimeZoneList(),
				],
				'env'               => [
					'is_windows' => System::isWindowsOS(),
					'is_multisite' => Helper::isMultisite(),
					'multisite_main_site_id' => Helper::getMainSiteId(),
				],
				'showcase' => Showcase::getAll(),
			],
		]);
	}

	/**
	 * @param $directory
	 *
	 * @return array
	 */
	private static function _loadNS($directory): array {
		if(!is_dir($directory)) return [];

		$output = [];
		$dir = dir($directory);
		while (false !== ($entry = $dir->read())) {
			if(!preg_match("/^(.*)\.json$/", $entry, $matches)) continue;
			$output[] = $matches[1];
		}
		$dir->close();

		return $output;
	}
}