<?php

namespace JetBackup\Destination\Vendors\Box;

use JetBackup\Destination\Integration\DestinationChunkedDownload;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedDownload implements DestinationChunkedDownload {

	private Box $_client;
	private string $_file_id;
	private string $_destination;

	public function __construct(Box $client, $file_id, $destination) {
		$this->_client = $client;
		$this->_file_id = $file_id;
		$this->_destination = $destination;
	}

	/**
	 * @return string
	 */
	public function download(int $start, int $end):int {
		return $this->_client->_retries(function() use ($start, $end) {
			$response = $this->_client->getClient()->download($this->_file_id, $this->_destination, $start, $end);
			return (int) $response->Headers->{'content-length'};
		}, "Failed downloading chunk");
	}
}