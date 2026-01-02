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
namespace JetBackup\Destination\Vendors\GoogleDrive\Client;

use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileChunkIterator;
use JetBackup\Web\File\FileDownload;
use JetBackup\Web\File\FileException;
use JetBackup\Web\File\FileStream;
use JetBackup\Web\JetHttp;
use JetBackup\Web\JetHttpResponse;
use stdClass;

class Client {

	const ROOT_ID = 'root';
	const MIMITYPE_DIR = 'application/vnd.google-apps.folder';

	const CLIENT_ID = '72463712983-r6mekushs9d8ugj4c5f29a84u5g4li4c.apps.googleusercontent.com';
	const CLIENT_SECRET = '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';
	const REDIRECT_URI = 'https://auth.jetbackup.com/google/';

	const API_URL = 'https://www.googleapis.com';
	const OAUTH_URL = 'https://oauth2.googleapis.com';

	const TOKEN_URL = self::OAUTH_URL . '/token';
	const FILES_API_URL = self::API_URL . '/drive/v3/files';
	const UPLOAD_API_URL = self::API_URL . '/upload/drive/v3/files';

	const RESUME_UPLOAD_CODE = 308;
	const UPLOAD_CHUNK_SIZE = 52428800; // 50MB - Chunk size must be in mib and not just any size (dividable by 1024). 

	private array $_accessToken=[];
	private string $_client_id='';
	private string $_client_secret='';
	private JetHttp $_http;
	private ?string $_refresh_token = null;

	/**
	 * 
	 */
	public function __construct() {
		$this->_http = JetHttp::request();
	}
	
	/**
	 * @param array $accessToken
	 *
	 * @return void
	 */
	public function setAccessToken(array $accessToken):void {
		$this->_accessToken = $accessToken;
	}

