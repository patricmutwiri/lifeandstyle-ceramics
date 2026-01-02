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
namespace JetBackup\Web;

use JetBackup\Exception\HttpRequestException;
use JetBackup\Web\File\FileStream;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileDownload;

defined("__JETBACKUP__") or die("Restricted Access.");

class JetHttp {

	const METHOD_HEAD       = 1;
	const METHOD_PUT        = 2;
	const METHOD_DELETE     = 3;
	const METHOD_GET        = 4;
	const METHOD_POST       = 5;
	
	const LINE_BREAK = "\r\n";
	
	private $_curl;
	private JetHttpResponse $_response;
	private bool $_debug;
	private array $_headers;
	private array $_options;
	private int $_method;
	private $_body;
	private bool $_is_ftp=false;

	/**
	 * @param bool $debug
	 */
	public function __construct(bool $debug=false) {
		$this->reset();
		
		$this->_debug = !!$debug;
		if($this->_debug) $this->setVerbose();

		$this->renew();
	}

	/**
	 * @param bool $debug
	 *
	 * @return JetHttp
	 */
	public static function request(bool $debug=false):JetHttp {
		return new JetHttp($debug);
	}

	/**
	 * @return void
	 */
	public function reset():void {
		$this->_headers = [];
		$this->_options = [];
		$this->_method = 0;
		$this->_body = false;
		$this->_response = new JetHttpResponse();

		if($this->_curl) curl_reset($this->_curl);
	}
	
