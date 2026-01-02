<?php

namespace JetBackup\Alert;

use Exception;
use JetBackup\Ajax\Ajax;
use JetBackup\CLI\CLI;
use JetBackup\Data\Engine;
use JetBackup\Data\SleekStore;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\NotificationException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Notification\Notification;
use JetBackup\Settings\Notifications;
use JetBackup\Wordpress\Wordpress;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;
use SleekDB\QueryBuilder;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Alert extends Engine {

	const COLLECTION = 'alerts';
	
	const UNIQUE_ID = 'unique_id';
	const CREATED = 'created';
	const TITLE = 'title';
	const MESSAGE = 'message';
	const LEVEL = 'level';

	const EMAIL_SENT = 'email_sent';

	const LEVEL_INFORMATION = 1;
	const LEVEL_WARNING = 2;
	const LEVEL_CRITICAL = 4;

	const LEVELS = self::LEVEL_INFORMATION | self::LEVEL_WARNING | self::LEVEL_CRITICAL;

	const LEVEL_NAMES = [
		self::LEVEL_INFORMATION     => 'Information',
		self::LEVEL_WARNING         => 'Warning',
		self::LEVEL_CRITICAL        => 'Critical',
	];
	
	public function __construct($_id=null) {
		parent::__construct(self::COLLECTION);
		if($_id) $this->_loadById((int) $_id);
	}

	public function setUniqueId($id) { $this->set(self::UNIQUE_ID, $id); }
	public function getUniqueId():string { return $this->get(self::UNIQUE_ID); }

	public function setCreated($value):void { $this->set(self::CREATED, $value); }
	public function getCreated() { return $this->get(self::CREATED); }

	public function setTitle($value):void { $this->set(self::TITLE, $value); }
	public function getTitle() { return $this->get(self::TITLE); }

	public function setMessage($value):void { $this->set(self::MESSAGE, $value); }
	public function getMessage() { return $this->get(self::MESSAGE); }

	public function setEmailSent(bool $bool):void { $this->set(self::EMAIL_SENT, $bool);}
	public function isEmailSent() : bool { return $this->get(self::EMAIL_SENT, false); }
	public function setLevel(int $value):void { $this->set(self::LEVEL, $value); }
	public function getLevel():int { return (int) $this->get(self::LEVEL); }

	public function save():void {
		if(!$this->getUniqueId()) $this->setUniqueId(Util::generateUniqueId());
		parent::save();
	}

	public static function db():SleekStore {
		return new SleekStore(self::COLLECTION);
	}

	public static function query():QueryBuilder {
		return self::db()->createQueryBuilder();
	}

	public static function getAlertNotificationFrequency(int $level) : int {
		$levels = Factory::getSettingsNotifications()->getAlertLevelFrequency();
		return $levels[$level] ?? 0;
	}

	/**
	 * @return int
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public static function getTotalAlerts():int {
		return count(self::query()
             ->select([JetBackup::ID_FIELD])
             ->getQuery()
             ->fetch());
	}

	/**
	 * @return int
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public static function getTotalCriticalAlerts():int {
		return count(self::query()
		                 ->select([JetBackup::ID_FIELD])
						->where([self::LEVEL, '=', self::LEVEL_CRITICAL])
		                 ->getQuery()
		                 ->fetch());
	}

	/**
	 * @throws IOException
	 * @throws DBException
	 * @throws InvalidArgumentException|NotificationException
	 * @throws Exception
	 */
	public static function processDailyAlerts() {

		if (!Factory::getSettingsNotifications()->isEmailsEnabled()) return;
		$levels = Factory::getSettingsNotifications()->getAlertLevelFrequency();
		$alerts = [];

		foreach ($levels as $level => $frequency) {

			if($frequency != Notifications::NOTIFICATION_FREQUENCY_DAILY) continue;

			$results = self::query()
				           ->select([JetBackup::ID_FIELD])
				           ->where([self::EMAIL_SENT, '=', false])
				           ->where([self::LEVEL, '=', $level])
			               ->getQuery()
			               ->fetch();

			if (empty($results)) continue;

			foreach ($results as $alert_id) {
				$alert = new Alert($alert_id[JetBackup::ID_FIELD]);
				if(!$alert->getId()) continue;
				$alerts[] = [
					'title' => $alert->getTitle(),
					'message' => $alert->getMessage(),
					'level' => self::LEVEL_NAMES[$alert->getLevel()],
					'date' => Util::date(
						Wordpress::getDateFormat() . ' ' . Wordpress::getTimeFormat(),
						(int) $alert->getCreated()
					),
				];

				$alert->setEmailSent(true);
				$alert->save();
			}

		}

		if (empty($alerts)) return;

		Notification::message()
		            ->addParam('backup_domain', Wordpress::getSiteDomain())
		            ->addParam('notification_frequency', Notifications::NOTIFICATION_FREQUENCY_NAMES[Notifications::NOTIFICATION_FREQUENCY_DAILY])
		            ->addParam('alerts', $alerts)
		            ->send('JetBackup WordPress Notification', 'alert');

	}

	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public static function clearAlerts():void {
		self::query()->getQuery()->delete();
	}

	/**
	 * @param string $title
	 * @param string $message
	 * @param int $level
	 *
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws NotificationException
	 * @throws Exception
	 */
	public static function add(string $title, string $message, int $level):void {

		$alert = new Alert();
		$alert->setEmailSent(false);

		if(self::getAlertNotificationFrequency($level) == Notifications::NOTIFICATION_FREQUENCY_REAL_TIME) {

			Notification::message()
			            ->addParam('backup_domain', Wordpress::getSiteDomain())
			            ->addParam('notification_frequency', Notifications::NOTIFICATION_FREQUENCY_NAMES[Notifications::NOTIFICATION_FREQUENCY_REAL_TIME])
						->addParam('alerts', [[
							'title' => $title,
							'message' => $message,
							'level' => self::LEVEL_NAMES[$level],
							'date' => Util::date(
								Wordpress::getDateFormat() . ' ' . Wordpress::getTimeFormat(),
								(int) $alert->getCreated()
							)
						]])
				->send('JetBackup WordPress Notification', 'alert');

			$alert->setEmailSent(true);
		}

		$alert->setCreated(time());
		$alert->setTitle($title);
		$alert->setMessage($message);
		$alert->setLevel($level);
		$alert->setEngine(Engine::ENGINE_WP);
		$alert->save();
	}

	/**
	 * @throws Exception
	 */
	public function getDisplay(): array {
		return [
			JetBackup::ID_FIELD => $this->getId(),
			self::UNIQUE_ID     => $this->getUniqueId(),
			self::CREATED       => $this->getCreated(),
			self::TITLE         => $this->getTitle(),
			self::MESSAGE       => $this->getMessage(),
			Engine::ENGINE      => $this->getEngine(),
			'engine_name'       => $this->getEngineName(),
			self::LEVEL         => $this->getLevel(),
			self::EMAIL_SENT => $this->isEmailSent()
		];
	}

	/**
	 * @throws Exception
	 */
	public function getDisplayCLI(): array {
		return [
			'ID'        => $this->getId(),
			'Created'   => CLI::date($this->getCreated()),
			'Title'     => $this->getTitle(),
			'Message'   => $this->getMessage(),
			'Engine'    => $this->getEngineName(),
			'Level'     => self::LEVEL_NAMES[$this->getLevel()],
			'Email Sent' => $this->isEmailSent() ? 'Yes' : 'No'
		];
	}
}