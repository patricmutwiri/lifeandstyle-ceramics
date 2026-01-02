<?php

namespace JetBackup\Destination\Vendors\S3;

use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Vendors\S3\Client\ClientManager;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedDownload implements DestinationChunkedDownload {

	private ClientManager $_client;
	private string $_source;
	private string $_destination;

	public function __construct(ClientManager $client, $source, $destination) {
		$this->_client = $client;
		$this->_source = $source;
		$this->_destination = $destination;
	}

	/**
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 * @throws Client\Exception\ClientException
	 */
	public function download(int $start, int $end):int {
		$object = $this->_client->getObject($this->_source, $this->_destination, $start, $end);
		return $object->getSize();
	}
}