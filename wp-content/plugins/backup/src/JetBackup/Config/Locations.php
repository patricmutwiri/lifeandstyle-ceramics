<?php

namespace JetBackup\Config;

use JetBackup\Entities\Util;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Locations {

	private ?string $_data_folder=null;

	// /home/user/jetbackup-XXXXXXXX
	public function getDataDir(): string {

		if (!$this->_data_folder) {

			$folder = Factory::getWPHelper()->getWordPressHomedir() . Factory::getWPHelper()->getUploadDir();
			if ($folder) $folder .= JetBackup::SEP . Factory::getConfig()->getDataDirectory();
			$this->_data_folder = $folder;

			$alt_folder = trim(Factory::getConfig()->getAlternateDataFolder());

			if ($alt_folder) {

				if ( basename($alt_folder) !==  basename($this->_data_folder) ) {

					if ( ! file_exists( $alt_folder ) )
						mkdir( $alt_folder, 0700 );

					$target = $alt_folder . JetBackup::SEP . Factory::getConfig()->getDataDirectory();
					if ( file_exists( $this->_data_folder ) && ! file_exists( $target ) ) {
						rename( $this->_data_folder, $target );
					}

				}
				$this->_data_folder = $alt_folder;
			}
		}

		Util::secureFolder($this->_data_folder);
		return $this->_data_folder;
	}


	public function getDatabaseDir():string {
		// /home/user/public_html/wp-content/uploads/jetbackup/db
		return $this->getDataDir() . JetBackup::SEP . Factory::getConfig()->getDatabaseDirectory();
	}

	public function getLogsDir():string {
		// /home/user/public_html/wp-content/uploads/jetbackup/logs
		return $this->getDataDir() . JetBackup::SEP . Factory::getConfig()->getLogsDirectory();
	}

	public function getBackupsDir():string {
		// /home/user/public_html/wp-content/uploads/jetbackup/backups
		return $this->getDataDir() . JetBackup::SEP . Factory::getConfig()->getBackupsDirectory();
	}

	public function getTempDir():string {
		// /home/user/public_html/wp-content/uploads/jetbackup/temp
		return $this->getDataDir() . JetBackup::SEP . Factory::getConfig()->getTempDirectory();
	}

	public function getDownloadsDir():string {
		// /home/user/public_html/wp-content/uploads/jetbackup/downloads
		return $this->getDataDir() . JetBackup::SEP . Factory::getConfig()->getDownloadsDirectory();
	}

	/**
	 * Should return "/public_html/wp-content/plugins/backup"
	 * @return void
	 */
	public function getPluginPublicDir() : string {
		return JetBackup::SEP . Factory::getWPHelper()->getWordPressHomedir(true) .
		       JetBackup::SEP . Wordpress::WP_CONTENT .
		       JetBackup::SEP . Wordpress::WP_PLUGINS .
		       JetBackup::SEP . JetBackup::PLUGIN_NAME;
	}

	/**
	 * Returns path if datadir inside public folder
	 * Example -
	 * Original fullpath data dir: /home/wpjetbackup/public_html/wp-content/uploads/jetbackup-XXXXXXXXX
	 * Should return: wp-content/uploads/jetbackup-XXXXXXXXX
	 * null if not
	 */
	public function getPublicDataDir() : ?string {

		if (str_starts_with($this->getDataDir(), Factory::getWPHelper()->getWordPressHomedir())) {
			return substr($this->getDataDir(), strlen(Factory::getWPHelper()->getWordPressHomedir()));

		}

		return null;

	}

	/**
	 * @return string|null
	 * Returns data dir relative to user's homedir
	 *  - Homedir: /home/user
	 *  - Datadir: /home/wpjetbackup/mydatadir/jetbackup-xxxxxxx
	 *  - Output: /mydatadir/jetbackup-xxxxxxx
	 */
	public function getRelativeDataDir() : string {

		$dataDir = trim($this->getDataDir(), JetBackup::SEP);
		$getUserHomedir = trim(Factory::getWPHelper()->getUserHomedir(), JetBackup::SEP);
		$relative_path = $getUserHomedir;
		if ($dataDir != $getUserHomedir && str_starts_with($dataDir, $getUserHomedir)) {
			$relative_path = trim(substr($dataDir, strlen($getUserHomedir)), JetBackup::SEP);
		}

		return JetBackup::SEP . $relative_path;

	}


}