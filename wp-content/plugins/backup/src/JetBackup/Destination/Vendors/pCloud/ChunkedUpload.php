<?php

namespace JetBackup\Destination\Vendors\pCloud;

use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Exception\IOException;
use JetBackup\Web\File\FileChunk;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ChunkedUpload implements DestinationChunkedUpload {

	private pCloud $_client;
	private string $_destination;
	private object $_data;

	public function __construct(pCloud $client, $destination) {
		$this->_client = $client;
		$this->_destination = $destination;
		$this->_data = new \stdClass();
	}
	
	/**
	 * @return object
	 */
	public function prepare():object {
		return $this->_client->_retries(function() {
			$response = $this->_client->getClient()->startUploadSession();

			// Debugging: Log the full response
			$this->_client->getlogController()->logDebug("[ChunkedUpload prepare] Response: " . json_encode($response));

			if (!isset($response->uploadid)) {
				throw new IOException("Upload session ID is missing from the response.");
			}

			$output = new \stdClass();
			$output->id = $response->uploadid;
			return $output;
		}, "Failed creating upload session");
	}

	public function setData(object $data):void {
		$this->_data = $data;		
	}
	
	/**
	 * @return int
	 */
	public function getOffset():int {
		if(!isset($this->_data->id)) throw new IOException("No session id was found");

		return $this->_client->_retries(function() {
			$offset = $this->_client->getClient()->getUploadSession($this->_data->id)->size;
			$this->_client->getlogController()->logDebug("[ChunkedUpload getOffset] Offset: $offset");
			return max( $offset, 0 );
		}, "Failed fetching upload offset");
	}

	/**
	 * @return int|null
	 */
	public function getChunkSize():?int {
		if (!isset($this->_data->part_size)) {
			$this->_client->getlogController()->logDebug("[ChunkedUpload getChunkSize] part_size is missing in the session data. Returning default chunk size.");
			return $this->_client->getChunkSize();
		}
		return $this->_data->part_size;
	}

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 */
	public function upload(FileChunk $chunk): void {
		if (!isset($this->_data->id)) {
			throw new IOException("No session id was found");
		}

		$this->_client->_retries(function () use ($chunk) {
			$this->_client->getClient()->appendUploadSession($this->_data->id, $chunk);
		}, "Failed uploading chunk");
	}


	/**
	 * @return void
	 */
	public function finalize(): void {
		if (!isset($this->_data->id)) {
			throw new IOException("No session id was found");
		}

		$this->_client->_retries(function () {
			$response = $this->_client->getClient()->commitUploadSession($this->_data->id, $this->_destination);

			if (!$response) {
				$this->_client->getlogController()->logDebug("[ChunkedUpload finalize] Commit response is empty.");
			} else {
				$this->_client->getlogController()->logDebug("[ChunkedUpload finalize] Upload session committed successfully.");
			}
		}, "Failed committing upload session");
	}

}