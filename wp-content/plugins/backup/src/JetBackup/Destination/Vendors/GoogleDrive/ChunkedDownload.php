<?php

namespace JetBackup\Destination\Vendors\GoogleDrive;

use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Exception\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedDownload implements DestinationChunkedDownload {

	private GoogleDrive $_client;
	private string $_file_id;
	private string $_destination;

	public function __construct(GoogleDrive $client, $file_id, $destination) {
		$this->_client = $client;
		$this->_file_id = $file_id;
		$this->_destination = $destination;
	}

	/**
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 * @throws IOException
	 */
	public function download(int $start, int $end):int {
		return $this->_client->_retries(function() use ($start, $end) {
			return $this->_client->getClient()->downloadChunk($this->_file_id, $this->_destination, $start, $end);
		}, "Failed downloading chunk");
	}
}