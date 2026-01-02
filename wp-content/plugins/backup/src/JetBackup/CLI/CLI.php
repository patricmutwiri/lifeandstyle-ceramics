<?php

namespace JetBackup\CLI;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Entities\Util;
use WP_CLI;

class CLI {

	const OUTPUT_TYPE_TABLE = 'table';
	const OUTPUT_TYPE_JSON = 'json';
	const OUTPUT_TYPE_CSV = 'csv';
	const OUTPUT_TYPE_YAML = 'yaml';
	const OUTPUT_TYPE_COUNT = 'count';

	const OUTPUT_TYPES = [
		self::OUTPUT_TYPE_TABLE,
		self::OUTPUT_TYPE_JSON,
		self::OUTPUT_TYPE_CSV,
		self::OUTPUT_TYPE_YAML,
		self::OUTPUT_TYPE_COUNT,
	];
	
	public static function init() {
		WP_CLI::add_command('jetbackup', '\JetBackup\CLI\Command');
	}

	public static function date($time) {
		return Util::date("d-M-Y H:i", (int) $time);
	}
}