	/**
	 * @param string $key
	 * @param string $value
	 *
	 * @return JetHttp
	 */
	public function addHeader(string $key, string $value):JetHttp {
		$headers = $this->getHeaders();
		$headers[$key] = $value; 
		$this->setHeaders($headers);
		return $this; 
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array {
		return $this->_headers;
	}

	/**
	 * @param array $headers
	 *
	 * @return JetHttp
	 */
	public function setHeaders(array $headers):JetHttp {
		$this->_headers = $headers;
		return $this;
	}

	/**
	 * @param int $option
	 * @param mixed $value
	 *
	 * @return JetHttp
	 */
	public function addOption(int $option, $value):JetHttp {
		$this->_options[$option] = $value;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOption(int $option) {
		return $this->_options[$option] ?? null;
	}

	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->_options;
	}

	/**
	 * @param array $options
	 *
	 * @return JetHttp
	 */
	public function setOptions(array $options):JetHttp {
		$this->_options = $options;
		return $this;
	}

	/**
	 * @param int $option
	 *
	 * @return bool
	 */
	public function optionExists(int $option):bool {
		$options = $this->getOptions();
		return isset($options[$option]);
	}

	/**
	 * @return int
	 */
	public function getMethod(): int {
		return $this->_method;
	}

	/**
	 * @param int $method
	 *
	 * @return JetHttp
	 */
	public function setMethod(int $method):JetHttp {
		$this->_method = $method;
		return $this;
	}

	/**
	 * @return string|array|false
	 */
	public function getBody() {
		return $this->_body;
	}

	/**
	 * @param string|array|false $body
	 *
	 * @return JetHttp
	 */
	public function setBody($body):JetHttp {
		$this->_body = $body;
		return $this;
	}

	/**
	 * @param null $stderr
	 *
	 * @return JetHttp
	 */
	public function setVerbose($stderr=null):JetHttp {
		$this->addOption(CURLOPT_VERBOSE, true);
		if($stderr !== null) $this->addOption(CURLOPT_STDERR, $stderr);
		return $this;
	}

	/**
	 * @param int|null $limit
	 * @param int|null $time
	 *
	 * @return JetHttp
	 */
	public function setLowSpeed(?int $limit=null, ?int $time=null):JetHttp {
		if($limit !== null) $this->addOption(CURLOPT_LOW_SPEED_LIMIT, $limit);
		if($time !== null) $this->addOption(CURLOPT_LOW_SPEED_TIME, $time);
		return $this;
	}

	/**
	 * @param int|null $peer
	 * @param int|null $host
	 *
	 * @return JetHttp
	 */
	public function setSSLVerify(?int $peer=null, ?int $host=null):JetHttp {
		if($peer !== null) $this->addOption(CURLOPT_SSL_VERIFYPEER, $peer);
		if($host !== null) $this->addOption(CURLOPT_SSL_VERIFYHOST, $host);
		return $this;
	}

	public function setAuth(?string $username=null, ?string $password=null, ?int $type=null):JetHttp {
		if($username !== null) $this->addOption(CURLOPT_USERNAME, $username);
		if($password !== null) $this->addOption(CURLOPT_USERPWD, $password);
		if($type !== null) $this->addOption(CURLOPT_HTTPAUTH, $type);
		return $this;
	}
	
	/**
	 * @param int $port
	 *
	 * @return $this
	 */
	public function setPort(int $port):JetHttp {
		$this->addOption(CURLOPT_PORT, $port);
		return $this;
	}

	/**
	 * @param int $timeout
	 *
	 * @return JetHttp
	 */
	public function setTimeout(int $timeout):JetHttp {
		$this->addOption(CURLOPT_TIMEOUT, $timeout);
		return $this;
	}

	/**
	 * @param int $timeout
	 *
	 * @return JetHttp
	 */
	public function setConnectionTimeout(int $timeout):JetHttp {
		$this->addOption(CURLOPT_CONNECTTIMEOUT, $timeout);
		return $this;
	}

	/**
	 * @return JetHttp
	 */
	public function setFollowLocation():JetHttp {
		$this->addOption(CURLOPT_FOLLOWLOCATION, 1);
		return $this;
	}

	public function setReturnTransfer():JetHttp {
		$this->addOption(CURLOPT_RETURNTRANSFER, 1);
		return $this;
	}

	/**
	 * @param string $url
	 * @param FileChunk $chunk
	 * @param bool $self_method
	 *
	 * @return JetHttpResponse
	 * @throws HttpRequestException
	 */
	public function uploadChunk(string $url, FileChunk $chunk, $self_method=false):JetHttpResponse {
		
		if(!$self_method) {
			$this->setMethod(0);
			$this->addOption(CURLOPT_PUT, 1);
		}
		
		$this->addOption(CURLOPT_INFILE, $chunk->getFile()->getDescriptor());
		$this->addOption(CURLOPT_INFILESIZE, $chunk->getSize());
		$this->addOption(CURLOPT_READFUNCTION, function ($ch, $fd, $length) use ($chunk) {
			return $chunk->readPiece($length);
		});

		return $this->exec($url);
	}

	/**
	 * @param string $url
	 * @param FileStream $file
	 *
	 * @return JetHttpResponse
	 * @throws HttpRequestException
	 */
	public function upload(string $url, FileStream $file):JetHttpResponse {
		$this->setMethod(0);
		$this->addOption(CURLOPT_PUT, 1);
		$this->addOption(CURLOPT_INFILE, $file->getDescriptor());
		$this->addOption(CURLOPT_INFILESIZE, $file->getSize());
		$this->addOption(CURLOPT_READFUNCTION, function ($ch, $fd, $length) use ($file) {
			return $file->read($length);
		});

		return $this->exec($url);
	}

	/**
	 * @param string $url
	 * @param FileStream $stream
	 * @param string $details
	 *
	 * @return JetHttpResponse
	 * @throws HttpRequestException
	 */
	public function uploadString(string $url, FileStream $stream, string $details):JetHttpResponse {

		$boundary = uniqid();
		$delimiter = '-------------' . $boundary;

		$body = "--$delimiter" . self::LINE_BREAK;
		$body .= "Content-Type: application/json" . self::LINE_BREAK . self::LINE_BREAK;
		$body .= $details . self::LINE_BREAK;
		$body .= "--$delimiter" . self::LINE_BREAK;
		$body .= "Content-Type: " . $stream->getMimeType() . self::LINE_BREAK . self::LINE_BREAK;
		$body .= ($stream->getSize() > 0 ? $stream->read() : '') . self::LINE_BREAK;
		$body .= "--$delimiter--" . self::LINE_BREAK;

		$this->addHeader('Content-Type', 'multipart/related; boundary=' . $delimiter);
		$this->addHeader('Content-Length', strlen($body));
		$this->setMethod(self::METHOD_POST);
		$this->setBody($body);
		
		return $this->exec($url);
	}
	
	/**
	 * @param string $url
	 * @param FileDownload $fileDownload
	 *
	 * @return JetHttpResponse
	 * @throws HttpRequestException
	 */
	public function download(string $url, FileDownload $fileDownload):JetHttpResponse {
		
		$this->addOption(CURLOPT_WRITEFUNCTION, function($ch, $str) use ($fileDownload) {

			$response = $this->_response->getHeaders();
			
			if(!$this->_is_ftp && (!$response || $response->getCode() < 200 || $response->getCode() > 299)) {
				$fileDownload->deleteFile();
				$this->_response->appendBody($str);
			} else {
				$fileDownload->writeFile($str);
			}

			return strlen($str);

		});

		$this->_prepare($url);
		curl_exec($this->_curl);
		return $this->_finalize();
	}

	/**
	 * @param string $url
	 *
	 * @return JetHttpResponse
	 * @throws HttpRequestException
	 */
	public function exec(string $url):JetHttpResponse {
		$this->_prepare($url);
		$this->_response->setBody(curl_exec($this->_curl));
		return $this->_finalize();
	}

	/**
	 * @return JetHttpResponse
	 * @throws HttpRequestException
	 */
	private function _finalize(): JetHttpResponse {
		$error_code = curl_errno($this->_curl);
		$error_message = curl_error($this->_curl);

		/**
		 * Detect known transient SSL/network errors (case-insensitive)
		 * By default the error code will be 0, so we return custom error
		 * code 499 so our wrapper clients will trigger retry
		 */
		$retryableSSL = (
			stripos($error_message, 'SSL_ERROR_SYSCALL') !== false ||
			stripos($error_message, 'Connection reset') !== false ||
			stripos($error_message, 'Connection aborted') !== false ||
			stripos($error_message, 'timeout') !== false ||
			stripos($error_message, 'timed out') !== false ||
			stripos($error_message, 'could not resolve host') !== false ||  // DNS issue
			stripos($error_message, 'Failed to connect to') !== false ||    // TCP connection issue
			stripos($error_message, 'Transfer closed') !== false            // abrupt remote closure
		);

		if ($error_code || $retryableSSL) {
			$code = $retryableSSL ? 499 : $error_code;
			throw new HttpRequestException($error_message, $code);
		}

		return $this->_response;
	}


	/**
	 * @param string $url
	 *
	 * @return void
	 */
	private function _prepare(string $url):void {

		$this->_is_ftp = str_starts_with($url, 'ftp:') || str_starts_with($url, 'ftps:');
		$this->_response->isFTP($this->_is_ftp);
		
		$this->addOption(CURLOPT_URL, $url);
		$this->addOption(CURLOPT_HEADER, 0);
		$this->addOption(CURLOPT_HEADERFUNCTION, function($ch, $str) {
			$this->_response->appendHeadersBuffer($str);
			return strlen($str);
		});
		
		switch($this->getMethod()) {
			case self::METHOD_HEAD:     $this->addOption(CURLOPT_NOBODY, 1); break;
			case self::METHOD_PUT:      $this->addOption(CURLOPT_CUSTOMREQUEST, 'PUT'); break;
			case self::METHOD_DELETE:   $this->addOption(CURLOPT_CUSTOMREQUEST, 'DELETE'); break;
			case self::METHOD_GET:      $this->addOption(CURLOPT_CUSTOMREQUEST, 'GET'); break;
			case self::METHOD_POST:     $this->addOption(CURLOPT_POST, 1); break;
		}

		if($this->getBody() !== false) $this->addOption(CURLOPT_POSTFIELDS, $this->getBody());
		
		$headers = [];
		foreach($this->getHeaders() as $key => $value) $headers[] = "$key:$value";
		if($headers) $this->addOption(CURLOPT_HTTPHEADER, $headers);

		curl_setopt_array($this->_curl, $this->getOptions());
		//$this->_logRequestDetails($url, $headers);
	}

	// Use for heavy debugs
	private function _logRequestDetails(string $url, array $headers): void {
		$options = $this->getOptions();
		$method = $this->getMethod();
		$body = $this->getBody();

		$methodMap = [
			self::METHOD_HEAD => 'HEAD',
			self::METHOD_PUT => 'PUT',
			self::METHOD_DELETE => 'DELETE',
			self::METHOD_GET => 'GET',
			self::METHOD_POST => 'POST',
		];

		$logMessage = "[JetHttp Request]\n";
		$logMessage .= "URL: $url\n";
		$logMessage .= "Method: " . $methodMap[$method] ?? 'UNKNOWN' . "\n";
		$logMessage .= "Headers: " . print_r($headers, true) . "\n";
		$logMessage .= "Options: " . print_r($options, true) . "\n";
		if ($body) {
			$logMessage .= "Body: " . (is_string($body) ? $body : json_encode($body, JSON_PRETTY_PRINT)) . "\n";
		}

		// Choose logging option
		//error_log($logMessage);
		//echo $logMessage . "\n";
		// file_put_contents('/path/to/log.txt', $logMessage, FILE_APPEND);

		//foreach($this->getOptions() as $option => $value) echo "OPTION: $option -> " . (is_callable($value) ? "FUNC" : print_r($value, true)) . "\n";
		//echo "\n\n";
	}


	/**
	 * @return void
	 */
	public function close():void {
		$this->_curl = false;
	}

	/**
	 * @return void
	 */
	public function renew():void {
		$this->_curl = curl_init();
	}
	
	/**
	 * 
	 */
	public function __destruct() {
		$this->close();
	}
}