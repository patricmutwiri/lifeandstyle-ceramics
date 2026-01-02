<?php

namespace JetBackup\Destination\Vendors\OneDrive;

use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Vendors\OneDrive\Client\ClientException;
use JetBackup\Exception\IOException;
use JetBackup\Web\File\FileChunk;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedUpload implements DestinationChunkedUpload {

	private OneDrive $_client;
	private string $_destination;
	private object $_data;

	public function __construct(OneDrive $client, $destination) {
		$this->_client = $client;
		$this->_destination = $destination;
		$this->_data = new \stdClass();
	}
	
	/**
	 * @return object
	 * @throws ClientException
	 */
	public function prepare():object {

		$this->_client->getLogController()->logDebug("[ChunkedUpload] [prepare]");
		$output = new \stdClass();
		$response = $this->_client->client('createUploadSession', OneDrive::DRIVE_ROOT . ':' . $this->_destination);
		$output->upload_url = $response->Body->uploadUrl;
		return $output;
	}

	public function setData(object $data):void {
		$this->_data = $data;		
	}

	/**
	 * @return int
	 * @throws ClientException
	 * @throws IOException
	 */
	public function getOffset():int {

		if(!isset($this->_data->upload_url)) throw new IOException("No upload url was found");

		$response = $this->_client->client('checkUploadSession', $this->_data->upload_url);

		$this->_client->getLogController()->logDebug("[ChunkedUpload] [getOffset]");
		$range = $response->Body->nextExpectedRanges[0];

		// no range, start from the beginning
		if (empty($range)) {
			$this->_client->getLogController()->logDebug("[ChunkedUpload] [getOffset] No Range, Start from beginning");
			return 0;
		}

		list($offset) = explode('-', $range);
		$this->_client->getLogController()->logDebug("[ChunkedUpload] [getOffset] Offset: $offset");

		return (int) $offset;

	}

	/**
	 * @return int|null
	 */
	public function getChunkSize():?int { return null; }

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 * @throws ClientException
	 * @throws IOException
	 */
	public function upload(FileChunk $chunk):void {
		$this->_client->getLogController()->logDebug("..uploading chunk [" . $chunk->getSize() . " Bytes]");
		if(!isset($this->_data->upload_url)) throw new IOException("No upload url was found");
		$this->_client->client('putChunk', $chunk, $this->_data->upload_url);
	}

	/**
	 * @return void
	 */
	public function finalize():void {}
}