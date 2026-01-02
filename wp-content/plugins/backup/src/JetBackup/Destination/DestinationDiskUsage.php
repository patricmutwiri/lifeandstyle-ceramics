<?php
/*
*
* JetBackup @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/
namespace JetBackup\Destination;

use JetBackup\Data\ArrayData;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DestinationDiskUsage extends ArrayData implements Integration\DestinationDiskUsage {

	/**
	 * @param array|null $data
	 */
	public function __construct(?array $data=null) {
		if($data) $this->setData($data);
	}

	/**
	 * @return int
	 */
	public function getFreeSpace():int { return $this->get('free', 0); }

	/**
	 * @param int $space
	 *
	 * @return void
	 */
	public function setFreeSpace(int $space):void { $this->set('free', $space); }

	/**
	 * @return int
	 */
	public function getTotalSpace(): int { return $this->get('total', 0); }

	/**
	 * @param int $space
	 *
	 * @return void
	 */
	public function setTotalSpace(int $space):void { $this->set('total', $space); }

	/**
	 * @return int
	 */
	public function getUsageSpace(): int { return $this->get('usage', 0); }

	/**
	 * @param int $space
	 *
	 * @return void
	 */
	public function setUsageSpace(int $space):void { $this->set('usage', $space); }
}