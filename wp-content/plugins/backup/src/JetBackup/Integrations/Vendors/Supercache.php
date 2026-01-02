<?php

namespace JetBackup\Integrations\Vendors;
use JetBackup\Entities\Util;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\Integrations\Integrations;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Supercache implements Integrations {

	/**
	 * @throws IOException
	 */
	public function execute(): void {
		$this->_clearCache();
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	private function _clearCache(): void{

		$cache_folder = Factory::getWPHelper()->getWordPressHomedir() .
		                Wordpress::WP_CONTENT.
		                JetBackup::SEP . 'cache' .
		                JetBackup::SEP . 'supercache';

		if (!file_exists($cache_folder)) return;
		Util::rm($cache_folder, false);

	}


}