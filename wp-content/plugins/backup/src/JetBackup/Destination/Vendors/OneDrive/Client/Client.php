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
namespace JetBackup\Destination\Vendors\OneDrive\Client;

use JetBackup\Destination\Vendors\OneDrive\OneDrive;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Log\LogController;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileDownload;
use JetBackup\Web\JetHttp;
use stdClass;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class Client {

	const LOGIN_HOST = "login.microsoftonline.com";
	const TOKEN_URL = "https://" . self::LOGIN_HOST . "/common/oauth2/v2.0/token";
	const REDIRECT_URI = "https://auth.jetbackup.com/microsoft/";
	const CLIENT_ID = "93e83d79-3f8d-483b-9285-ae358b83640e";
	const CLIENT_SECRET = "JUl8Q~myq0ZdMFHOjc52Cr2-rqVP_S-Iit432a3Z";
	const GRAPH_URL = "https://graph.microsoft.com/v1.0/";

	const METHOD_GET = JetHttp::METHOD_GET;
	const METHOD_POST = JetHttp::METHOD_POST;
	const METHOD_PUT = JetHttp::METHOD_PUT;
	const METHOD_DELETE = JetHttp::METHOD_DELETE;
	const METHOD_HEAD = JetHttp::METHOD_HEAD;

	const HTTP_VERSION_DEFAULT = 0;
	const HTTP_VERSION_1_1 = 1;
	const HTTP_VERSION_2_0 = 2;

	private ?JetHttp $_http=null;
	private bool $_verifyssl;
	private int $_http_version;
	private string $_access_token='';
	private string $_refresh_token='';
	private string $_auth_code='';
	private bool $_use_access_token;
	private string $_client_id='';
	private string $_client_secret='';

	private string $_uri='';
	private int $_method=self::METHOD_GET;
	private array $_params=[];
	private $_body=false;
	private string $_destination='';
	private ?FileChunk $_fileUpload=null;
	private array $_headers=[];
	private LogController $_log_controller;

	/**
	 * @param bool $verifyssl
	 * @param int $http_version
	 */
	public function __construct(bool $verifyssl=true, int $http_version=0) {
		$this->_verifyssl = !!$verifyssl;
		$this->_http_version = $http_version;
	}

	/**
	 * @return void
	 */
	private function _reset():void {
		$this->_fileUpload = null;
		$this->_body = false;

		$this->_uri = $this->_destination = '';
		$this->_method = self::METHOD_GET;
		$this->_headers = $this->_params = [];
		$this->_use_access_token = true;
	}

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
	public function getHeader(string $key):string { return $this->_headers[$key] ?? ''; }

	/**
	 * @return int
	 */
	public function getMethod():int { return $this->_method; }

	/**
	 * @param int $method
	 *
	 * @return void
	 */
	public function setMethod(int $method):void { $this->_method = $method; }

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
	 * @param bool $use
	 *
	 * @return void
	 */
	public function setUseAccessToken(bool $use):void { $this->_use_access_token = $use; }

	/**
	 * @return bool
	 */
	public function isUseAccessToken():bool { return !!$this->_use_access_token; }

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
	 * @return int
	 */
	public function getHTTPVersion():int { return $this->_http_version; }

	/**
	 * @param int $http_version
	 *
	 * @return void
	 */
	public function setHTTPVersion(int $http_version):void { $this->_http_version = $http_version; }

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
	public function getParams():array { return $this->_params; }

	/**
	 * @param array $params
	 *
	 * @return void
	 */
	public function setParams(array $params):void { $this->_params = $params; }

	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return void
	 */
	public function addParam(string $key, string $value):void { $this->_params[$key] = $value; }

	/**
	 * @return FileChunk|null
	 */
	public function getFileChunk():?FileChunk { return $this->_fileUpload; }

	/**
	 * @param FileChunk $chunk
	 *
	 * @return void
	 */
	public function setFileChunk(FileChunk $chunk):void { $this->_fileUpload = $chunk; }

	public function setLogController(LogController $logController):void {
		$this->_log_controller = $logController ?: new LogController();
	}

	public function getLogController():LogController { return $this->_log_controller; }
	
	/**
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function fetchToken():object {
		$this->_reset();
		$this->setURI(self::TOKEN_URL);
		$this->setMethod(self::METHOD_POST);
		$this->addHeader('Host', self::LOGIN_HOST);
		$this->addHeader('Content-type', 'application/x-www-form-urlencoded');
		//$this->addHeader('Origin', 'https://' . gethostname());
		
		$params = [];
		$params['scope'] = 'Files.ReadWrite offline_access';
		$params['client_id'] = $this->getClientId();
		$params['client_secret'] = $this->getClientSecret();
		$params['redirect_uri'] = self::REDIRECT_URI;

		if($this->getAuthorizationCode()) {
			$params['code'] = $this->getAuthorizationCode();
			//$params['code_verifier'] = $this->getCodeVerifier();
			$params['grant_type'] = 'authorization_code';
		} else {
			$params['refresh_token'] = $this->getRefreshToken();
			$params['grant_type'] = 'refresh_token';
		}

		$this->setBody(http_build_query($params, '', '&'));
		
		return $this->_execute();
	}

	/**
	 * @param FileChunk $fileUpload
	 * @param string $destination
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function putChunk(FileChunk $fileUpload, string $destination):object {

		$this->getLogController()->logDebug("[putChunk]");
		$this->_reset();

		$this->setMethod(self::METHOD_PUT);
		$this->setURI($destination);
		$this->setFileChunk($fileUpload);
		$this->setUseAccessToken(false);

		$tell = $fileUpload->getFile()->tell();
		$end = $tell+$fileUpload->getSize()-1;
		
		$this->addHeader("content-type", "multipart/form-data");
		$this->addHeader("content-length", $fileUpload->getSize());
		$this->addHeader("Content-Range", "bytes " . $tell . "-" . $end . "/" . $fileUpload->getFile()->getSize());

		return $this->_execute();
	}

	/**
	 * @param string $uri
	 * @param string $body
	 * @param array $params
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function putString(string $uri, string $body, array $params=[]):object {
		$this->_reset();
		$this->setMethod(self::METHOD_PUT);
		$this->setURI(self::GRAPH_URL . $uri);
		$this->setParams($params);
		$this->setBody($body);

		if($body) $this->addHeader("Content-Type", "text/plain");
		$this->addHeader("Content-Length", strlen($body));

		return $this->_execute();
	}

	/**
	 * @param string $uri
	 * @param array $params
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	private function _request(string $uri, array $params):object {
		$this->setURI(self::GRAPH_URL . $uri);
		$this->setParams($params);

		return $this->_execute();
	}

	/**
	 * @param string $destination
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function createUploadSession(string $destination):object {
		$this->_reset();
		$this->setMethod(self::METHOD_POST);
		$this->setURI(self::GRAPH_URL . $destination . ':/createUploadSession');
		$body = '{}';
		$this->setBody('{}');
		$this->addHeader('Content-Type', 'application/json');
		$this->addHeader('Content-Length', strlen($body));
		return $this->_execute();
	}

	/**
	 * @param string $upload_uri
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function checkUploadSession(string $upload_uri):object {

		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($upload_uri);
		$this->addHeader('Content-Type', 'application/json');
		$this->setUseAccessToken(false);

		return $this->_execute();
	}

	/**
	 * @param string $uri
	 * @param string $destination
	 * @param int $start
	 * @param int $end
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getObject(string $uri, string $destination, int $start=0, int $end=0):object {

		$this->getLogController()->logDebug("[getObject] Uri: $uri");
		$this->getLogController()->logDebug("[getObject] Destination: $destination");
		$this->getLogController()->logDebug("[getObject] Start: $start, End: $end");

		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($uri);
		$this->setDestination($destination);
		$this->addHeader('Content-Type', 'text/plain');
		if($start || $end) $this->addHeader('Range', 'bytes=' . $start . '-' . $end);
		return str_starts_with($uri, "http") ? $this->_execute() : $this->_request($uri, []);
	}

	public function downloadChunked(string $sourceUri, string $destination, int $start, int $end): int {
		$this->_reset();

		// First request: fetch only headers to get content-location
		$this->setMethod(self::METHOD_GET);
		$this->setURI(self::GRAPH_URL . $sourceUri);
		$this->addHeader('Range', "bytes=$start-$end");
		// Execute the request to fetch headers
		$response = $this->_execute();

		// Check if content-location is available
		$contentLocation = $response->Headers->{'content-location'} ?? $response->Headers->{'location'} ?? null;

		if ($contentLocation === null) {
			throw new ClientException("Content-Location header is missing.");
		}

		// Log the content-location URL
		$this->getLogController()->logDebug("[downloadChunked] Content-Location: $contentLocation");

		// Perform the actual download using the content-location URL
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($contentLocation);
		$this->addHeader('Range', "bytes=$start-$end");
		$this->setUseAccessToken(false);
		$this->setDestination($destination);

		$finalResponse = $this->_execute();

		// Verify successful download by checking the Content-Range header
		if (!isset($finalResponse->Headers->{'content-range'})) {throw new ClientException("Failed to download the chunk $start-$end. Content-Range is missing.");}
		$this->getLogController()->logDebug("[downloadChunked] Downloaded range: {$finalResponse->Headers->{'content-range'}}");

		// Return the number of bytes downloaded
		// Without '+1', the last byte of each chunk would be missing, leading to an incomplete file.
		return ($end - $start + 1);
	}


	/**
	 * @param string $uri
	 * @param array $params
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function get(string $uri, array $params=[]):object {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->addHeader('Content-Type', 'text/plain');
		return $this->_request($uri, $params);
	}

	/**
	 * @param string $uri
	 * @param array $params
	 * @param string|false $body
	 * @param string|null $contentType
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function post(string $uri, array $params=[], $body=false, ?string $contentType=null):object {
		$this->_reset();

		$this->setMethod(self::METHOD_POST);
		if($body !== false) $this->setBody($body);
		if($contentType) $this->addHeader('Content-Type', $contentType);
		return $this->_request($uri, $params);
	}

	/**
	 * @param string $uri
	 * @param array $params
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function delete(string $uri, array $params=[]):object {
		$this->_reset();
		$this->setMethod(self::METHOD_DELETE);
		return $this->_request($uri, $params);
	}

	/**
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 * @throws IOException
	 */
	private function _execute():object {
		$url = $this->getURI();
		if($this->getParams()) $url .= "?" . http_build_query($this->getParams());


		if(!$this->_http) $this->_http = new JetHttp();
		$this->_http->reset();

		$this->_http
			->setSSLVerify($this->_verifyssl ? 1 : 0, $this->_verifyssl ? 2 : 0)
			->setFollowLocation()
			->setReturnTransfer()
			->setConnectionTimeout(30)
			->setTimeout(43200)
			->setLowSpeed(1, 120);

		// Handle the http version - default, do not add anything. - 1, use http version 1.1. - 2, use http version 2.
		if ($this->getHTTPVersion())
			$this->_http->addOption(CURLOPT_HTTP_VERSION, $this->getHTTPVersion() == self::HTTP_VERSION_1_1? CURL_HTTP_VERSION_1_1: CURL_HTTP_VERSION_2_0);
		if (defined('CURL_SSLVERSION_TLSv1_2'))
			$this->_http->addOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

		if(!($chunk = $this->getFileChunk())) {
			$this->_http
				->setMethod($this->getMethod())
				->setBody($this->getBody());
		}
		
		if($this->isUseAccessToken() && $this->getAccessToken()) $this->addHeader('Authorization', 'Bearer ' . $this->getAccessToken());

		$this->_http->setHeaders($this->getHeaders());

		if(($destination = $this->getDestination())) {
			if(!file_exists(dirname($destination)) || !is_dir(dirname($destination)))
				throw new ClientException("Destination provided not exists (" . dirname($destination) . ")");
			$fileDownload = new FileDownload($destination);
			$response = $this->_http->download($url, $fileDownload);
			
		} else {
			$response = $chunk ? $this->_http->uploadChunk($url, $chunk) : $this->_http->exec($url);
		}

		$output = new stdClass();
		$output->Headers = $response->getHeaders()->getHeaders();
		$output->Body = json_decode(trim($response->getBody()));

		if($output->Body === false) $output->Body = $response->getBody();
		elseif(isset($output->Body->error) && $output->Body->error) {
			$message = '';
			if(isset($output->Body->error->message)) $message = $output->Body->error->message;
			if(isset($output->Body->error_description)) $message = $output->Body->error_description;
			throw new ClientException($message, $response->getHeaders()->getCode());
		}

		if($response->getHeaders()->getCode() < 200 || $response->getHeaders()->getCode() > 299) {
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