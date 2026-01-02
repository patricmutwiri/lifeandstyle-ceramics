<?php

namespace JetBackup\Destination\Vendors\Box;

use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Vendors\Box\Client\Client;
use JetBackup\Exception\IOException;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileException;
use JetBackup\Web\File\FileStream;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedUpload implements DestinationChunkedUpload {

	private Box $_client;
	private FileStream $_source;
	private string $_destination;
	private string $_parent;
	private object $_data;
	private array $_parts;

	/**
	 * @throws FileException
	 */
	public function __construct(Box $client, $source, $destination, $parent=Client::ROOT_FOLDER) {
		$this->_client = $client;
		$this->_source = new FileStream($source);
		$this->_destination = $destination;
		$this->_parent = $parent;
		$this->_parts = [];
		$this->_data = new \stdClass();
	}
	
	/**
	 * @return object|mixed
	 * @throws IOException
	 */
	public function prepare():object {
		return $this->_client->_retries(function() {
			return $this->_client->getClient()->startUploadSession(basename($this->_destination), $this->_source->getSize(), $this->_parent);
		}, "Failed creating upload session");
	}

	public function setData(object $data):void {
		$this->_data = $data;		
	}
	
	/**
	 * @return int
	 * @throws IOException
	 */
	public function getOffset():int {
		if(!isset($this->_data->id)) throw new IOException("No session id was found");

		return $this->_client->_retries(function() {
			$this->_parts = $this->_client->getClient()->listUploadSessionParts($this->_data->id)->entries;

			$offset = 0;
			foreach ($this->_parts as $part) $offset += $part->size;
			$res = max( $offset, 0 );
			$this->_client->getLogController()->logDebug("[getOffset] Offset: " . $res);
			return $res;

		}, "Failed fetching upload offset");
	}

	/**
	 * @return int|null
	 */
	public function getChunkSize():?int { return $this->_data->part_size; }

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 * @throws IOException
	 */
	public function upload(FileChunk $chunk):void {
		if(!isset($this->_data->id)) throw new IOException("No session id was found");

		$this->_client->_retries(function() use ($chunk) {
			$this->_parts[] = $this->_client->getClient()->appendUploadSession($this->_data->id, $chunk)->part;
			$this->_client->getLogController()->logDebug("[upload] Uploading chunk..." . $chunk->getSize());
		}, "Failed uploading chunk");
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	public function finalize():void {
		if(!isset($this->_data->id)) throw new IOException("No session id was found");

		$this->_client->_retries(function() {
			$this->_source->rewind();
			$this->_client->getClient()->commitUploadSession($this->_data->id, $this->_source, $this->_parts);
			$this->_client->getLogController()->logDebug("[finalize] Finalized upload session");
		}, "Failed commiting upload session");
	}
}