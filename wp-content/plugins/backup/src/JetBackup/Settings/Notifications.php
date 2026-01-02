<?php

namespace JetBackup\Settings;

use JetBackup\Alert\Alert;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Notifications extends Settings {

	const SECTION = 'notifications';

	const EMAILS = 'EMAILS';
	const ALTERNATE_EMAIL = 'ALTERNATE_EMAIL';
	const ADMIN_EMAIL = 'ADMIN_EMAIL';
	const NOTIFICATION_LEVELS_FREQUENCY = 'NOTIFICATION_LEVELS_FREQUENCY';

	const NOTIFICATION_FREQUENCY_DISABLED = 0;
	const NOTIFICATION_FREQUENCY_REAL_TIME = 1;
	const NOTIFICATION_FREQUENCY_DAILY = 2;

	const NOTIFICATION_FREQUENCIES = [
		self::NOTIFICATION_FREQUENCY_DISABLED,
		self::NOTIFICATION_FREQUENCY_DAILY,
		self::NOTIFICATION_FREQUENCY_REAL_TIME
	];

	const NOTIFICATION_FREQUENCY_DEFAULTS = [
		Alert::LEVEL_INFORMATION     => self::NOTIFICATION_FREQUENCY_DISABLED,
		Alert::LEVEL_WARNING     => self::NOTIFICATION_FREQUENCY_DISABLED,
		Alert::LEVEL_CRITICAL     => self::NOTIFICATION_FREQUENCY_REAL_TIME,
	];

	const NOTIFICATION_FREQUENCY_NAMES = [
		self::NOTIFICATION_FREQUENCY_DISABLED       => 'Disabled',
		self::NOTIFICATION_FREQUENCY_REAL_TIME      => 'Real Time',
		self::NOTIFICATION_FREQUENCY_DAILY          => 'Daily',

	];

	/**
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
	}

	/**
	 * @return bool
	 */
	public function isEmailsEnabled():bool { return (bool) $this->get(self::EMAILS, true); }

	/**
	 * @param bool $value
	 *
	 * @return void
	 */
	public function setEmailsEnabled(bool $value):void { $this->set(self::EMAILS, $value); }

	/**
	 * @return string
	 */
	public function getAlternateEmail():string { return $this->get(self::ALTERNATE_EMAIL); }

	/**
	 * @param $value
	 *
	 * @return void
	 */
	public function setAlternateEmail($value):void { $this->set(self::ALTERNATE_EMAIL, $value); }
	public function setAlertLevelFrequency(array $value):void { $this->set(self::NOTIFICATION_LEVELS_FREQUENCY, $value); }

	public function getAlertLevelFrequency():array {return $this->get(self::NOTIFICATION_LEVELS_FREQUENCY, self::NOTIFICATION_FREQUENCY_DEFAULTS);}

	/**
	 * @return array
	 */
	public function getDisplay():array {

		return [
			self::EMAILS                        => $this->isEmailsEnabled() ? 1 : 0,
			self::ALTERNATE_EMAIL               => $this->getAlternateEmail(),
			self::ADMIN_EMAIL                   => Wordpress::getBlogInfo('admin_email'),
			self::NOTIFICATION_LEVELS_FREQUENCY => $this->getAlertLevelFrequency(),
		];
	}

	/**
	 * @return array
	 */
	public function getDisplayCLI():array {

		$alert_levels = [];
		foreach($this->getAlertLevelFrequency() as $level => $frequency)
			$alert_levels[] = Alert::LEVEL_NAMES[$level] . ' - ' . self::NOTIFICATION_FREQUENCY_NAMES[$frequency];

		return [
			'Emails'                        => $this->isEmailsEnabled() ? "Yes" : "No",
			'Alternate Email'               => $this->getAlternateEmail(),
			'Admin Email'                   => Wordpress::getBlogInfo('admin_email'),
			'Email Alert Levels'            => implode(", ", $alert_levels),
		];
	}

	/**
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {

		$frequency = $this->getAlertLevelFrequency();

		foreach ($frequency as $key => $value) {

			if (!($key & Alert::LEVELS)) 
				throw new FieldsValidationException("Invalid alert level key: $key");

			if (!in_array($value, self::NOTIFICATION_FREQUENCIES))
				throw new FieldsValidationException("Invalid frequency value for $key: $value");
		}

		if ($this->getAlternateEmail() && !Wordpress::isEmail($this->getAlternateEmail())) 
			throw new FieldsValidationException("Email " . $this->getAlternateEmail() . " is not valid");
	}
}