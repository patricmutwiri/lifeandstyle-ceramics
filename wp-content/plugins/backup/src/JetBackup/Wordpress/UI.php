<?php

namespace JetBackup\Wordpress;

use JetBackup\Alert\Alert;
use JetBackup\Config\System;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\MFA\GoogleAuthenticator;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class UI {
	
	const PUBLIC_PATH = 'public';
	const PUBLIC_LIBRARIES = self::PUBLIC_PATH . '/libraries';
	const PUBLIC_CSS = self::PUBLIC_PATH . '/css';
	const PUBLIC_IMAGES = self::PUBLIC_PATH . '/images';
	const PUBLIC_JS = self::PUBLIC_PATH . '/js';

	const PAGES = [
		[ 'menu_title' => 'Backups',        'page_title' => 'Restore Entire Site Backups',  'slug' => 'backups' ],
		[ 'menu_title' => 'Backup Jobs',    'page_title' => 'Backup Jobs',                  'slug' => 'jobs' ],
		[ 'menu_title' => 'Destinations',   'page_title' => 'Destinations',                 'slug' => 'destinations' ],
		[ 'menu_title' => 'Schedules',      'page_title' => 'Schedules',                    'slug' => 'schedules' ],
		[ 'menu_title' => 'Queue',          'page_title' => 'Queue',                        'slug' => 'queue' ],
		[ 'menu_title' => 'Downloads',      'page_title' => 'Downloads',                    'slug' => 'downloads' ],
		[ 'menu_title' => 'Alerts',         'page_title' => 'Alerts',                       'slug' => 'alerts' ],
		[ 'menu_title' => 'Settings',       'page_title' => 'Settings',                     'slug' => 'settings/general' ],
		[ 'menu_title' => 'System Info',    'page_title' => 'System Info',                  'slug' => 'system' ],
	];

	/**
	 * @throws IOException
	 * @throws \JetBackup\Exception\IOException
	 * @throws InvalidArgumentException
	 */
	public static function main() {

		$site_url = self::getPluginPath() . '/';
		wp_enqueue_script('jetbackup-lib-main-js', $site_url . self::PUBLIC_LIBRARIES . '/main.js');
		$eddie_icon = $site_url . '/' . self::PUBLIC_IMAGES . '/eddie-menu.svg';

		$hook = add_menu_page('JetBackup', __('JetBackup','jetbackup'), 'manage_options', 'jetbackup', [ '\JetBackup\Wordpress\UI', 'loadUI'], $eddie_icon, 74);
		add_action('admin_print_scripts-' . $hook, [ '\JetBackup\Wordpress\UI', 'enqueueCSS' ]);
		add_action('admin_print_scripts-' . $hook, [ '\JetBackup\Wordpress\UI', 'removeAdminNotices']);

		foreach (self::PAGES as $position => $page) {

			if($page['menu_title'] == 'System Info' && System::getTotalAlerts() > 1) {
				$page['menu_title'] = __('System Info','jetbackup').'<span class="update-plugins count-2"><span class="plugin-count">'.System::getTotalAlerts().'</span></span>';
			}

			if($page['menu_title'] == 'Alerts' && Alert::getTotalCriticalAlerts() > 1) {
				$page['menu_title'] =  __('Alerts','jetbackup'). '<span class="update-plugins count-2"><span class="plugin-count">'.Alert::getTotalCriticalAlerts().'</span></span>';
			}

			$hook = add_submenu_page('jetbackup', $page['page_title'], __($page['menu_title'],'jetbackup'), 'manage_options', 'jetbackup#!/' . $page['slug'], function() {}, $position+1);
			add_action('admin_print_scripts-' . $hook, [ '\JetBackup\Wordpress\UI', 'removeAdminNotices']);
		}
	}

	public static function enqueueCSS() {
		$site_url = self::getPluginPath() . '/';
		wp_enqueue_style('jetbackup-lib-main-css', $site_url . self::PUBLIC_LIBRARIES . '/main.css');
	}


	/**
	 * @param $links
	 *
	 * @return array
	 */
	public static function addActionLinks($links): array {
		$custom_links = [
			'<a href="' . admin_url('admin.php?page=jetbackup') . '">' . __('Dashboard', 'jetbackup') . '</a>',
			'<a href="' . admin_url('admin.php?page=jetbackup#!/settings/general') . '">' . __('Settings', 'jetbackup') . '</a>',
		];
		return array_merge($custom_links, $links);
	}

	/**
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public static function addRowMeta($links, $file) : array {
		if ($file != 'backup/backup.php') return $links;
		$custom_links = [
			'<a href="https://docs.jetbackup.com/wordpress/jbwp" target="_blank">' . __('Documentation', 'jetbackup') . '</a>',
			'<a href="https://www.jetbackup.com/contact" target="_blank">' . __('Support', 'jetbackup') . '</a>',
			'<a href="https://wordpress.org/support/plugin/backup/reviews/?filter=5#new-post" target="_blank">' . __('Rate ★★★★★', 'jetbackup') . '</a>',
		];
		return array_merge($links, $custom_links);
	}

	/**
	 * @param $admin_bar
	 *
	 * @return void
	 */
	public static function addTopMenuBarIntegration($admin_bar) : void {
		if (!Factory::getSettingsGeneral()->isAdminTopMenuIntegrationEnabled()) return;
		if (!current_user_can('manage_options')) return;

		$admin_bar->add_menu(array(
			'id'    => 'jetbackup',
			'title' => '<img src="' . esc_url(UI::getPluginPath() . '/public/images/logo-loader.png' ) . '" alt="JetBackup" style="height: 15px; vertical-align: middle;" />',
			'href'  => admin_url('admin.php?page=jetbackup'),
			'meta'  => array(
				'title' => __('JetBackup'),
			),
		));
	}

	public static function convertPhpToMomentFormat($phpFormat) : string {
		$replacements = [
			'F' => 'MMMM',  // Full month name
			'j' => 'D',     // Day without leading zero
			'S' => 'o',   // Day with ordinal suffix (6th, 7th)
			'd' => 'DD',    // Day with leading zero
			'm' => 'MM',    // Month with leading zero
			'n' => 'M',     // Month without leading zero
			'Y' => 'YYYY',  // Full year
			'y' => 'YY',    // 2-digit year
			'H' => 'HH',    // 24-hour with leading zero
			'G' => 'H', // 24-hour format without leading zero
			'h' => 'hh',    // 12-hour with leading zero
			'g' => 'h',     // 12-hour without leading zero
			'i' => 'mm',    // Minutes
			's' => 'ss',    // Seconds
			'A' => 'A',     // Uppercase AM/PM
			'a' => 'a',     // Lowercase am/pm
			'l' => 'dddd',  // Full day of the week
			'D' => 'ddd',   // Abbreviated day of the week
		];

		$format = '';

		for($i = 0; $i < strlen($phpFormat); $i++) {
			if($phpFormat[$i] == '\\') {
				$i++;
				if(!isset($phpFormat[$i])) break;
				$format .= '[' . $phpFormat[$i] . ']';
			} else {
				$format .= $replacements[$phpFormat[$i]] ?? $phpFormat[$i];
			}
		}
		
		return $format;
	}


	public static function loadUI() {

		$dateFormat = self::convertPhpToMomentFormat(Wordpress::getDateFormat());
		$timeFormat = self::convertPhpToMomentFormat(Wordpress::getTimeFormat());

		$lang = Wordpress::getLocale();
		$version = JetBackup::VERSION;
		$public_path = self::getPluginPath() . '/' . self::PUBLIC_PATH;
		$nonce = Wordpress::createNonce();

		echo <<<HTML
<div ng-include="'$public_path/views/main.htm?v=$version'" ng-controller="JetBackup" id="JetBackup"></div>
<script type="text/javascript">
	new JetBackup({
		nonce: '$nonce',
		language: '$lang',
		dateFormat: '$dateFormat',
		timeFormat: '$timeFormat',
		plugin_path: '$public_path'
	});
</script>
HTML;
	}
	
	public static function removeAdminNotices() {
		remove_all_actions('admin_notices');
		remove_all_actions('all_admin_notices');
	}

	public static function getPluginPath(): string {
		return Wordpress::getSiteURL() . '/' . Wordpress::WP_CONTENT . '/' . Wordpress::WP_PLUGINS . '/' . JetBackup::PLUGIN_NAME;
	}

	public static function validateMFA(): bool {
		return isset($_COOKIE[GoogleAuthenticator::MFA_COOKIE_KEY]) &&
		       hash_equals(Wordpress::getUnslash($_COOKIE[GoogleAuthenticator::MFA_COOKIE_KEY]), GoogleAuthenticator::getCookieHash());
	}

	public static function heartbeat() {
		$ttl = Factory::getSettingsAutomation()->getHeartbeatTTL();
		$nonce = Wordpress::createNonce();

		echo <<<HTML
<script type="text/javascript">
var nonce = '$nonce';
setInterval(function () {
	const request = new XMLHttpRequest();
	request.open('POST', ajaxurl, true);
	request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	request.onreadystatechange = () => {
  		if (request.readyState !== XMLHttpRequest.DONE || request.status !== 200) return;
		const data = JSON.parse(request.responseText);
		if(nonce !== data.system.nonce) nonce = data.system.nonce;
	};
	request.send('action=jetbackup_heartbeat&nonce=' + nonce);
}, $ttl)
</script>
HTML;
	}
}
