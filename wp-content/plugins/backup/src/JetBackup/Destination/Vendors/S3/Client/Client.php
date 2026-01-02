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

use JetBackup\Destination\Vendors\S3\Client\Exception\ClientException;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileDownload;
use JetBackup\Web\JetHttp;
use JetBackup\Wordpress\Wordpress;
use stdClass;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class Client {

	const METHOD_GET = JetHttp::METHOD_GET;
	const METHOD_POST = JetHttp::METHOD_POST;
	const METHOD_PUT = JetHttp::METHOD_PUT;
	const METHOD_DELETE = JetHttp::METHOD_DELETE;
	const METHOD_HEAD = JetHttp::METHOD_HEAD;

	const METHOD_NAMES = [
		self::METHOD_GET        => 'GET',
		self::METHOD_POST       => 'POST',
		self::METHOD_PUT        => 'PUT',
		self::METHOD_DELETE     => 'DELETE',
		self::METHOD_HEAD       => 'HEAD',
	];
	
	const ALGO = 'sha256';
	const ALGORITHM = 'AWS4-HMAC-SHA256';
	const DATE_FORMAT = "Ymd";
	const DATE_FORMAT_LONG = "Ymd\THis\Z";

	private ?JetHttp $_http=null;
	private string $_key;
	private string $_secret;
	private string $_region;
	private string $_bucket;
	private bool $_verifyssl;
	private int $_date;
	private string $_host;

	private string $_uri = '';
	private int $_method;
	private array $_params;
	private $_body;
	private string $_destination;
	private ?FileChunk $_chunk = null;
	private array $_headers;
	private array $_headers_signature;
	private int $_keepalive_timeout;
	private int $_keepalive_requests;

	public function __construct(string $key, string $secret, string $region, string $bucket, string $endpoint, bool $verifyssl=true, int $keepalive_timeout=0, int $keepalive_queries=0) {
		$this->_key = $key;
		$this->_secret = $secret;
		$this->_region = $region;
		$this->_bucket = $bucket;
		$this->_verifyssl = !!$verifyssl;
		$this->_keepalive_timeout = $keepalive_timeout;
		$this->_keepalive_requests = $keepalive_queries;

		//if($this->_region && strpos($endpoint, '{region}') === false) $endpoint = "{region}.{$endpoint}";
		$this->_host = str_replace(['{region}','{bucket}'], [$this->_region, $this->_bucket], $endpoint);
		if(Wordpress::strContains($endpoint, '{bucket}')) $this->_bucket = '';
	}

	private function _reset():void {
		$this->_chunk = null;
		$this->_body = false;
		$this->_date = time();

		$this->_uri = $this->_destination = '';
		$this->_method = self::METHOD_GET;
		$this->_headers = $this->_headers_signature = $this->_params = [];

		$this->addHeader("host", $this->_host, true);
	}

	public function getHeaders():array { return $this->_headers; }
	public function getHeadersSignature():array { return $this->_headers_signature; }
	public function addHeader(string $key, string $value, bool $signature=false):void {
		$this->_headers[$key] = $value;
		if($signature) $this->_headers_signature[$key] = $value;
	}
	public function getHeader(string $key, bool $signature=false):string {
		if($signature) return $this->_headers_signature[$key];
		return $this->_headers[$key];
	}

	public function getMethod():int { return $this->_method; }
	public function setMethod(int $method):void { $this->_method = $method; }

	public function getURI():string {
		$uri = $this->_uri;
		if($this->_bucket) $uri = '/' . $this->_bucket . '/' . $uri;
		$uri = preg_replace("#/+#", "/", $uri);
		return implode("/", array_map('rawurlencode', explode("/", $uri)));
	}
	public function setURI(string $uri):void { $this->_uri = $uri; }

	public function getBody() { return $this->_body; }
	public function setBody($body):void { $this->_body = $body; }

	public function getDestination():string { return $this->_destination; }
	public function setDestination(string $destination):void { $this->_destination = $destination; }

	public function getParams():array { return $this->_params; }
	public function setParams(array $params):void { $this->_params = $params; }
	public function addParams(string $key, $value):void { $this->_params[$key] = $value; }

	public function getFileChunk():?FileChunk { return $this->_chunk; }
	public function setFileChunk(FileChunk $chunk):void { $this->_chunk = $chunk; }

	private static function hmac(string $data, string $key, bool $raw=true):string {
		return hash_hmac( self::ALGO, $data, $key, $raw);
	}

	public function getAmzCredential():string { return gmdate(self::DATE_FORMAT, $this->_date) . '/' . $this->_region . '/s3/aws4_request'; }

	private function buildSignatureCanonical():string {

		$fields = [];
		$fields[] = self::METHOD_NAMES[$this->getMethod()];
		$fields[] = $this->getURI();
		$fields[] = $this->getParams() ? self::http_build_query($this->getParams()) : '';
		foreach($this->getHeadersSignature() as $key => $value) $fields[] = "$key:$value";
		$fields[] = "";
		$fields[] = implode(";", array_keys($this->getHeadersSignature()));
		$fields[] = $this->getHeader('x-amz-content-sha256');

		return hash(self::ALGO, implode("\n", $fields));
	}

	private function buildSignatureSigningString():string {
		return implode("\n", [
			self::ALGORITHM,
			gmdate(self::DATE_FORMAT_LONG, $this->_date),
			$this->getAmzCredential(),
			$this->buildSignatureCanonical()
		]);
	}

	private function buildSignature():string {
		$key = 'AWS4' . $this->_secret;
		foreach(explode("/", $this->getAmzCredential()) as $data) $key = self::hmac($data, $key);
		return self::hmac($this->buildSignatureSigningString(), $key, false);
	}

	/**
	 * @param FileChunk $fileChunk
	 * @param string $destination
	 * @param string|null $uploadId
	 * @param string|null $partNumber
	 *
	 * @return stdClass
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function putChunk(FileChunk $fileChunk, string $destination, ?string $uploadId=null, ?string $partNumber=null):stdClass {
		$this->_reset();

		$this->setMethod(self::METHOD_PUT);
		$this->setURI($destination);
		if($uploadId) $this->addParams('uploadId', $uploadId);
		if($partNumber) $this->addParams('partNumber', $partNumber);
		$this->setFileChunk($fileChunk);

		$this->addHeader("content-type", "multipart/form-data");
		$this->addHeader("content-length", $fileChunk->getSize());

		return $this->_execute();
	}

	/**
	 * @param string|false $body
	 * @param string $uri
	 * @param array $params
	 *
	 * @return object
	 * @throws HttpRequestException
	 * @throws ClientException
	 */
	public function putString($body, string $uri='/', array $params=[]):object {
		$this->_reset();
		$this->setMethod(self::METHOD_PUT);
		$this->setURI($uri);
		$this->setParams($params);
		$this->setBody($body);

		if($body) $this->addHeader("Content-Type", "multipart/form-data");
		$this->addHeader("Content-Length", strlen($body));

		return $this->_execute();
	}

	/**
	 * @param string $uri
	 * @param array $params
	 *
	 * @return object
	 * @throws HttpRequestException
	 * @throws ClientException
	 */
	private function _request(string $uri, array $params):object {
		$this->setURI($uri);
		$this->setParams($params);

		return $this->_execute();
	}

	/**
	 * @param string $uri
	 * @param string $destination
	 *
	 * @return object
	 * @throws ClientException
	 * @throws HttpRequestException
	 */
	public function getObject(string $uri, string $destination):object {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($uri);
		$this->setDestination($destination);

		return $this->_execute();
	}

	public function getObjectRange(string $uri, string $destination, int $start, int $end):object {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		$this->setURI($uri);
		$this->setDestination($destination);
		$this->addHeader('Range', 'bytes=' . $start . '-' . $end);

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
	public function get(string $uri='/', array $params=[]):object {
		$this->_reset();
		$this->setMethod(self::METHOD_GET);
		return $this->_request($uri, $params);
	}

	/**
	 * @param string $uri
	 * @param array $params
	 * @param string|false $body
	 * @param string|null $contentType
	 *
	 * @return object
	 * @throws HttpRequestException
	 * @throws ClientException
	 */
	public function post(string $uri='/', array $params=[], $body=false, ?string $contentType=null):object {
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
	public function delete(string $uri='/', array $params=[]):object {
		$this->_reset();
		$this->setMethod(self::METHOD_DELETE);
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
	public function head(string $uri='/', array $params=[]):object {
		$this->_reset();
		$this->setMethod(self::METHOD_HEAD);
		return $this->_request($uri, $params);
	}

	private function _getAuthorization (): string {
		$authorization = [];
		$authorization[] = "Credential=" . $this->_key . '/' . $this->getAmzCredential();
		$authorization[] = "SignedHeaders=" . implode(";", array_keys($this->getHeadersSignature()));
		$authorization[] = "Signature=" . $this->buildSignature();
		return self::ALGORITHM . " " . implode(",", $authorization);
	}
	
	/**
	 * @return object
	 * @throws HttpRequestException
	 * @throws ClientException
	 */
	private function _execute():object {

		$url = 'https://' . $this->_host;
		$url .= $this->getURI();
		if($this->getParams()) $url .= "?" . http_build_query($this->getParams());

		if(!$this->_keepalive_timeout || !$this->_http) $this->_http = new JetHttp();
		$this->_http->reset();

		$this->_http
			->setSSLVerify($this->_verifyssl ? 1 : 0, $this->_verifyssl ? 2 : 0)
			->setReturnTransfer()
			->setConnectionTimeout(30)
			->setTimeout(43200)
			->setLowSpeed(1, 120);

		if(($chunk = $this->getFileChunk())) {
			$hashedContent = $chunk->getHash(self::ALGO);
		} else {
			if($this->getMethod() != self::METHOD_GET) $this->_http->setMethod($this->getMethod());
			$this->_http->setBody($this->getBody());
			$hashedContent = hash(self::ALGO, $this->getBody() !== false ? $this->getBody() : '');
		}

		if ($this->_keepalive_timeout) {
			if ($this->_keepalive_requests) $keepalive_val = sprintf('timeout=%d,max=%d',$this->_keepalive_timeout, $this->_keepalive_requests);
			else $keepalive_val = sprintf('timeout=%d',$this->_keepalive_timeout);

			$this->addHeader('Connection', 'Keep-Alive');
			$this->addHeader('Keep-Alive', $keepalive_val);
		}
		
		$this->addHeader("x-amz-content-sha256", $hashedContent, true);
		$this->addHeader("x-amz-date", gmdate(self::DATE_FORMAT_LONG, $this->_date), true);
		$this->addHeader("Authorization", $this->_getAuthorization());
		
		$this->_http->setHeaders($this->getHeaders());

		if(($destination = $this->getDestination())) {

			if(!file_exists(dirname($destination)) || !is_dir(dirname($destination)))
				throw new ClientException("Destination provided not exists (" . dirname($destination) . ")");

			$fileDownload = new FileDownload($destination);
			$response = $this->_http->download($url, $fileDownload);

			$output = new stdClass();
			$output->Headers = $response->getHeaders()->getHeaders();
			$output->Body = $response->getBody() ? @simplexml_load_string(trim($response->getBody())) : new stdClass();

			if($output->Body === false) $output->Body = $response->getBody();
			elseif(isset($output->Body->Code) && $output->Body->Code) throw new ClientException($output->Body->Message ? ($output->Body->Message . " (" . $output->Body->Code . ")") : $output->Body->Code, $response->getHeaders()->getCode());

			if($response->getHeaders()->getCode() < 200 || $response->getHeaders()->getCode() > 299)
				throw new ClientException($response->getHeaders()->getMessage(), $response->getHeaders()->getCode());

			return $output;
		}

		$response = $chunk ? $this->_http->uploadChunk($url, $chunk) : $this->_http->exec($url);

		$output = new stdClass();
		$output->Headers = $response->getHeaders()->getHeaders();

		$output->Body = @simplexml_load_string(trim($response->getBody()));

		if($output->Body === false) $output->Body = $response->getBody();
		else {
			$code = $output->Body->Code?? ($output->Body->code?? false);
			$message = $output->Body->Message?? ($output->Body->message?? "");
			if($code) throw new ClientException($message? ($message . " (" . $code . ")") : $code, $response->getHeaders()->getCode());
		}

		if($response->getHeaders()->getCode() < 200 || $response->getHeaders()->getCode() > 299)
			throw new ClientException($response->getHeaders()->getMessage(), $response->getHeaders()->getCode());
		
		return $output;
	}

	private static function http_build_query(array $params):string {
		$url = [];
		foreach($params as $key => $value) $url[] = rawurlencode($key) . "=" . rawurlencode($value);
		sort($url);
		return implode("&", $url);
	}
	
	public function close():void {
		unset($this->_http);
	}
}