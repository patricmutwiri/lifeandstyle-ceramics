<?php

namespace JetBackup\Settings;

use JetBackup\Config\System;
use JetBackup\Data\DBObject;
use JetBackup\Data\ReflectionObject;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use ReflectionException;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

abstract class Settings extends DBObject {

	const COLLECTION = 'settings';

	/**
	 * @param string $section
	 *
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function __construct(string $section) {
		parent::__construct(self::COLLECTION);
		$this->_load([ ['_setting_id', '=', $section] ]);
		$this->set('_setting_id', $section);
	}


}