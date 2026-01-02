<?php

namespace JetBackup\Destination\Vendors\GoogleDrive;

use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Vendors\GoogleDrive\Client\Client;
use JetBackup\Exception\IOException;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileException;
use JetBackup\Web\File\FileStream;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedUpload implements DestinationChunkedUpload {

	private GoogleDrive $_client;
	private FileStream $_source;
	private string $_destination;
	private string $_parent;
	private object $_data;

	/**
	 * @throws FileException
	 */
	public function __construct(GoogleDrive $client, $source, $destination, $parent) {
		$this->_client = $client;
		$this->_source = new FileStream($source);
		$this->_destination = $destination;
		$this->_parent = $parent;
		$this->_data = new \stdClass();
	}

	/**
	 * @return object|mixed
	 * @throws IOException
	 */
	public function prepare():object {
		$this->_client->getLogController()->logDebug("[ChunkedUpload prepare]");
		return $this->_client->_retries(function() {
			$output = new \stdClass();
			$output->upload_url = $this->_client->getClient()->getUploadURL($this->_source, $this->_destination, $this->_parent);
			return $output;
		}, "Failed fetching upload URL");
	}

	public function setData(object $data):void {
		$this->_data = $data;		
	}
	
	/**
	 * @return int
	 * @throws IOException
	 */
	public function getOffset():int {
		if(!isset($this->_data->upload_url)) throw new IOException("No upload url was found");

		return $this->_client->_retries(function() {
			return $this->_client->getClient()->getUploadOffset($this->_data->upload_url);
		}, "Failed fetching upload offset");
	}

	/**
	 * @return int|null
	 */
	public function getChunkSize():?int { return null; }

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 * @throws IOException
	 */
	public function upload(FileChunk $chunk):void {
		if(!isset($this->_data->upload_url)) throw new IOException("No upload url was found");

		$this->_client->_retries(function() use($chunk) {
			$this->_client->getClient()->uploadChunk($chunk, $this->_data->upload_url);
		}, "Failed uploading chunk");
	}

	/**
	 * @return void
	 */
	public function finalize():void {}
}