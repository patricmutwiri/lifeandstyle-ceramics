<?php

namespace JetBackup\Destination\Integration;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

interface DestinationDirIterator {

	/**
	 * @return void
	 */
	public function rewind():void;

	/**
	 * @return bool
	 */
	public function hasNext():bool;

	/**
	 * @return DestinationFile|null
	 */
	public function getNext():?DestinationFile;
}