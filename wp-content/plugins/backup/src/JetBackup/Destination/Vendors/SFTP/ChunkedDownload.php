<?php

namespace JetBackup\Destination\Vendors\SFTP;

use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Exception\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedDownload implements DestinationChunkedDownload {

	private SFTP $_connection;
	private string $_source;
	private string $_destination;

	public function __construct(SFTP $connection, $source, $destination) {
		$this->_connection = $connection;
		$this->_source = $source;
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
		return $this->_connection->retries(function() use ($start, $end) {
			$length = $end - $start;
			$fd = fopen($this->_destination, 'a');
			$this->_connection->getConnection()->get($this->_source, $fd, $start, $length);
			return $length + 1;
		}, "Failed downloading chunk");
	}
}