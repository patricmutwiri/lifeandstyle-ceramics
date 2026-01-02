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
namespace JetBackup\Destination\Vendors\pCloud\Client;

use JetBackup\Data\ArrayData;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Log\LogController;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileDownload;
use JetBackup\Web\File\FileStream;
use JetBackup\Web\JetHttp;
use stdClass;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class Client extends ArrayData {

	const CHUNK_SIZE = 1024 * 1024;
	const MIMITYPE_DIR = 'folder';
	const MIMITYPE_FILE = 'file';

	const CODE_FILE_NOT_FOUND = 2055;
	const CODE_DIR_NOT_FOUND = 2005;
	const CODE_DIR_EXISTS = 2004;
	
	const METHOD_POST = JetHttp::METHOD_POST;
	const METHOD_GET = JetHttp::METHOD_GET;
	
	const CLIENT_ID = 'nvbJ2u82GTh';
	const CLIENT_SECRET = 'wn4rQOaJN3V14N3N8mKFz8fzRPvX';

	private ?JetHttp $_http=null;
	private ?FileChunk $_fileChunk=null;

	private LogController $_log_controller;
	private ?int $_chunk_size;
	public function getChunkSize():int { return $this->_chunk_size ?: self::CHUNK_SIZE; }

	/**
	 * @param int $size
	 *
	 * @return void
	 */
	public function setChunkSize(int $size):void { $this->_chunk_size = $size; }

	public function setLogController(LogController $logController):void {
		$this->_log_controller = $logController ?: new LogController();
	}

	public function getLogController():LogController { return $this->_log_controller; }

	private function _reset():void {
		$this->_fileChunk = null;
		$this->setBody(false);
		$this->setMethod(self::METHOD_POST);
		$this->setURI('');
		$this->setDestination('');
		$this->setHeaders([]);
	}

	/**
	 * @return array
	 */
	public function getHeaders():array { return $this->get('headers', []); }

	/**
	 * @param array $headers
	 *
	 * @return void
	 */
	public function setHeaders(array $headers):void { $this->set('headers', $headers); }

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function addHeader(string $key, string $value):void {
		$headers = $this->getHeaders();
		$headers[$key] = $value;
		$this->setHeaders($headers);
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function getHeader(string $key):string {
		$headers = $this->getHeaders();
		return $headers[$key] ?? '';
	}

	/**
	 * @return string
	 */
	public function getURI():string { return $this->get('uri'); }

	/**
	 * @param string $uri
	 *
	 * @return void
	 */
	public function setURI(string $uri):void { $this->set('uri', $uri); }

	/**
	 * @return string|false
	 */
	public function getBody() { return $this->get('body'); }

	/**
	 * @param mixed $body
	 *
	 * @return void
	 */
	public function setBody($body):void { $this->set('body', $body); }

	/**
	 * @return int
	 */
	public function getMethod():int { return $this->get('method', self::METHOD_POST); }

	/**
	 * @param int $method
	 *
	 * @return void
	 */
	public function setMethod(int $method):void { $this->set('method', $method); }

	/**
	 * @return string
	 */
	public function getDestination():string { return $this->get('destination'); }

	/**
	 * @param string $destination
	 *
	 * @return void
	 */
	public function setDestination(string $destination):void { $this->set('destination', $destination); }

	/**
	 * @return string
	 */
	public function getAccessToken():string { return $this->get('access_token'); }

	/**
	 * @param string $access_token
	 *
	 * @return void
	 */
	public function setAccessToken(string $access_token):void { $this->set('access_token', $access_token); }

	/**
	 * @return string
	 */
	public function getAuthorizationCode():string { return $this->get('auth_code'); }

	/**
	 * @param string $code
	 *
	 * @return void
	 */
	public function setAuthorizationCode(string $code):void { $this->set('auth_code', $code); }

	/**
	 * @param int $id
	 *
	 * @return void
	 */
	public function setLocationId(int $id) { $this->set('location_id', $id); }

	/**
	 * @return FileChunk|null
	 */
	public function getFileChunk():?FileChunk { return $this->_fileChunk; }

	/**
	 * @param FileChunk $fileUpload
	 *
	 * @return void
	 */
	public function setFileChunk(FileChunk $fileUpload):void { $this->_fileChunk = $fileUpload; }

	/**
	 * @param string $url
	 *
	 * @return void
	 */
	public function setAPIUrl(string $url) { $this->set('api_url', $url); }

	/**
	 * @return string
	 */
	public function getAPIUrl():string { return $this->get('api_url'); }

	/**
	 * @param string $query
	 *
	 * @return string
	 */
	public function apiurl(string $query='', $params=[]):string { return 'https://' . $this->getAPIUrl() . '/' . $query . ($params ? '?' . http_build_query($params) : ''); }

	/**
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function fetchToken(): stdClass {
		$this->_reset();

		$params = [];
		$params['client_id'] = self::CLIENT_ID;
		$params['client_secret'] = self::CLIENT_SECRET;
		$params['code'] = $this->getAuthorizationCode();
		
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('oauth2_token', $params));

		return $this->_execute()->Body;
	}

	public function getAccountInfo():stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('userinfo'));
		return $this->_execute()->Body;
	}

	/**
	 * @param string $session_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getUploadSession(string $session_id):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('upload_info', [ 'uploadid' => $session_id ]));
		return $this->_execute()->Body;
	}

	/**
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function startUploadSession():stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('upload_create'));
		return $this->_execute()->Body;
	}

	/**
	 * @param string $session_id
	 * @param FileChunk $chunk
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function appendUploadSession(string $session_id, FileChunk $chunk):stdClass {
		$this->_reset();
		$this->setURI($this->apiurl('upload_write', [ 'uploadid' => $session_id, 'uploadoffset' => $chunk->getFile()->tell() ]));
		$this->setFileChunk($chunk);
		return $this->_execute()->Body;
	}

	/**
	 * @param string $session_id
	 * @param string $destination
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function commitUploadSession(string $session_id, string $destination):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('upload_save', [ 'uploadid' => $session_id, 'path' => $destination ]));
		return $this->_execute();
	}

	/**
	 * @param FileStream $file
	 * @param string $destination
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 * @throws IOException
	 */
		public function upload(FileStream $file, string $destination):stdClass {
		$this->_reset();
		$this->setURI($this->apiurl('uploadfile', [ 'path' => dirname($destination), 'filename' => basename($destination) ]));
		$this->setFileChunk(new FileChunk($file, $file->getSize()));
		$this->getlogController()->logDebug("[Client upload] Uploading file to $destination");
		return $this->_execute();
	}

	/**
	 * @param string $destination
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function createEmptyFile(string $destination): stdClass {

		$this->_reset();

		try {
			$this->getFileInfo($destination);
			$this->getlogController()->logDebug("[Client createEmptyFile] File already exists: $destination. Skipping creation.");
			return new stdClass(); // Return an empty object to indicate no action needed
		} catch (ClientException $e) {
			if ($e->getCode() !== self::CODE_FILE_NOT_FOUND) {
				throw $e; // If it's another error, propagate it
			}
		}

		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('uploadfile', [
			'path'     => dirname($destination),
			'filename' => basename($destination)
		]));

		$this->setBody(""); // Empty content
		$this->getlogController()->logDebug("[Client createEmptyFile] Creating an empty file at $destination");

		return $this->_execute();
	}



	/**
	 * @param string $source
	 * @param string $destination
	 * @param int $start
	 * @param int $end
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function download(string $source, string $destination, int $start=0, int $end=0):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('getfilelink', [ 'path' => $source ]));
		$result = $this->_execute()->Body;

		$response = null;
		$exception = null;
		$this->getlogController()->logDebug("[Client download] Downloading $source -> $destination");

		// Try to download from all hosts (in case of failure)
		foreach($result->hosts as $host) {
			$this->_reset();
			$this->setURI('https://' . $host . $result->path);
			if($start || $end) $this->addHeader('Range', 'bytes=' . $start . '-' . $end);
			$this->setDestination($destination);
			
			try {
				$response = $this->_execute();
				break;
			} catch(HttpRequestException $e) {
				$exception = $e;
			}
		}
		
		if($exception) throw $exception;
		return $response;
	}

	/**
	 * @param string $file
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getFileInfo(string $file):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('stat', [ 'path' => $file ]));
		$this->getlogController()->logDebug("[Client getFileInfo] Request file info for $file");
		return $this->_execute()->Body;
	}

	/**
	 * @param string $file
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function deleteFile(string $file):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('deletefile', [ 'path' => $file ]));
		$this->getlogController()->logDebug("[Client deleteFile] Request delete file for $file");
		return $this->_execute()->Body;
	}

	/**
	 * @param string $directory
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getFolderInfo(string $directory):stdClass {

		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('listfolder', [ 'path' => $directory ]));
		$this->getlogController()->logDebug("[Client getFolderInfo] Request folder info for $directory");
		return $this->_execute()->Body;
	}

	/**
	 * @param string $directory
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function deleteFolder(string $directory):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('deletefolderrecursive', [ 'path' => $directory ]));
		$this->getlogController()->logDebug("[Client deleteFolder] Request delete folder for $directory");
		return $this->_execute()->Body;
	}

	/**
	 * @param string $directory
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function createFolder(string $directory):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('createfolder', [ 'path' => $directory ]));
		$this->getlogController()->logDebug("[Client createFolder] Request create folder for $directory");
		return $this->_execute()->Body;
	}

	/**
	 * @param string $directory
	 *
	 * @return File[]
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function listFolder(string $directory):array {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($this->apiurl('listfolder', [ 'path' => $directory ]));
		$this->getlogController()->logDebug("[Client listFolder] Request list folder for $directory");
		$response = $this->_execute();

		$output = [];

		foreach($response->Body->metadata->contents as $details) {
			$file = new File();
			$file->setId($details->id);
			$file->setName($details->name);
			$file->setSize($details->size ?? 0);
			$file->setCreationTime(strtotime($details->created));
			$file->setModificationTime(strtotime($details->modified));
			$file->setMimeType($details->isfolder ? self::MIMITYPE_DIR : self::MIMITYPE_FILE);
			$file->setChecksum( $details->hash ?? '');

			$output[] = $file;
		}

		return $output;
	}

	/**
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	private function _execute():stdClass {

		$url = $this->getURI();

		if(!$this->_http) $this->_http = new JetHttp();
		$this->_http->reset();

		$this->_http
			->setReturnTransfer()
			->setFollowLocation()
			->setSSLVerify(1, 2)
			->setConnectionTimeout(30)
			->setTimeout(43200)
			->setLowSpeed(1, 120)
			->setMethod($this->getMethod());

		if(!($chunk = $this->getFileChunk())) $this->_http->setBody($this->getBody());

		if($this->getAccessToken()) $this->addHeader('Authorization', 'Bearer ' . $this->getAccessToken());

		$this->_http->setHeaders($this->getHeaders());

		if(($destination = $this->getDestination())) {

			if(!file_exists(dirname($destination)) || !is_dir(dirname($destination)))
				throw new ClientException("Destination provided not exists (" . dirname($destination) . ")");

			$fileDownload = new FileDownload($destination);
			$response = $this->_http->download($url, $fileDownload);

		} else {
			if($chunk) $response = $this->_http->uploadChunk($url, $chunk);
			else $response = $this->_http->exec($url);
		}

		$output = new stdClass();
		$output->Headers = $response->getHeaders()->getHeaders();
		$output->Body = json_decode(trim($response->getBody()));

		if($output->Body === false || $output->Body === null) $output->Body = strip_tags($response->getBody());

		if(isset($output->Body->result) && (int) $output->Body->result)
			throw new ClientException(($output->Body->error ?? 'Unknown Error'), $output->Body->result);

		return $output;
	}

	/**
	 * @return void
	 */
	public function close():void {
		unset($this->_http);
	}
}