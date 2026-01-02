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
namespace JetBackup\Web\File;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class FileStream {

	const CHUNK_SIZE_PIECE = 1048576; // 1MB

	private string $_file;
	private $_fd;
	private int $_size;

	/**
	 * @param string $file
	 * @param string $mode
	 *
	 * @throws FileException
	 */
	public function __construct(string $file, string $mode="r") {
		if(!file_exists($file)) throw new FileException("Provided file not exists");
		$this->_file = $file;
		$this->_fd = fopen($this->_file, $mode);
		if($this->getDescriptor() === false) throw new FileException("Failed to open file stream");
		$this->_size = filesize($this->_file);
	}

	/**
	 * @return string
	 */
	public function getFile():string {
		return $this->_file;
	}

	/**
	 * @return int
	 */
	public function getSize():int {
		return $this->_size;
	}

	/**
	 * @return false|string
	 */
	public function getMimeType() {
		if(function_exists('mime_content_type')) return mime_content_type($this->_file);
		else {
			$file = new \CURLFile($this->_file);
			return $file->getMimeType() ?: 'text/plain';
		}
	}

	/**
	 * @return false|resource
	 */
	public function getDescriptor() {
		return $this->_fd;
	}

	/**
	 * @return false|int
	 */
	public function tell() {
		return ftell($this->getDescriptor());
	}

	/**
	 * @param int|null $length
	 *
	 * @return string
	 */
	public function read(?int $length=null):string {
		if($length === null) $length = $this->getSize();
		return fread($this->getDescriptor(), $length);
	}

	/**
	 * @return bool
	 */
	public function eof():bool {
		return feof($this->getDescriptor());
	}
	
	/**
	 * @param int $offset
	 * @param int $whence
	 *
	 * @return int
	 */
	public function seek(int $offset, int $whence=SEEK_SET):int {
		return fseek($this->getDescriptor(), $offset, $whence);
	}

	/**
	 * @return void
	 */
	public function rewind():void {
		$this->seek(0);
	}

	/**
	 * @return void
	 */
	public function close():void {
		if (is_resource($this->_fd)) fclose($this->_fd);
		$this->_fd = null;
	}

	public function getHash(string $algorithm, $binary=false):string {
		$this->rewind();
		$ctx = hash_init($algorithm);
		while (!$this->eof()) hash_update($ctx, $this->read(self::CHUNK_SIZE_PIECE));
		$this->rewind();
		return hash_final($ctx, $binary);
	}

	/**
	 * 
	 */
	public function __destruct() {
		$this->close();
	}
}