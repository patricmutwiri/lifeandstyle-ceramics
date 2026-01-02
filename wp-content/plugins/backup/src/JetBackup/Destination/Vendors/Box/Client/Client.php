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
namespace JetBackup\Destination\Vendors\Box\Client;

use JetBackup\Data\ArrayData;
use JetBackup\Destination\Vendors\Box\Cache;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Log\LogController;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileDownload;
use JetBackup\Web\File\FileStream;
use JetBackup\Web\JetHttp;
use stdClass;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class Client extends ArrayData {

	const DEFAULT_LIST_RECORDS = 1000;

	const MIMITYPE_DIR = 'folder';
	const MIMITYPE_FILE = 'file';
	
	const METHOD_POST = JetHttp::METHOD_POST;
	const METHOD_GET = JetHttp::METHOD_GET;
	const METHOD_DELETE = JetHttp::METHOD_DELETE;

	const ALGO = 'sha1';

	const ROOT_FOLDER = 0;
	const DOMAIN = 'box.com';
	const AUTH_TOKEN_URL = 'https://www.' . self::DOMAIN . '/api/oauth2/token';
	const API_URL = 'https://api.' . self::DOMAIN . '/2.0';
	const UPLOAD_URL = 'https://upload.' . self::DOMAIN . '/api/2.0';
	const CLIENT_ID = 'wvxkgyw14hdyha0wzzrphmmyd3h50lp4';
	const CLIENT_SECRET = 'wBatFg2CFSJyTEqGA0JtS9AdLXJMOx8L';
	const REDIRECT_URI = 'https://auth.jetbackup.com/box/';
	
	private ?JetHttp $_http=null;
	private bool $_verifyssl;

	private ?FileChunk $_fileChunk=null;
	/**
	 * @var LogController|mixed
	 */
	private LogController $_log_controller;
	private Cache $_cache;
	/**
	 * @param bool $verifyssl
	 */
	public function __construct(bool $verifyssl=true) {
		$this->_verifyssl = !!$verifyssl;
	}

	/**
	 * @return void
	 */
	private function _reset():void {
		$this->_fileChunk = null;
		$this->setBody(false);
		$this->setMethod(self::METHOD_POST);
		$this->setURI('');
		$this->setDestination('');
		$this->setHeaders([]);
	}

	public function setCache(Cache $cache):void {
		$this->_cache = $cache ?: new Cache();
	}

	public function getCache():Cache { return $this->_cache; }

	public function setLogController(LogController $logController):void {
		$this->_log_controller = $logController ?: new LogController();
	}

	public function getLogController():LogController { return $this->_log_controller; }

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
	public function getRefreshToken():string { return $this->get('refresh_token'); }

	/**
	 * @param string $refresh_token
	 *
	 * @return void
	 */
	public function setRefreshToken(string $refresh_token):void { $this->set('refresh_token', $refresh_token); }

	/**
	 * @return string
	 */
	public function getAccessCode():string { return $this->get('access_code'); }

	/**
	 * @param string $code
	 *
	 * @return void
	 */
	public function setAccessCode(string $code):void { $this->set('access_code', $code); }

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
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function fetchToken(): stdClass {
		$this->_reset();
		$this->setURI(self::AUTH_TOKEN_URL);

		$params = [];
		$params['client_id'] = self::CLIENT_ID;
		$params['client_secret'] = self::CLIENT_SECRET;

		if($this->getAccessCode()) {
			$params['redirect_uri'] = self::REDIRECT_URI;
			$params['code'] = $this->getAccessCode();
			$params['grant_type'] = 'authorization_code';
		} else {
			$params['refresh_token'] = $this->getRefreshToken();
			$params['grant_type'] = 'refresh_token';
		}

		$this->setBody(http_build_query($params, '', '&'));
		
		return $this->_execute()->Body;
	}

	/**
	 * @param string $name
	 * @param string $content
	 * @param string $folder_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function upload(string $source, string $destination, string $folder_id=self::ROOT_FOLDER):stdClass {

		$this->_reset();
		$this->setURI(self::UPLOAD_URL . '/files/content');
		$this->addHeader("Content-Type", 'multipart/form-data');
		$this->setBody([ 
			'attributes'    => json_encode([ 'name' => basename($destination), 'parent' => [ 'id' => $folder_id ] ]),
			'file'          => new \CURLFile($source),
		]);

		return $this->_execute()->Body;
	}

	/**
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getAccountInfo():stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI(self::API_URL . '/users/me');
		return $this->_execute()->Body;
	}

	/**
	 * @param string $file_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getFileInfo(string $file_id):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI(self::API_URL . '/files/' . $file_id);
		return $this->_execute()->Body;
	}

	/**
	 * @param string $folder_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getFolderInfo(string $folder_id):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI(self::API_URL . '/folders/' . $folder_id);
		return $this->_execute()->Body;
	}

	/**
	 * @param string $parts_uri
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function listUploadSessionParts(string $session_id):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI(self::UPLOAD_URL . '/files/upload_sessions/' . $session_id . '/parts');
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
		$this->setURI(self::UPLOAD_URL . '/files/upload_sessions/' . $session_id);
		return $this->_execute();
	}

	/**
	 * @param string $name
	 * @param int $size
	 * @param string $folder_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function startUploadSession(string $name, int $size, string $folder_id=self::ROOT_FOLDER):stdClass {
		$this->_reset();
		$this->setURI(self::UPLOAD_URL . '/files/upload_sessions');
		$this->addHeader('Content-Type', 'application/json');
		$this->setBody(json_encode([
			'folder_id'     => $folder_id,
			'file_size'     => $size,
			'file_name'     => $name,
		]));
		return $this->_execute()->Body;
	}

	/**
	 * @param string $session_id
	 * @param FileStream $file
	 * @param array|null $parts
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function commitUploadSession(string $session_id, FileStream $file, array $parts=null):stdClass {

		if(!$parts) $parts = $this->listUploadSessionParts($session_id)->entries;

		$file->rewind();
		
		$this->_reset();
		$this->setURI(self::UPLOAD_URL . '/files/upload_sessions/' . $session_id . '/commit');
		$this->addHeader('Content-Type', 'application/json');
		$this->addHeader('digest', 'sha=' . base64_encode($file->getHash(self::ALGO, true)));
		$this->setBody(json_encode([ 'parts' => $parts ]));

		return $this->_execute();
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
		
		$file = $chunk->getFile();
		$start = $file->tell();
		$end = $start+$chunk->getSize()-1;
		
		$this->_reset();
		$this->setURI( self::UPLOAD_URL . '/files/upload_sessions/' . $session_id);
		$this->setFileChunk($chunk);
		$this->addHeader('digest', 'sha=' . base64_encode($chunk->getHash(self::ALGO, true)));
		$this->addHeader("Content-Range", 'bytes ' . $start . '-' . $end . '/' . $file->getSize());
		$this->addHeader("Content-Type", "application/octet-stream");
		return $this->_execute()->Body;
	}

	/**
	 * @param string $file_id
	 * @param string $destination
	 * @param int $start
	 * @param int $end
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function download(string $file_id, string $destination, int $start=0, int $end=0):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI(self::API_URL . '/files/' . $file_id . '/content');
		if($start || $end) $this->addHeader('Range', 'bytes=' . $start . '-' . $end);
		$this->setDestination($destination);
		return $this->_execute();
	}

	/**
	 * @param int $folder_id
	 * @param int $limit
	 * @param string $marker
	 *
	 * @return ListFiles
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function listFolder(int $folder_id = self::ROOT_FOLDER, int $limit = self::DEFAULT_LIST_RECORDS, string $marker = ''): ListFiles {

		$this->_reset();

		$params = [
			'direction' => 'ASC',
			'sort' => 'name',
			'fields' => 'id,type,name,created_at,modified_at,size,sha1',
			'marker' => $marker,
			'limit' => $limit,
		];

		$this->setMethod(self::METHOD_GET);
		$this->setURI(self::API_URL . '/folders/' . $folder_id . '/items?' . http_build_query($params));
		$this->addHeader('Content-Type', 'application/json');

		$response = $this->_execute();

		$output = new ListFiles();
		foreach ($response->Body->entries as $details) {
			$file = new File();
			$file->setId($details->id);
			$file->setName($details->name);
			$file->setSize($details->size);
			$file->setCreationTime(strtotime($details->created_at));
			$file->setModificationTime(strtotime($details->modified_at));
			$file->setMimeType($details->type);
			$file->setSHA1Checksum($details->sha1 ?? '');
			$output->addFile($file);
		}

		return $output;
	}

	/**
	 * @param string $file_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function deleteFile(string $file_id):stdClass {

		$this->_reset();
		$this->setMethod(self::METHOD_DELETE);
		$this->setURI(self::API_URL . '/files/' . $file_id);

		return $this->_execute();
	}

	/**
	 * @param string $folder_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function deleteFolder(string $folder_id):stdClass {
		$this->_reset();
		$this->setMethod(self::METHOD_DELETE);
		$this->setURI(self::API_URL . '/folders/' . $folder_id);
		return $this->_execute();
	}





	/**
	 * @param string $name
	 * @param string $folder_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function createFolder(string $name, string $folder_id=self::ROOT_FOLDER):stdClass {
		$this->_reset();
		$this->setURI(self::API_URL . '/folders');
		$this->addHeader('Content-Type', 'application/json');
		$this->setBody(json_encode([
			'name'      => $name,
			'parent'    => [ 'id' => $folder_id ],
		]));
		return $this->_execute()->Body;
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
			->setSSLVerify($this->_verifyssl ? 1 : 0, $this->_verifyssl ? 2 : 0)
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
		if($response->getHeaders()->getCode() < 200 || $response->getHeaders()->getCode() > 302) {
			if(isset($output->Body->code)) $message = ($output->Body->message ?? 'Unknown Error') . ' (Code: ' . $output->Body->code . ')';
			else $message = ($output->Body && is_string($output->Body) ? $output->Body : 'Unknown Error') . ' (Status Code: ' . $response->getHeaders()->getCode() . ')';
			throw new ClientException($message, $response->getHeaders()->getCode());
		}

		return $output;
	}

	/**
	 * @return void
	 */
	public function close():void {
		unset($this->_http);
	}
}