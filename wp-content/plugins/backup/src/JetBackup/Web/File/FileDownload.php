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

use JetBackup\Exception\IOException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class FileDownload {

	const DELIMITER = "\r\n\r\n";

	private string $_destination;
	private string $_headers = '';
	private string $_buffer = '';
	private $_fd;

	/**
	 * @param string $destination
	 */
	public function __construct(string $destination) {
		$this->_destination = $destination;
		$this->_fd = fopen($this->_destination, 'a');
		if (!$this->_fd) throw new IOException("Failed to open file: {$this->_destination}");
	}

	/**
	 * @return void
	 */
	public function deleteFile():void {
		$this->close();
		if(file_exists($this->_destination)) unlink($this->_destination);
	}

	/**
	 * @return string
	 */
	public function getHeaders():string { return $this->_headers; }

	/**
	 * @param string $str
	 *
	 * @return void
	 */
	public function read(string $str):void {
		$this->writeHeaders($str);
		$this->writeFile($str);
	}

	/**
	 * @param string $str
	 *
	 * @return void
	 */
	public function writeHeaders(string &$str):void {

		if($this->_headers) return;
		$this->_buffer .= $str;

		// must receive more than 4 bytes - wait for more data
		if(strlen($this->_buffer) < 5) {
			$str = '';
			return;
		}

		$d = substr($this->_buffer, 0, 4);
		$o = 4;

		while(self::DELIMITER != $d) {
			$d = substr($this->_buffer, $o++, 4);
			if($d === false || (strlen($this->_buffer) < $o+3)) {
				$str = '';
				return;
			}
		}

		$this->_headers = substr($this->_buffer, 0, $o-1);
		$str = substr($this->_buffer, $o+3);
		$this->_buffer = '';
	}

	/**
	 * @param string $str
	 *
	 * @return void
	 */
	public function writeFile(string $str):void {
		if(!$this->_fd || $str === '') return;
		fwrite($this->_fd, $str);
		fflush($this->_fd);
	}

	/**
	 * @return void
	 */
	public function close():void {
		if(!$this->_fd) return;
		@fclose($this->_fd);
		$this->_fd = null;
	}

	/**
	 * 
	 */
	public function __destruct() {
		$this->close();
	}
}