<?php

namespace JetBackup\Integrations\Vendors;
use JetBackup\Entities\Util;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\Integrations\Integrations;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Woocommerce implements Integrations {

	/**
	 * @throws IOException
	 */
	public function execute(): void {
		$this->_clearCache();
		$this->_actions();
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	private function _actions(): void{

		if (function_exists('wc_update_product_lookup_tables')) {
			wc_update_product_lookup_tables();
		}

		if (function_exists('wc_update_order_stats')) {
			wc_update_order_stats();
		}

		if (function_exists('WC_Install') && method_exists('WC_Install', 'needs_db_update') && WC_Install::needs_db_update()) {
			WC_Install::update();
		}

	}

	private function _clearCache(): void{

		$content_directory = Factory::getWPHelper()->getWordPressHomedir() . Wordpress::WP_CONTENT;

		$folders = [
			$content_directory . JetBackup::SEP . 'cache' . JetBackup::SEP . 'wc-cache',       // WooCommerce Cache
			$content_directory . JetBackup::SEP . 'uploads' . JetBackup::SEP . 'wc-logs',      // WooCommerce Logs
		];

		foreach ($folders as $folder) {
			if (!file_exists($folder)) continue;
			Util::rm($folder, false);
		}

	}


}