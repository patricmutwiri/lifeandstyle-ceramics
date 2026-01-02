<?php

namespace JetBackup\Showcase;

use JetBackup\Wordpress\Helper;
use JetBackup\Wordpress\Wordpress;

if (!defined('__JETBACKUP__')) die('Direct access is not allowed');

class Showcase {

	const STATUS = 'status';
	const TYPE = 'type';
	const META_KEY = 'jetbackup_showcase_data';
	const QUICK_START = 'quick_start';

	const SHOWCASE_DEFAULTS = [
		self::QUICK_START => false,
	];

	private function __construct() {}

	public static function getAll() : array {
		$data = Wordpress::getUserMeta(Helper::getUserId(), self::META_KEY, true);
		return is_array($data) ? array_merge(self::SHOWCASE_DEFAULTS, $data) : self::SHOWCASE_DEFAULTS;
	}

	private static function saveAll(array $data) : void {
		Wordpress::updateUserMeta(Helper::getUserId(), self::META_KEY, $data);
	}

	public static function setQuickStart(bool $value) : void {
		$data = self::getAll();
		$data[self::QUICK_START] = $value;
		self::saveAll($data);
	}

	public static function isQuickStartDisabled() : bool {
		$data = self::getAll();
		return $data[self::QUICK_START] ?? false;
	}

}
