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

defined("__JETBACKUP__") or die("Restricted Access.");

class JetHttpResponse {

	private bool $_is_ftp = false;
	private string $_body = '';
	private string $_headers_buffer = '';
	private ?JetHttpResponseHeaders $_headers = null;

	/**
	 * @param bool|null $isFTP
	 *
	 * @return bool
	 */
	public function isFTP(?bool $isFTP=null): bool {
		if($isFTP !== null) $this->_is_ftp = $isFTP; 
		return $this->_is_ftp;
	}
	
	/**
	 * @param string $body
	 *
	 * @return void
	 */
	public function setBody(string $body):void {
		$this->_body = $body;
	}

	/**
	 * @param string $body
	 *
	 * @return void
	 */
	public function appendBody(string $body):void {
		$this->_body .= $body;
	}

	/**
	 * @return string
	 */
	public function getBody():string {
		return $this->_body;
	}

	/**
	 * @param string $headers
	 *
	 * @return void
	 */
	public function setHeadersBuffer(string $headers):void {
		$this->_headers_buffer = $headers;
	}

	/**
	 * @param string $headers
	 *
	 * @return void
	 */
	public function appendHeadersBuffer(string $headers):void {
		$this->_headers_buffer .= $headers;
	}

	/**
	 * @return JetHttpResponseHeaders|null
	 */
	public function getHeaders():?JetHttpResponseHeaders {
		if(!$this->_headers && $this->_headers_buffer) $this->_headers = new JetHttpResponseHeaders($this->_headers_buffer, $this->_is_ftp);
		return $this->_headers;
	}
}