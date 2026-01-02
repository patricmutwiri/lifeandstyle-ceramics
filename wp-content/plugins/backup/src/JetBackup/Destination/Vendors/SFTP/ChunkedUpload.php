<?php

namespace JetBackup\Destination\Vendors\SFTP;

use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Exception\IOException;
use phpseclib3\Net\SFTP as lSFTP;
use JetBackup\Web\File\FileChunk;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedUpload implements DestinationChunkedUpload {

	private SFTP $_connection;
	private string $_destination;
	private \stdClass $_data;

	public function __construct(SFTP $connection, $destination) {
		$this->_connection = $connection;
		$this->_destination = $destination;
		$this->_data = new \stdClass();
	}
	
	/**
	 * @return object
	 */
	public function prepare():object { return new \stdClass(); }
	public function setData(object $data):void {}

	/**
	 * @return int
	 * @throws IOException
	 */
	public function getOffset():int {
		return $this->_connection->retries(function() {
			$stat = $this->_connection->getConnection()->stat($this->_destination);
			return $stat ? $stat['size'] : 0;
		}, "Failed fetching upload offset");
	}

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 * @throws IOException
	 */
	public function upload(FileChunk $chunk):void {
		while($data = $chunk->read(1024 * 1024)) {
			$this->_connection->retries(function() use ($chunk, $data) {
				$offset = $chunk->getFile()->tell();
				$this->_connection->getConnection()->put($this->_destination, $data, lSFTP::SOURCE_STRING, $offset);
			}, "Failed uploading chunk");
		}
	}

	/**
	 * @return void
	 */
	public function finalize():void {}

	/**
	 * @inheritDoc
	 */
	public function getChunkSize(): ?int {
		return null;
	}
}