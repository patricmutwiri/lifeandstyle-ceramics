<?php

namespace JetBackup\Destination\Vendors\DropBox;

use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Vendors\DropBox\Client\Client;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedDownload implements DestinationChunkedDownload {

	private Client $_client;
	private string $_source;
	private string $_destination;

	public function __construct(Client $client, $source, $destination) {
		$this->_client = $client;
		$this->_source = $source;
		$this->_destination = $destination;
	}
	
	/**
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 */
	public function download(int $start, int $end):int {

		$this->_client->getLogController()->logDebug("[ChunkedDownload] Source: {$this->_source} Destination: {$this->_destination}");
		$response = $this->_client->download($this->_source, $this->_destination, $start, $end);
		$headers = $response->Headers;
		$contentLength = isset($headers->{'content-length'}) ? (int)$headers->{'content-length'} : 0;
		if ($contentLength <= 0) throw new \Exception("Failed to determine content length for chunk: $start-$end");

		return $contentLength;

	}
}