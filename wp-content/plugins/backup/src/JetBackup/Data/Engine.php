<?php

namespace JetBackup\Data;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Engine extends DBObject {

	const ENGINE = 'engine';

	const ENGINE_WP = 1;
	const ENGINE_JB = 2;
	const ENGINE_SGB = 3;

	const ENGINE_NAMES = [
		self::ENGINE_WP     => 'Wordpress',
		self::ENGINE_JB     => 'JetBackup',
		self::ENGINE_SGB     => 'SGB',
	];

	public function setEngine(int $value):void { $this->set(self::ENGINE, $value); }
	public function getEngine():int { return (int) $this->get(self::ENGINE, self::ENGINE_WP); }

	public function getEngineName():string { return self::ENGINE_NAMES[$this->getEngine()]; }
}