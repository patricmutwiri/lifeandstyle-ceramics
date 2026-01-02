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
namespace JetBackup\Destination\Vendors\S3\Client;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class ListObjects {

	private string $_next_continuation_token = '';
	/** @var ObjectData[] */
	private array $_objects;
	private string $_isTruncated = '';

	/**
	 * 
	 */
	public function __construct() {
		$this->_objects = [];
	}

	/**
	 * @return ObjectData[]
	 */
	public function getObjectsList():array { return $this->_objects; }

	/**
	 * @param ObjectData[] $objects
	 *
	 * @return void
	 */
	public function setObjectsList(array $objects):void { $this->_objects = $objects; }

	/**
	 * @param ObjectData $object
	 * 
	 * @return void
	 */
	public function addObject(ObjectData $object):void { $this->_objects[] = $object; }

	/**
	 * @return int
	 */
	public function getObjectsCount():int { return count($this->_objects); }

	/**
	 * @return ObjectData|null
	 */
	public function getNextObject():?ObjectData { return $this->getObjectsCount() ? array_pop($this->_objects) : null; }

	/**
	 * @return string
	 */
	public function getNextContinuationToken():string { return $this->_next_continuation_token; }

	/**
	 * @param string $token
	 *
	 * @return void
	 */
	public function setNextContinuationToken(string $token): void { $this->_next_continuation_token = $token; }

	/**
	 * @return bool
	 */
	public function isTruncated(): bool { return $this->_isTruncated === 'true'; }

	/**
	 * @param string $isTruncated
	 *
	 * @return void
	 */
	public function setIsTruncated(string $isTruncated): void { $this->_isTruncated = $isTruncated; }

}