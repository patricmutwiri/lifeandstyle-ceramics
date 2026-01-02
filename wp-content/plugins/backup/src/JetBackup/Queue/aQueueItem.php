<?php

namespace JetBackup\Queue;

use JetBackup\Data\ArrayData;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

abstract class aQueueItem extends ArrayData {

	public function __construct($data=[]) {
		$this->setData($data);
	}

	abstract public function getDisplay():array;
}
