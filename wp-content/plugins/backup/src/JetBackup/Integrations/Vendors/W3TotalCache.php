<?php

namespace JetBackup\Integrations\Vendors;
use JetBackup\Entities\Util;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\Integrations\Integrations;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class W3TotalCache implements Integrations {

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

		$content_directory = Factory::getWPHelper()->getWordPressHomedir() . Wordpress::WP_CONTENT;

		$folders = [
				$content_directory . JetBackup::SEP . 'cache' . JetBackup::SEP . 'page_enhanced',  // W3 Total Cache Page Cache
				$content_directory . JetBackup::SEP . 'cache' . JetBackup::SEP . 'minify',         // W3 Total Cache Minify Cache
				$content_directory . JetBackup::SEP . 'cache' . JetBackup::SEP . 'object',         // W3 Total Cache Object Cache
				$content_directory . JetBackup::SEP . 'cache' . JetBackup::SEP . 'db',              // W3 Total Cache Database Cache
		];

		foreach ($folders as $folder) {
			if (!file_exists($folder)) continue;
			Util::rm($folder, false);
		}

	}


}