<?php
/*
*
* JetBackup @ package
* Created By Idan Ben-Ezra
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/
namespace JetBackup\Destination\Vendors\S3\Client;

use Exception;
use JetBackup\Destination\Vendors\S3\Client\Exception\ClientException;
use JetBackup\Exception\IOException;
use JetBackup\Log\LogController;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileChunkIterator;
use JetBackup\Web\File\FileException;
use JetBackup\Web\File\FileStream;
use SimpleXMLElement;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class ClientManager {

	const PUT_CHUNK_SIZE = 1073741824; // 1GB
	const KEEP_ALIVE_TIMEOUT = 60;
	const KEEP_ALIVE_REQUESTS = 100;
	const RETRIES = 0;

	private LogController $_logController;
	private ?string $_key;
	private ?string $_secret;
	private ?string $_region;
	private ?string $_bucket;
	private ?string $_endpoint;
	private ?bool $_verifyssl;
	private ?int $_retries;
	private ?bool $_verifyupload;
	private ?int $_chunk_size;
	private ?int $_keepalive_timeout;
	private ?int $_keepalive_requests;
	private int $_retry_upload;
	private ?Client $_client = null;

	/**
	 * @param LogController $logController
	 * @param string|null $key
	 * @param string|null $secret
	 * @param string|null $region
	 * @param string|null $bucket
	 * @param string|null $endpoint
	 * @param bool|null $verifyssl
	 * @param int|null $chunk_size
	 * @param int|null $keepalive_timeout
	 * @param int|null $keepalive_requests
	 * @param int|null $retries
	 */
	public function __construct(LogController $logController, ?string $key=null, ?string $secret=null, ?string $region=null, ?string $bucket=null, ?string $endpoint=null, ?bool $verifyssl=true, ?int $chunk_size=null, ?int $keepalive_timeout=null, ?int $keepalive_requests=null, ?int $retries=null) {
		$this->setLogController($logController);
		if($key) $this->setAccessKey($key);
		if($secret) $this->setSecretKey($secret);
		if($region) $this->setRegion($region);
		if($bucket) $this->setBucket($bucket);
		if($endpoint) $this->setEndpoint($endpoint);
		if($verifyssl) $this->setVerifySSL($verifyssl);
		if($chunk_size) $this->setChunkSize($chunk_size);
		if($keepalive_timeout) $this->setKeepAliveTimeout($keepalive_timeout);
		if($keepalive_requests) $this->setKeepAliveRequests($keepalive_requests);
		if($retries) $this->setRetries($retries);
		$this->_retry_upload = 0;
	}

	/**
	 * @return Client
	 */
	public function getClient():Client {
		if (!$this->_client) $this->_client = new Client($this->getAccessKey(), $this->getSecretKey(), $this->getRegion(), $this->getBucket(), $this->getEndpoint(), $this->getVerifySSL(), $this->getKeepAliveTimeout(), $this->getKeepAliveRequests());
		return $this->_client;
	}

	/**
	 * @return LogController
	 */
	public function getLogController():LogController { return $this->_logController; }

	/**
	 * @param LogController $logController
	 *
	 * @return void
	 */
	public function setLogController(LogController $logController):void { $this->_logController = $logController; }

	/**
	 * @return string|null
	 */
	public function getAccessKey():?string { return $this->_key; }

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public function setAccessKey(string $key):void { $this->_key = $key; }

	/**
	 * @return string|null
	 */
	public function getSecretKey():?string { return $this->_secret; }

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public function setSecretKey(string $key):void { $this->_secret = $key; }

	/**
	 * @return string|null
	 */
	public function getRegion():?string { return $this->_region; }

	/**
	 * @param string $region
	 *
	 * @return void
	 */
	public function setRegion(string $region):void { $this->_region = $region; }

	/**
	 * @return string|null
	 */
	public function getBucket():?string { return $this->_bucket; }

	/**
	 * @param string $bucket
	 *
	 * @return void
	 */
	public function setBucket(string $bucket):void { $this->_bucket = $bucket; }

	/**
	 * @return string|null
	 */
	public function getEndpoint():?string { return $this->_endpoint; }

	/**
	 * @param string $endpoint
	 *
	 * @return void
	 */
	public function setEndpoint(string $endpoint):void { $this->_endpoint = $endpoint; }

	/**
	 * @return bool
	 */
	public function getVerifySSL():bool { return !!$this->_verifyssl; }

	/**
	 * @param bool $verifyssl
	 *
	 * @return void
	 */
	public function setVerifySSL(bool $verifyssl):void { $this->_verifyssl = !!$verifyssl; }

	/**
	 * @return int
	 */	
	public function getChunkSize():int { return $this->_chunk_size ?: self::PUT_CHUNK_SIZE; }

	/**
	 * @param int $size
	 *
	 * @return void
	 */
	public function setChunkSize(int $size):void { $this->_chunk_size = $size; }

	/**
	 * @return int
	 */
	public function getKeepAliveTimeout():int { return $this->_keepalive_timeout?: self::KEEP_ALIVE_TIMEOUT; }

	/**
	 * @param int $timeout
	 *
	 * @return void
	 */
	public function setKeepAliveTimeout(int $timeout):void { $this->_keepalive_timeout = $timeout; }

	/**
	 * @return int
	 */
	public function getKeepAliveRequests():int { return $this->_keepalive_requests?: self::KEEP_ALIVE_REQUESTS; }

	/**
	 * @param int $queries
	 *
	 * @return void
	 */
	public function setKeepAliveRequests(int $queries):void { $this->_keepalive_requests = $queries; }

	/**
	 * @return int
	 */
	public function getRetries():int { return $this->_retries?: self::RETRIES; }

	/**
	 * @param int $retries
	 *
	 * @return void
	 */
	public function setRetries(int $retries):void { $this->_retries = $retries; }

	/**
	 * @return ClientRetry
	 */
	public static function client():ClientRetry { return new ClientRetry(); }

	/**
	 * @param string $prefix A string used to group keys. When specified, the response will only contain objects with keys beginning with the string.
	 * @param int $limit The maximum number of objects to return. Defaults to 1,000.
	 * @param string $token The key (object name) to start with when listing objects. For use with pagination (e.g. when then number of objects in the result exceeds the specified max-keys).
	 * @param string $delimiter A single character used to group keys. When specified, the response will only contain keys up to its first occurrence. (E.g. Using a slash as the delimiter can allow you to list keys as if they were folders, especially in combination with a prefix.)
	 *
	 * @return ListObjects
	 * @throws ClientException
	 */	
	public function listObjects(string $prefix='', int $limit=0, string $token='', string $delimiter='/'):ListObjects {

		// https://docs.aws.amazon.com/AmazonS3/latest/API/API_ListObjectsV2.html
		//encoding-type=EncodingType&fetch-owner=FetchOwner&max-keys=MaxKeys&start-after=StartAfter

		$params = [ 'list-type' => 2 ];
		if($delimiter) $params['delimiter'] = $delimiter;
		if($token) $params['continuation-token'] = $token;
		if($limit) $params['max-keys'] = $limit;
		if($prefix) {
			if(!str_ends_with($prefix, '/')) $prefix .= '/';
			$params['prefix'] = $prefix;
		}

		$listObjects = new ListObjects();

		$result = self::client()->func('get')->args('/', $params)->exec($this);
		$output = [];

		// NextContinuationToken is sent when isTruncated is true, which means there are more keys in the bucket that can be listed.
		// The next list requests to Amazon S3 can be continued with this NextContinuationToken.
		if(isset($result->Body->IsTruncated)) $listObjects->setIsTruncated($result->Body->IsTruncated);
		if(isset($result->Body->NextContinuationToken)) $listObjects->setNextContinuationToken($result->Body->NextContinuationToken);

		foreach($result->Body->CommonPrefixes as $objectData) {
			$key = (string) $objectData->Prefix;
			$object = new ObjectData();
			$object->setKey($key);
			$output[] = $object;
		}

		foreach($result->Body->Contents as $objectData) {
			if($prefix == (string) $objectData->Key) continue;
			$object = new ObjectData();
			$object->setKey($objectData->Key);
			//$object->setEtag($objectData->ETag);
			$object->setSize(intval($objectData->Size));
			//$object->setType($objectData->Type);
			$object->setMtime($objectData->{"LastModified"} ? strtotime($objectData->{"LastModified"}) : 0);
			$output[] = $object;
		}
		
		$listObjects->setObjectsList($output);

		return $listObjects;
	}

	/**
	 * @return string|null
	 * @throws ClientException
	 */
	public function getBucketRegion():?string {
		$result = self::client()->func('get')->args('', [ 'location' => 1 ])->exec($this);
		if(!isset($result->Body->{0})) return null;
		return $result->Body->{0};
	}

	/**
	 * @param string $key
	 * @param string|null $destination
	 * @param int $start
	 * @param int $end
	 *
	 * @return ObjectData
	 * @throws ClientException
	 */
	public function getObject(string $key, ?string $destination=null, int $start=0, int $end=0):ObjectData {
		if($destination) {
			if($start || $end) $result = self::client()->func('getObjectRange')->args($key, $destination, $start, $end)->exec($this);
			else $result = self::client()->func('getObject')->args($key, $destination)->exec($this);
		} else $result = self::client()->func('head')->args($key)->exec($this);
		$object = new ObjectData();
		$object->setKey($key);
		//$object->setEtag($result->Headers->{"etag"});
		$object->setSize(intval($result->Headers->{"content-length"}));
		//$object->setType($result->Headers->{"x-rgw-object-type"});
		$object->setMtime($result->Headers->{"last-modified"} ? strtotime($result->Headers->{"last-modified"}) : 0);
		return $object;
	}
	
	/**
	 * @param string $object
	 *
	 * @return void
	 * @throws ClientException
	 */
	public function deleteObject(string $object):void {
		self::client()->func('delete')->args($object)->exec($this);
	}

	/**
	 * @param string $directory
	 *
	 * @return void
	 * @throws ClientException
	 */
	public function createObject(string $directory):void {
		self::client()->func('putString')->args('', $directory)->exec($this);
	}

	/**
	 * @param string $destination
	 *
	 * @return string
	 * @throws ClientException
	 */
	public function createUploadID(string $destination):string {
		$result = self::client()->func('post')->args($destination, ['uploads' => 1])->exec($this);
		return (string) $result->Body->UploadId;
	}

	/**
	 * @param string $destination
	 * @param string $upload_id
	 *
	 * @return object|mixed
	 * @throws ClientException
	 */
	public function listUploadParts(string $destination, string $upload_id):object {
		return self::client()->func('get')->args($destination, ['uploadId' => $upload_id])->exec($this);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 * @throws ClientException
	 */
	public function putObject(string $source, string $destination):void {
		if(!file_exists($source) || !is_file($source)) throw new ClientException("Invalid source provided");

		try {
			$file = new FileStream($source);
		} catch(FileException $e) {
			throw new ClientException($e->getMessage(), $e->getCode(), $e);
		}

		try {
			$chunk = new FileChunk($file, $this->getChunkSize());
			$this->getLogController()->logDebug('[_putObjectSingle]');
			$this->getLogController()->logDebug('File: ' . $file->getFile());
			$this->getLogController()->logDebug('ChunkSize: ' . $chunk->getSize());
		} catch(IOException $e) {
			throw new ClientException($e->getMessage(), $e->getCode(), $e);
		}

		self::client()
		    ->func('putChunk')
		    ->args($chunk, $destination)
		    ->retryCallback(function() use ($chunk) {
			    // rewind the chunk on each retry, we need to send the data from the strat
			    $chunk->rewind();
		    })
		    ->exec($this);
	}

	/**
	 * @throws ClientException
	 */
	public function putChunk(FileChunk $chunk, string $destination, string $upload_id, int $part_number) {
		return self::client()->func('putChunk')
             ->args($chunk, $destination, $upload_id, $part_number)
             ->exec($this);
	}

	/**
	 * @throws ClientException
	 */
	public function closeChunkedUpload(string $destination, string $upload_id, string $document) {
		self::client()
		    ->func('post')
		    ->args('/' . $destination, [ 'uploadId' => $upload_id ], $document, 'application/xml')
		    ->exec($this);
	}

	public function __destruct() {
		if($this->_client) $this->_client->close();
	}
}