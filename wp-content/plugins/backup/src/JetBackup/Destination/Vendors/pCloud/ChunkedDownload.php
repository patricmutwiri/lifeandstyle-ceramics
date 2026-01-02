<?php

namespace JetBackup\Destination\Vendors\pCloud;

use JetBackup\Destination\Integration\DestinationChunkedDownload;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedDownload implements DestinationChunkedDownload {

	private pCloud $_client;
	private string $_source;
	private string $_destination;

	public function __construct(pCloud $client, $source, $destination) {
		$this->_client      = $client;
		$this->_source      = $source;
		$this->_destination = $destination;
	}

	/**
	 * @return string
	 */
	public function download(int $start, int $end):int {
		return $this->_client->_retries(function() use ($start, $end) {
			$response = $this->_client->getClient()->download($this->_source, $this->_destination, $start, $end);
			return (int) $response->Headers->{'content-length'};
		}, "Failed downloading chunk");
	}
}