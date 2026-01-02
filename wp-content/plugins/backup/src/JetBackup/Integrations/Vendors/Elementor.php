<?php

namespace JetBackup\Integrations\Vendors;
use JetBackup\Integrations\Integrations;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Elementor implements Integrations {

	/**
	 * @return void
	 */
	public function execute(): void {
		if (!class_exists('\Elementor\Plugin')) return;
		$this->_clearCache();
	}

	/**
	 * @return void
	 */
	private function _clearCache(): void {
		add_action('elementor/init', function() {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		});
		do_action('elementor/init');
	}
}