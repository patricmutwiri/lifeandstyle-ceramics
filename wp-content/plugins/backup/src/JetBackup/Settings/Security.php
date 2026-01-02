<?php

namespace JetBackup\Settings;

use JetBackup\Config\Config;
use JetBackup\Config\System;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\MFA\GoogleAuthenticator;
use JetBackup\Wordpress\UI;
use ReflectionException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Security extends Settings {

	const SECTION = 'security';

	const MFA_ENABLED = 'MFA_ENABLED';
	const MFA_ALLOW_CLI = 'MFA_ALLOW_CLI';
	const DAILY_CHECKSUM_CHECK = 'DAILY_CHECKSUM_CHECK';
	const DATADIR_SECURED = 'SECURITY_DATADIR_SECURED';
	const DATADIR_RECOMMENDED = 'SECURITY_DATADIR_RECOMMENDED';

	/**
	 * @throws IOException
	 * @throws ReflectionException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
	}

	/**
	 * @return bool
	 */
	public function isMFAEnabled():bool { return (bool) $this->get(self::MFA_ENABLED, false); }
	public function isMFAAllowCLI():bool { return (bool) $this->get(self::MFA_ALLOW_CLI, false); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setMFAEnabled(bool $value):void { $this->set(self::MFA_ENABLED,$value); }
	public function setMFAAllowCLI(bool $value):void { $this->set(self::MFA_ALLOW_CLI,$value); }

	/**
	 * @return bool
	 */
	public function isValidateChecksumsEnabled():bool { return (bool) $this->get(self::DAILY_CHECKSUM_CHECK, false); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setValidateChecksumsEnabled(bool $value):void { $this->set(self::DAILY_CHECKSUM_CHECK, $value); }

	/**
	 * @return array
	 */
	public function getDisplay():array {
		return [
			self::DATADIR_SECURED               => System::isDataDirSecured() ? 1 : 0,
			self::DATADIR_RECOMMENDED           => System::getRecommendSecurePath(),
			self::MFA_ENABLED                   => $this->isMFAEnabled() ? 1 : 0,
			self::MFA_ALLOW_CLI                 => $this->isMFAAllowCLI() ? 1 : 0,
			Config::ALTERNATE_DATA_FOLDER       => Factory::getConfig()->getAlternateDataFolder(),
			self::DAILY_CHECKSUM_CHECK          => $this->isValidateChecksumsEnabled() ? 1 : 0,
		];
	}

	/**
	 * @return array
	 */
	public function getDisplayCLI():array {
		return [
			'MFA Enabled'                       => $this->isMFAEnabled() ? "Yes" : "No",
			'MFA Allow CLI'                     => $this->isMFAAllowCLI() ? "Yes" : "No",
			'Alternate Data Directory'          => Factory::getConfig()->getAlternateDataFolder(),
			'Validate System Files Checksums'   => $this->isValidateChecksumsEnabled() ? "Yes" : "No",
		];
	}

	/**
	 */
	public function validateFields():void {}

	public function save(): void {
		if (!$this->isMFAEnabled()) GoogleAuthenticator::clearCookie();
		parent::save();
	}
}