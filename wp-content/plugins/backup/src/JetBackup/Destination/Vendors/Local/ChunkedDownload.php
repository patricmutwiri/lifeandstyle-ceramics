<?php

namespace JetBackup\Destination\Vendors\Local;

use JetBackup\Destination\Integration\DestinationChunkedDownload;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedDownload implements DestinationChunkedDownload {

	private $_source;
	private $_destination;

	public function __construct($source, $destination) {
		$this->_source = fopen($source, 'r');
		$this->_destination = fopen($destination, 'a');
	}
	
	/**
	 * @return string
	 */
	public function download(int $start, int $end):int {
		fseek($this->_source, $start);

		$readed = 0;
		$left = $end - $start;
		
		while($left && !feof($this->_source)) {
			$read = fread($this->_source, min($left, 1024 * 1024));
			fwrite($this->_destination, $read);
			$left -= strlen($read);
			$readed += strlen($read);
		}
		
		return $readed;
	}
}