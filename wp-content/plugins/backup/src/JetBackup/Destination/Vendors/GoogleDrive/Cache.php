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
namespace JetBackup\Destination\Vendors\GoogleDrive;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class Cache {

	const CACHE_DEFAULT_SIZE = 500;
	const CACHE_DEFAULT_TOLERANCE = 100;
	
	private array $_hash;
	private array $_priorities;
	private int $_size;
	private int $_tolerance;

	/**
	 * 
	 */
	public function __construct(){
		$this->_hash = [];
		$this->_priorities = [];
		$this->_size = self::CACHE_DEFAULT_SIZE;
		$this->_tolerance = self::CACHE_DEFAULT_TOLERANCE;
	}

	/**
	 * @param int $size
	 *
	 * @return void
	 */
	public function setSize(int $size):void { $this->_size = $size; }

	/**
	 * @return int
	 */
	public function getSize():int { return $this->_size; }

	/**
	 * @param int $tolerance
	 *
	 * @return void
	 */
	public function setTolerance(int $tolerance):void { $this->_tolerance = $tolerance; }

	/**
	 * @return int
	 */
	public function getTolerance():int { return $this->_tolerance; }

	/**
	 * @param string $key
	 *
	 * @return string|null
	 */
	public function get(string $key):?string {
		if(!isset($this->_hash[$key])) return null;
		$this->_promote($key);
		return $this->_hash[$key];
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 *
	 * @return void
	 */
	public function set(string $key, $data):void {
		if($this->has($key)) $this->remove($key);
		$this->_hash[$key] = $data;
		$this->_priorities[] = $key;
		$this->_cleanup();
	}

	/**
	 * @return array
	 */
	public function getList():array {
		return $this->_hash;
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has(string $key):bool {
		return isset($this->_hash[$key]);
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public function remove(string $key):void {
		if(!$this->has($key)) return;
		$this->_removePriority($key);
		unset($this->_hash[$key]);
	}

	/**
	 * @return int
	 */
	public function getActualSize():int {
		return sizeof($this->_priorities);
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	private function _promote(string $key):void {
		$this->_removePriority($key);
		$this->_priorities[] = $key;
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	private function _removePriority(string $key):void {
		$ind = array_search($key, $this->_priorities);
		array_splice($this->_priorities, $ind, 1);
	}

	/**
	 * @return void
	 */
	private function _cleanup():void {
		if($this->getActualSize() <= $this->getSize()+$this->getTolerance()) return;
		while($this->getActualSize() > $this->getSize()) {
			$key = array_shift($this->_priorities);
			unset($this->_hash[$key]);
		}
	}
}
