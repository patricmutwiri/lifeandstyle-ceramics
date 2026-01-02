<?php

namespace JetBackup\Schedule;

use JetBackup\Data\ArrayData;
use JetBackup\Exception\JBException;
use JetBackup\Exception\ScheduleException;
use JetBackup\JetBackup;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ScheduleItem extends ArrayData {
	
	const TYPE        = 'type';
	const NEXT_RUN    = 'next_run';
	const RETAIN      = 'retain';

	private ?Schedule $_instance=null;

	/**
	 * @param array|null $data
	 */
	public function __construct(?array $data=null) {
		if($data) $this->setData($data);
	}

	/**
	 * @param int $_id
	 *
	 * @return void
	 */
	public function setId(int $_id):void {
		$this->set(JetBackup::ID_FIELD, $_id);
	}

	/**
	 * @return int
	 */
	public function getId():int {
		return (int) $this->get(JetBackup::ID_FIELD, 0);
	}

	/**
	 * @param int $type
	 *
	 * @return void
	 */
	public function setType(int $type):void { $this->set(self::TYPE, $type); }

	/**
	 * @return int
	 * @throws JBException
	 */
	public function getType():int {
		$type = $this->get(self::TYPE, 0);
		if(!$type) $type = $this->getScheduleInstance()->getType();
		return $type;
	}

	/**
	 * @param int|null $time
	 * @param string|null $scheduleTime
	 *
	 * @return void
	 * @throws ScheduleException
	 */
	public function setNextRun(?int $time, ?string $scheduleTime=null):void {
		if($time === null) {
			$instance = $this->getScheduleInstance();
			$time = $instance ? $instance->calculateNextRun($scheduleTime) : 0;
		}
		$this->set(self::NEXT_RUN, $time);
	}

	/**
	 * @return int
	 */
	public function getNextRun():int { return (int) $this->get(self::NEXT_RUN, 0); }

	/**
	 * @param int $retain
	 */
	public function setRetain(int $retain):void { $this->set(self::RETAIN, $retain); }

	/**
	 * @return int
	 */
	public function getRetain():int { return $this->get(self::RETAIN, 0); }

	/**
	 * @return array|null
	 */
	public function getTypeData():?array {
		$instance = $this->getScheduleInstance();
		return $instance ? $instance->getIntervals() : null;
	}

	/**
	 * @param Schedule $instance
	 *
	 * @return void
	 */
	public function setScheduleInstance(Schedule $instance):void { $this->_instance = $instance; }

	/**
	 * @return Schedule|null
	 */
	public function getScheduleInstance():?Schedule {
		if(!$this->_instance) {
			$_id = $this->getId();
			if($_id) $this->_instance = new Schedule($_id);
		}

		return $this->_instance;
	}
}
