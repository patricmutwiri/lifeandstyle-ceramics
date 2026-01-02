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

class ObjectData {

	private string $_key='';
	//private $_etag;
	private int $_size=0;
	private int $_mtime=0;
	//private $_type;
	private bool $_is_dir = false;

	/**
	 * @return string
	 */
	public function getKey():string { return $this->_key; }

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public function setKey(string $key): void {
		$this->_key = $key;
		if(str_ends_with($this->_key, '/')) {
			$this->_is_dir = true;
			$this->_key = substr($this->_key, 0, -1);
		}
	}

	/*
	public function getEtag() { return $this->_etag; }
	public function setEtag($etag) { $this->_etag = (string) str_replace('"', '', $etag); }
	*/

	/**
	 * @return int
	 */
	public function getSize():int { return $this->_size; }

	/**
	 * @param int $size
	 *
	 * @return void
	 */
	public function setSize(int $size):void { $this->_size = $size; }

	/**
	 * @return int
	 */
	public function getMtime():int { return $this->_mtime; }

	/**
	 * @param int $mtime
	 *
	 * @return void
	 */
	public function setMtime(int $mtime):void { $this->_mtime = $mtime; }

	/*
	public function getType() { return $this->_type; }
	public function setType($type) { $this->_type = (string) $type; }
	*/

	/**
	 * @return bool
	 */
	public function isDir():bool { return !!$this->_is_dir; }
}