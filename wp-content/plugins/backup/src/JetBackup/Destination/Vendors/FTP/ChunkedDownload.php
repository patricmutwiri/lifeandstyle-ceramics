<?php

namespace JetBackup\Destination\Vendors\FTP;

use JetBackup\Destination\Integration\DestinationChunkedDownload;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedDownload implements DestinationChunkedDownload {

	private FTP $_client;
	private string $_source;
	private string $_destination;

	public function __construct(FTP $client, $source, $destination) {
		$this->_client = $client;
		$this->_source = $source;
		$this->_destination = $destination;
	}
	
	/**
	 * @return string
	 */
	public function download(int $start, int $end):int {
		$this->_client->_client('download', $this->_source, $this->_destination, $start, $end);
		return $end - $start + 1;
	}
}