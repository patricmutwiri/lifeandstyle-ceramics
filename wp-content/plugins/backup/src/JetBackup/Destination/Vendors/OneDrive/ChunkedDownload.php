<?php

namespace JetBackup\Destination\Vendors\OneDrive;

use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Vendors\OneDrive\Client\Client;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedDownload implements DestinationChunkedDownload {

	private OneDrive $_client;
	private string $_source;
	private string $_destination;

	public function __construct(OneDrive $client, $source, $destination) {
		$this->_client = $client;
		$this->_source = $source;
		$this->_destination = $destination;
	}

	/**
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 * @throws Client\ClientException
	 * @throws \JetBackup\Exception\IOException
	 */
	public function download(int $start, int $end): int {
		$this->_client->getLogController()->logDebug("[ChunkedDownload download] Source: {$this->_source} Destination: {$this->_destination}");
		$this->_client->getLogController()->logDebug("[ChunkedDownload download] Start: $start End: $end");

		// Call the Client's downloadChunked method
		return $this->_client->getClient()->downloadChunked(
			OneDrive::DRIVE_ROOT . ':' . $this->_source . ':/content',
			$this->_destination,
			$start,
			$end
		);
	}
}