<?php

namespace JetBackup\Settings;

use JetBackup\Exception\DBException;
use JetBackup\Exception\IOException;
use ReflectionException;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Restore extends Settings {

	const SECTION = 'restore';
	
	const RESTORE_COMPATIBILITY_CHECK = 'RESTORE_COMPATIBILITY_CHECK';
	const RESTORE_ALLOW_CROSS_DOMAIN = 'RESTORE_ALLOW_CROSS_DOMAIN';
	const RESTORE_ALTERNATE_PATH = 'RESTORE_ALTERNATE_PATH';
	const RESTORE_WP_CONTENT_ONLY = 'RESTORE_WP_CONTENT_ONLY';
	/**
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
	}

	/**
	 * @return bool
	 */
	public function isRestoreCompatibilityCheckEnabled():bool { return (bool) $this->get(self::RESTORE_COMPATIBILITY_CHECK, true); }
	public function isRestoreAlternatePathEnabled():bool { return (bool) $this->get(self::RESTORE_ALTERNATE_PATH, false); }
	public function isRestoreWpContentOnlyEnabled():bool { return (bool) $this->get(self::RESTORE_WP_CONTENT_ONLY, false); }
	public function isRestoreAllowCrossDomain():bool { return (bool) $this->get(self::RESTORE_ALLOW_CROSS_DOMAIN, false); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setRestoreCompatibilityCheck(bool $value):void { $this->set(self::RESTORE_COMPATIBILITY_CHECK, $value); }
	public function setRestoreAlternatePath(bool $value):void { $this->set(self::RESTORE_ALTERNATE_PATH, $value); }
	public function setRestoreWpContentOnly(bool $value):void { $this->set(self::RESTORE_WP_CONTENT_ONLY, $value); }

	public function setRestoreAllowCrossDomain(bool $value):void { $this->set(self::RESTORE_ALLOW_CROSS_DOMAIN, $value); }

	/**
	 * @return bool[]
	 */
	public function getDisplay():array {

		return [
			self::RESTORE_COMPATIBILITY_CHECK   => $this->isRestoreCompatibilityCheckEnabled() ? 1 : 0,
			self::RESTORE_ALLOW_CROSS_DOMAIN   => $this->isRestoreAllowCrossDomain() ? 1 : 0,
			self::RESTORE_ALTERNATE_PATH   => $this->isRestoreAlternatePathEnabled() ? 1 : 0,
			self::RESTORE_WP_CONTENT_ONLY   => $this->isRestoreWpContentOnlyEnabled() ? 1 : 0,
		];
	}

	/**
	 * @return bool[]
	 */
	public function getDisplayCLI():array {

		return [
			'Restore Compatability Check'   => $this->isRestoreCompatibilityCheckEnabled(),
			'Restore Allow Cross Domain'   => $this->isRestoreAllowCrossDomain(),
			'Restore Alternate Path'   => $this->isRestoreAlternatePathEnabled(),
			'Limit restore to wp-content only'   => $this->isRestoreWpContentOnlyEnabled(),
		];
	}

	/**
	 * @return void
	 */
	public function validateFields():void {
	}
}