	/**
	 * @return array
	 */
	public function getAccessToken():array {
		return $this->_accessToken;
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

	public function setRefreshToken(?string $token):void { $this->_refresh_token = $token; }
	public function getRefreshToken():?string {return $this->_refresh_token;}

	/**
	 * @return bool
	 */
	public function isAccessTokenExpired():bool {

		$accessToken = $this->getAccessToken();
		if(!$accessToken) return true;

		$created = 0;
		if (isset($accessToken['created'])) {
			$created = $accessToken['created'];
		} elseif (isset($accessToken['id_token'])) {
			// check the ID token for "iat"
			// signature verification is not required here, as we are just
			// using this for convenience to save a round trip request
			// to the Google API server
			$idToken = $accessToken['id_token'];
			if (substr_count($idToken, '.') == 2) {
				$parts = explode('.', $idToken);
				$payload = json_decode(base64_decode($parts[1]), true);
				if ($payload && isset($payload['iat'])) {
					$created = $payload['iat'];
				}
			}
		}
		if (!isset($accessToken['expires_in'])) {
			// if the token does not have an "expires_in", then it's considered expired
			return true;
		}

		// If the token is set to expire in the next 30 seconds.
		return ($created + ($accessToken['expires_in'] - 30)) < time();

	}

	/**
	 * @return void
	 * @throws ClientException
	 */
	public function fetchAccessTokenWithRefreshToken():void {

		try {
			$this->_http->reset();
			$response = $this->_http
				->setMethod(JetHttp::METHOD_POST)
				->setReturnTransfer()
				->addHeader('Content-Type', 'application/x-www-form-urlencoded')
				->setBody(http_build_query([
					'client_id'         => $this->getClientId(),
					'client_secret'     => $this->getClientSecret(),
					'refresh_token'     => $this->getRefreshToken(),
					'grant_type'        => 'refresh_token',
				]))
				->exec(self::TOKEN_URL);

			self::_checkResponse($response);
			
		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}

		$body = json_decode($response->getBody());
		if(isset($body->error)) throw new ClientException($body->error->message, $body->error->code);

		$accessToken = $this->getAccessToken();
		$accessToken['access_token'] = $body->access_token;
		//$accessToken['created'] = time();
		$this->setAccessToken($accessToken);
	}

	/**
	 * @param string $authCode
	 *
	 * @return array
	 * @throws ClientException
	 */
	public function fetchAccessTokenWithAuthCode(string $authCode):array {

		try {
			$this->_http->reset();
			$response = $this->_http
				->setMethod(JetHttp::METHOD_POST)
				->setReturnTransfer()
				->addHeader('Content-Type', 'application/x-www-form-urlencoded')
				->setBody(http_build_query([
					'code'              => $authCode,
					'client_id'         => $this->getClientId(),
					'client_secret'     => $this->getClientSecret(),
					'redirect_uri'      => self::REDIRECT_URI,
					'grant_type'        => 'authorization_code',
				]))
				->exec(self::TOKEN_URL);

			self::_checkResponse($response);

		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}

		$body = json_decode($response->getBody());
		if(isset($body->error)) throw new ClientException($body->error->message, $body->error->code);

		return (array) $body;
	}
	
	public function getUploadOffset(string $upload_url):int {

		try {
			$this->_http->reset();
			$response = $this->_http
				->addOption(CURLOPT_PUT, 1)
				->setReturnTransfer()
				->setBody('')
				->addHeader('Content-Range', 'bytes */*')  // Request the current upload offset
				->addHeader('Content-Length', 0)  // Add Content-Length: 0 to avoid 411 error
				->exec($upload_url);

			if(
				$response->getHeaders()->getCode() != 308 ||
				!($range = $response->getHeaders()->getHeader('range'))
			) return 0;

			list(,$end) = explode('-', $range);

			return ((int) $end) + 1;
		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}
	}
	
	public function getUploadURL(FileStream $stream, string $destination, string $parent):string {
		
		try {
			
			$this->_http->reset();
			$response = $this->_http
				->setMethod(JetHttp::METHOD_POST)
				->setReturnTransfer()
				->addHeader('Authorization', 'Bearer ' . $this->getAccessToken()['access_token'])
				->addHeader('Content-Type', 'application/json; charset=UTF-8')
				->addHeader('X-Upload-Content-Type', $stream->getMimeType())
				->addHeader('X-Upload-Content-Length', $stream->getSize())
				->setBody(json_encode([
					'name'      => basename($destination),
					'parents'   => [$parent],
				]))
				->exec(self::UPLOAD_API_URL . '?uploadType=resumable&fields=id');

			self::_checkResponse($response);

			$body = json_decode($response->getBody());
			if(isset($body->error)) throw new ClientException($body->error->message, $body->error->code);

			return $response->getHeaders()->getHeader('location');

		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function uploadChunk(FileChunk $chunk, $uploadURL):object {

		$this->_http->reset();

		$response = $this->_http
			->setReturnTransfer()
			->addHeader('Content-Length', $chunk->getSize())
			->addHeader('Content-Range', sprintf("bytes %d-%d/%d", $chunk->getFile()->tell(), $chunk->getFile()->tell() + $chunk->getSize() - 1, $chunk->getFile()->getSize()))
			->addHeader('expect', '')
			->uploadChunk($uploadURL, $chunk);

		if($response->getHeaders()->getCode() != self::RESUME_UPLOAD_CODE) self::_checkResponse($response);
		
		return $response;
	}
	
	/**
	 * @param string $source
	 * @param string $destination
	 * @param string $parent
	 *
	 * @return stdClass
	 * @throws ClientException
	 */
	public function upload(string $source, string $destination, string $parent): stdClass {

		try {

			$stream = new FileStream($source);

			if($stream->getSize() <= self::UPLOAD_CHUNK_SIZE) {
				// Upload small file
				$details = json_encode([
					'name'      => basename($destination),
					'parents'   => [$parent],
				]);
				
				$this->_http->reset();
				$response = $this->_http
					->setReturnTransfer()
					->addHeader('Authorization', 'Bearer ' . $this->getAccessToken()['access_token'])
					->uploadString(self::UPLOAD_API_URL . '?uploadType=multipart&fields=id', $stream, $details);

			} else {
				// Upload large file
				$uploadURL = $this->getUploadURL($stream, $destination, $parent);
				
				$iterator = new FileChunkIterator($stream, self::UPLOAD_CHUNK_SIZE);
				if(!$iterator->hasNext()) throw new ClientException("Unable to get file chunks");

				while($iterator->hasNext()) {

					try {
						$chunk = $iterator->next();
					} catch(IOException $e) {
						throw new ClientException($e->getMessage(), $e->getCode(), $e);
					}

					$response = $this->uploadChunk($chunk, $stream, $uploadURL);
				}
			}
			

			self::_checkResponse($response);

			$output = json_decode($response->getBody());
			if(isset($output->error)) throw new ClientException($output->error->message, $output->error->code);

		} catch(HttpRequestException|FileException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}

		return $output;
	}

	/**
	 * @param string $file_id
	 * @param string $destination
	 * @param int $start
	 * @param int $end
	 *
	 * @return int
	 * @throws ClientException
	 */
	public function downloadChunk(string $file_id, string $destination, int $start, int $end):int {

		try {
			$download = new FileDownload($destination);

			$this->_http->reset();
			$response = $this->_http
				->setReturnTransfer()
				->addHeader('Authorization', 'Bearer ' . $this->getAccessToken()['access_token'])
				->addHeader('Range', 'bytes=' . $start . '-' . $end)
				->download(self::FILES_API_URL . '/' . $file_id . '?alt=media', $download);

			self::_checkResponse($response);

			return $response->getHeaders()->getHeader('content-length');
		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $file_id
	 * @param string $destination
	 *
	 * @return void
	 * @throws ClientException
	 */
	public function download(string $file_id, string $destination):void {

		try {
			$download = new FileDownload($destination);

			$this->_http->reset();
			$response = $this->_http
				->setReturnTransfer()
				->addHeader('Authorization', 'Bearer ' . $this->getAccessToken()['access_token'])
				->download(self::FILES_API_URL . '/' . $file_id . '?alt=media', $download);

			self::_checkResponse($response);
			
		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $file_id
	 *
	 * @return void
	 * @throws ClientException
	 */
	public function deleteFile(string $file_id):void {

		try {
			$this->_http->reset();
			$response = $this->_http
				->setMethod(JetHttp::METHOD_DELETE)
				->setReturnTransfer()
				->addHeader('Authorization', 'Bearer ' . $this->getAccessToken()['access_token'])
				->exec(self::FILES_API_URL . '/' . $file_id);

			self::_checkResponse($response);

			$body = json_decode($response->getBody());
			if(isset($body->error)) throw new ClientException($body->error->message, $body->error->code);

		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $file_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 */
	public function getFile(string $file_id):stdClass {

		try {
			$this->_http->reset();
			$response = $this->_http
				->setReturnTransfer()
				->addHeader('Authorization', 'Bearer ' . $this->getAccessToken()['access_token'])
				->exec(self::FILES_API_URL . '/' . $file_id . '?fields=id,name,size,mimeType,modifiedTime,parents');

			self::_checkResponse($response);

			$body = json_decode($response->getBody());
			if(isset($body->error)) throw new ClientException($body->error->message, $body->error->code);

			return $body;
		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param array $params
	 *
	 * @return ListFiles
	 * @throws ClientException
	 */
	public function listFiles(array $params=[]):ListFiles {
		
		try {
			$this->_http->reset();
			$response = $this->_http
				->setReturnTransfer()
				->addHeader('Authorization', 'Bearer ' . $this->getAccessToken()['access_token'])
				->exec(self::FILES_API_URL . (sizeof($params) ? '?' . http_build_query($params) : ''));

			self::_checkResponse($response);

			$body = json_decode($response->getBody());
			if(isset($body->error)) throw new ClientException($body->error->message, $body->error->code);

			$listFiles = new ListFiles();
			$listFiles->setNextPageToken($body->nextPageToken ?? null);
			
			foreach($body->files as $file_details) {
				$file = new File();
				$file->setId($file_details->id);
				$file->setName($file_details->name);
				$file->setSize($file_details->size ?? 0);
				$file->setMimeType($file_details->mimeType);
				$file->setModificationTime(strtotime($file_details->modifiedTime));
				$file->setCreationTime(strtotime($file_details->createdTime));
				$file->setMD5Checksum($file_details->md5Checksum ?? '');
				$file->setSHA1Checksum($file_details->sha1Checksum ?? '');
				$file->setSHA256Checksum($file_details->sha256Checksum ?? '');

				$listFiles->addFile($file);
			}
			
			return $listFiles;
		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $directory
	 * @param string $parent_id
	 *
	 * @return stdClass
	 * @throws ClientException
	 */
	public function createDir(string $directory, string $parent_id):stdClass {
		
		try {
			$this->_http->reset();
			$response = $this->_http
				->setReturnTransfer()
				->addHeader('Authorization', 'Bearer ' . $this->getAccessToken()['access_token'])
				->addHeader('Content-Type', 'application/json')
				->setBody(json_encode([
					'name'      => $directory,
					'mimeType'  => self::MIMITYPE_DIR,
					'parents'   => $parent_id == self::ROOT_ID ? [] : [$parent_id],
				]))
				->exec(self::FILES_API_URL . '?fields=id');

			self::_checkResponse($response);

			$body = json_decode($response->getBody());
			if(isset($body->error)) throw new ClientException($body->error->message, $body->error->code);

			return $body;
		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * @return stdClass
	 * @throws ClientException
	 */
	public function about():stdClass {

		try {
			$this->_http->reset();
			$response = $this->_http
				->setReturnTransfer()
				->addHeader('Authorization', 'Bearer ' . $this->getAccessToken()['access_token'])
				->exec(self::API_URL . '/drive/v2/about');

			self::_checkResponse($response);

			$body = json_decode($response->getBody());
			if(isset($body->error)) throw new ClientException($body->error->message, $body->error->code);

		} catch(HttpRequestException $e) {
			throw new ClientException($e->getMessage(), $e->getCode());
		}

		return $body;
	}
	
	/**
	 * @param JetHttpResponse $response
	 *
	 * @return void
	 * @throws ClientException
	 */
	private static function _checkResponse(JetHttpResponse $response):void {
		
		if(
			$response->getHeaders()->getCode() < 200 ||
			$response->getHeaders()->getCode() > 299
		) {

			$body = json_decode($response->getBody());
			
			if($body === null) {
				$message = $response->getHeaders()->getMessage() ?: $response->getBody();
			} else {
				$message = $response->getHeaders()->getMessage() ?: 'Unknown Error';

				if(isset($body->error)) {
					if(isset($body->error->error_description)) $message = $body->error->error_description;
					elseif(isset($body->error_description)) $message = $body->error_description;
					elseif(isset($body->error->message)) $message = $body->error->message;
				}
			}

			throw new ClientException($message, $response->getHeaders()->getCode());
		}
	}
}