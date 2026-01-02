<?php

namespace JetBackup\Destination\Integration;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

interface DestinationDiskUsage {

	/**
	 * @return int
	 */
	public function getFreeSpace(): int;

	/**
	 * @param int $space
	 *
	 * @return void
	 */
	public function setFreeSpace(int $space):void;

	/**
	 * @return int
	 */
	public function getTotalSpace(): int;

	/**
	 * @param int $space
	 *
	 * @return void
	 */
	public function setTotalSpace(int $space):void;

	/**
	 * @return int
	 */
	public function getUsageSpace():int ;

	/**
	 * @param int $space
	 *
	 * @return void
	 */
	public function setUsageSpace(int $space):void;

	/**
	 * @return array
	 */
	public function getData():array;
}