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

use stdClass;

defined("__JETBACKUP__") or die("Restricted Access.");

class JetHttpResponseHeaders {

	private int $_code=0;
	private string $_message='';
	private object $_headers;
	private bool $_is_ftp;

	/**
	 * @param string $headers
	 * @param bool $isFTP
	 */
	public function __construct(string $headers, bool $isFTP=false) {
		$this->_headers = new stdClass();
		$this->_is_ftp = $isFTP;
		$this->_parseResponseHeaders($headers);
	}

	/**
	 * @param string $response
	 *
	 * @return void
	 */
	private function _parseResponseHeaders(string $response): void {

		$headers = explode("\r\n", $response);

		foreach( $headers as $v ) {
			if($this->_is_ftp && preg_match("/^\d{3}/", $v)) {
				$this->addHeader('ftp', trim($v), true);
			} else {
				$t = explode( ':', $v, 2 );
				if(!$this->_is_ftp && sizeof($t) == 1 && preg_match( "#HTTP/[0-9.]+\s+([0-9]+)\s+(.*)#",$v, $m ) ){
					$this->setCode(intval($m[1]));
					$this->setMessage(trim($m[2]));
					continue;
				}

				if(sizeof($t) == 2) $this->addHeader(strtolower($t[0]), trim($t[1]));
			}
		}
	}

	/**
	 * @param int $code
	 *
	 * @return void
	 */
	public function setCode(int $code):void { $this->_code = $code; }

	/**
	 * @return int
	 */
	public function getCode():int { return $this->_code; }

	/**
	 * @param string $message
	 *
	 * @return void
	 */
	public function setMessage(string $message):void { $this->_message = $message; }

	/**
	 * @return string
	 */
	public function getMessage():string { return $this->_message; }

	/**
	 * @param object $headers
	 *
	 * @return void
	 */
	public function setHeaders(object $headers):void {
		$this->_headers = $headers;
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @param bool $append
	 *
	 * @return void
	 */
	public function addHeader(string $key, string $value, bool $append=false):void {
		if($append) {
			if(!isset($this->_headers->{$key})) $this->_headers->{$key} = [];
			$this->_headers->{$key}[] = $value;
		} else $this->_headers->{$key} = $value;
	}

	/**
	 * @return object
	 */
	public function getHeaders():object {
		return $this->_headers;
	}

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getHeader(string $key) {
		return $this->_headers->{$key} ?? null;
	}
}