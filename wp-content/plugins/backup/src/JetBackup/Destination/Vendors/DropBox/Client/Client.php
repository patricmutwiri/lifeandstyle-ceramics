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
namespace JetBackup\Destination\Vendors\DropBox\Client;

use JetBackup\Exception\HttpRequestException;
use JetBackup\Log\LogController;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileDownload;
use JetBackup\Web\JetHttp;
use stdClass;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class Client {

	const DEFAULT_LIST_RECORDS = 1000;
	const REDIRECT_URI = 'https://auth.jetbackup.com/dropbox/';

	const DOMAIN = "dropboxapi.com";
	const API_URL = "https://api." . self::DOMAIN . "/2";
	const CONTENT_URL = "https://content." . self::DOMAIN . "/2";
	const AUTH_TOKEN_URL = "https://api." . self::DOMAIN . "/oauth2/token";
	const CLIENT_ID = 'zkkk6t8uhu9umtk';
	const CLIENT_SECRET = '8c067xihc42o47f';

	private ?JetHttp $_http=null;
	private bool $_verifyssl;
	private string $_access_token='';
	private string $_refresh_token='';
	private string $_auth_code='';
	private string $_client_id='';
	private string $_client_secret='';

	private string $_uri='';
	private array $_params=[];
	private $_body=false;
	private string $_destination='';
	private ?FileChunk $_fileUpload=null;
	private array $_headers=[];
	private bool $_no_exception=false;
	private LogController $_log_controller;

	/**
	 * @param bool $verifyssl
	 */
	public function __construct(bool $verifyssl=true) {
		$this->_verifyssl = !!$verifyssl;
	}

	public function getLogController():LogController { return $this->_log_controller; }

	public function setLogController(LogController $logController):void {
		$this->_log_controller = $logController ?: new LogController();
	}

	/**
	 * @return void
	 */
	private function _reset():void {
		$this->_fileUpload = null;
		$this->_body = false;
		$this->_no_exception = false;

		$this->_uri = $this->_destination = '';
		$this->_headers = $this->_params = [];
	}

	/**
	 * @return string
	 */
	public function getClientId():string { return $this->_client_id ?: self::CLIENT_ID; }

	/**
	 * @param string $id
	 *
	 * @return void
	 */
	public function setClientId(string $id):void { $this->_client_id = $id; }

	/**
	 * @return string
	 */
	public function getClientSecret():string { return $this->_client_secret ?: self::CLIENT_SECRET; }

	/**
	 * @param string $secret
	 *
	 * @return void
	 */
	public function setClientSecret(string $secret):void { $this->_client_secret = $secret; }

	/**
	 * @return array
	 */
	public function getHeaders():array { return $this->_headers; }

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function addHeader(string $key, string $value):void { $this->_headers[$key] = $value; }

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public function getHeader(string $key):string { return $this->_headers[$key]; }

	/**
	 * @return string
	 */
	public function getURI():string { return $this->_uri; }

	/**
	 * @param string $uri
	 *
	 * @return void
	 */
	public function setURI(string $uri):void { $this->_uri = $uri; }

	/**
	 * @return string|false
	 */
	public function getBody() { return $this->_body; }

	/**
	 * @param string|false $body
	 *
	 * @return void
	 */
	public function setBody($body):void { $this->_body = $body; }

	/**
	 * @return string
	 */
	public function getDestination():string { return $this->_destination; }

	/**
	 * @param string $destination
	 *
	 * @return void
	 */
	public function setDestination(string $destination):void { $this->_destination = $destination; }

	/**
	 * @return string
	 */
	public function getAccessToken():string { return $this->_access_token; }

	/**
	 * @param string $access_token
	 *
	 * @return void
	 */
	public function setAccessToken(string $access_token):void { $this->_access_token = $access_token; }

	/**
	 * @return string
	 */
	public function getRefreshToken():string { return $this->_refresh_token; }

	/**
	 * @param string $refresh_token
	 *
	 * @return void
	 */
	public function setRefreshToken(string $refresh_token):void { $this->_refresh_token = $refresh_token; }

	/**
	 * @return string
	 */
	public function getAuthorizationCode():string { return $this->_auth_code; }

	/**
	 * @param string $code
	 *
	 * @return void
	 */
	public function setAuthorizationCode(string $code):void { $this->_auth_code = $code; }

	/**
	 * @param bool $no_exception
	 *
	 * @return void
	 */
	public function setNoException(bool $no_exception):void { $this->_no_exception = $no_exception; }

	/**
	 * @return bool
	 */
	public function isNoException():bool { return $this->_no_exception; }

	/**
	 * @return FileChunk|null
	 */
	public function getFileChunk():?FileChunk { return $this->_fileUpload; }

	/**
	 * @param FileChunk $fileUpload
	 *
	 * @return void
	 */
	public function setFileChunk(FileChunk $fileUpload):void { $this->_fileUpload = $fileUpload; }

	/**
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function fetchToken(): stdClass {
		$this->_reset();
		$this->setURI(self::AUTH_TOKEN_URL);

		$params = [];
		$params['client_id'] = $this->getClientId();
		$params['client_secret'] = $this->getClientSecret();

		if($this->getAuthorizationCode()) {
			$params['redirect_uri'] = self::REDIRECT_URI;
			$params['code'] = $this->getAuthorizationCode();
			$params['grant_type'] = 'authorization_code';
		} else {
			$params['refresh_token'] = $this->getRefreshToken();
			$params['grant_type'] = 'refresh_token';
		}

		$this->setBody(http_build_query($params, '', '&'));
		
		return $this->_execute();
	}

	/**
	 * @param string $destination
	 * @param string $content
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function upload(string $destination, string $content):stdClass {
		$this->_reset();
		$this->setURI(self::CONTENT_URL . '/files/upload');
		$this->setBody($content);
		$this->addHeader("Dropbox-API-Arg", json_encode([ 'path' => $destination, 'autorename' => false, 'mode' => 'overwrite', 'mute' => true ]));
		$this->addHeader("Content-Type", 'application/octet-stream');
		return $this->_execute();
	}

	/**
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getSpaceUsage():stdClass {
		$this->_reset();
		$this->setURI(self::API_URL . '/users/get_space_usage');
		$this->addHeader('Content-Type', '');
		return $this->_execute();
	}

	/**
	 * @param string $path
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getObjectMetadata(string $path):stdClass {
		$this->_reset();
		$this->setURI(self::API_URL . '/files/get_metadata');
		$this->setBody(json_encode([ 'path' => $path ]));
		$this->addHeader('Content-Type', 'application/json; charset=utf-8');
		return $this->_execute();
	}

	/**
	 * There is no official way to fetch the offset of partially upload using dropbox
	 * When resuming an upload, we are simulating an error (bad chunk size) and dropbox returns the offset
	 * @param string $session_id
	 *
	 * @return int
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getUploadSessionOffset(string $session_id):int {
		$this->_reset();
		$this->setURI(self::CONTENT_URL . '/files/upload_session/append_v2');
		$this->addHeader("Dropbox-API-Arg", json_encode([ 'close' => false, 'cursor' => [ 'offset' => 0, 'session_id' => $session_id ] ]));
		$this->addHeader("Content-Type", "application/octet-stream");
		$this->addHeader("Content-Length", 0);
		$this->setNoException(true);
		$response = $this->_execute();
		return isset($response->Body->error->correct_offset) ? (int) $response->Body->error->correct_offset : 0;
	}

	/**
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function startUploadSession():stdClass {
		$this->_reset();
		$this->setURI(self::CONTENT_URL . '/files/upload_session/start');
		$this->addHeader("Dropbox-API-Arg", json_encode([ 'close' => false ]));
		$this->addHeader('Content-Type', 'application/octet-stream');
		return $this->_execute();
	}

	/**
	 * @param string $session_id
	 * @param int $offset
	 * @param string $path
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function finishUploadSession(string $session_id, int $offset, string $path):stdClass {
		$this->_reset();
		$this->setURI(self::CONTENT_URL . '/files/upload_session/finish');
		$this->addHeader("Dropbox-API-Arg", json_encode([ 'commit' => [ 'autorename' => false, 'path' => $path, 'mode' => 'overwrite', 'mute' => true ], 'cursor' => [ 'offset' => $offset, 'session_id' => $session_id ] ]));
		$this->addHeader('Content-Type', 'application/octet-stream');
		return $this->_execute();
	}

	/**
	 * @param FileChunk $chunk
	 * @param string $session_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function appendUploadSession(FileChunk $chunk, string $session_id):stdClass {
		$this->_reset();
		$this->setURI(self::CONTENT_URL . '/files/upload_session/append_v2');
		$this->setFileChunk($chunk);
		$this->addHeader("Dropbox-API-Arg", json_encode([ 'close' => false, 'cursor' => [ 'offset' => $chunk->getFile()->tell(), 'session_id' => $session_id ] ]));
		$this->addHeader("Content-Type", "application/octet-stream");
		$this->addHeader("Content-Length", $chunk->getSize());
		return $this->_execute();
	}

	/**
	 * @param string $path
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function createFolder(string $path):stdClass {
		$this->_reset();
		$this->setURI(self::API_URL . '/files/create_folder');
		$this->addHeader('Content-Type', 'application/json; charset=utf-8');
		$this->setBody(json_encode([ 'path' => $path, 'autorename' => false ]));
		return $this->_execute();
	}

	/**
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getAccountInfo():stdClass {
		$this->_reset();
		$this->setURI(self::API_URL . '/users/get_current_account');
		$this->addHeader('Content-Type', '');
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
		$this->setURI(self::CONTENT_URL . '/files/download');
		$this->addHeader("Dropbox-API-Arg", json_encode([ 'path' => $source ]));
		$this->addHeader('Content-Type', 'text/plain');
		if($start || $end) $this->addHeader('Range', 'bytes=' . $start . '-' . $end);
		$this->setDestination($destination);
		return $this->_execute();
	}

	/**
	 * @param string $directory
	 * @param bool $recursive
	 * @param int $limit
	 * @param bool $include_deleted
	 * @param bool $include_has_explicit_shared_members
	 * @param bool $include_media_info
	 * @param bool $include_mounted_folders
	 * @param bool $include_non_downloadable_files
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function listFolder(string $directory, bool $recursive=false, int $limit=self::DEFAULT_LIST_RECORDS, bool $include_deleted=false, bool $include_has_explicit_shared_members=false, bool $include_media_info=false, bool $include_mounted_folders=false, bool $include_non_downloadable_files=false):stdClass {
		$this->_reset();
		$this->setURI(self::API_URL . '/files/list_folder');
		$this->addHeader('Content-Type', 'application/json; charset=utf-8');
		$this->setBody(json_encode([
			'path'                                      => $directory,
			'recursive'                                 => $recursive,
			'include_deleted'                           => $include_deleted,
			'include_has_explicit_shared_members'       => $include_has_explicit_shared_members,
			'include_media_info'                        => $include_media_info,
			'include_mounted_folders'                   => $include_mounted_folders,
			'include_non_downloadable_files'            => $include_non_downloadable_files,
			'limit'                                     => $limit,
		]));
		return $this->_execute();
	}




	/**
	 * @param string $cursor
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function listFolderContinue(string $cursor):stdClass {
		$this->_reset();
		$this->setURI(self::API_URL . '/files/list_folder/continue');
		$this->addHeader('Content-Type', 'application/json; charset=utf-8');
		$this->setBody(json_encode([ 'cursor' => $cursor ]));
		return $this->_execute();
	}

	/**
	 * @param string $file
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function delete(string $file):stdClass {
		$this->_reset();
		$this->setURI(self::API_URL . '/files/delete_v2');
		$this->addHeader('Content-Type', 'application/json; charset=utf-8');
		$this->setBody(json_encode([ 'path' => $file ]));
		return $this->_execute();
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
			->setMethod(JetHttp::METHOD_POST);
		
		if(!($chunk = $this->getFileChunk())) $this->_http->setBody($this->getBody());

		if($this->getAccessToken()) $this->addHeader('Authorization', 'Bearer ' . $this->getAccessToken());

		$this->_http->setHeaders($this->getHeaders());

		if(($destination = $this->getDestination())) {
			if(!file_exists(dirname($destination)) || !is_dir(dirname($destination))) throw new ClientException("Destination provided not exists (" . dirname($destination) . ")");
			$fileDownload = new FileDownload($destination);
			$response = $this->_http->download($url, $fileDownload);
		} else {
			$response = $chunk ? $this->_http->uploadChunk($url, $chunk, true) : $this->_http->exec($url);
		}
		
		$output = new stdClass();
		$output->Headers = $response->getHeaders()->getHeaders();
		$output->Body = json_decode(trim($response->getBody()));
		if($output->Body === false) $output->Body = $response->getBody();

		if($this->isNoException()) return $output;

		if(isset($output->Body->error) && $output->Body->error) {
			$message = $output->Body->error_description ?? ($output->Body->error_summary ?? 'Unknown Error');
			throw new ClientException($message, $response->getHeaders()->getCode());
		}

		if($response->getHeaders()->getCode() < 200 || $response->getHeaders()->getCode() > 302) {
			$message = $response->getHeaders()->getMessage();
			if(isset($output->Body->error->message)) $message = $output->Body->error->message;
			if(isset($output->Body->error_description)) $message = $output->Body->error_description;
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