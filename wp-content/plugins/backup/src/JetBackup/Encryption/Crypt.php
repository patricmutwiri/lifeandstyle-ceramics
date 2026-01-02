<?php
/*
*
* JetBackup @ package
* Created By Shlomi Bazak
*
* Copyrights @ JetApps
* https://www.jetapps.com
*
**/
namespace JetBackup\Encryption;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Crypt {

	const CIPHER_METHOD = "AES-256-CBC";
	const CIPHER_METHOD_V1 = "AES-128-CTR";
	const DELIMITER = "|";
	const CURRENT_VERSION = "v2";

	private string $iv; 		// The iv used for encryption, decryption
	private string $key='';		// The key used for encryption, decryption
	private bool $useBase64=false; // Boolean, eather  encrypt/decrypt with assist of base64 string.

	/**
	 * @param string $key
	 * @param bool $useBase64
	 */
	private function __construct(string $key='', bool $useBase64=true) {
		$this->iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_METHOD));
		$this->setKey( $key );
		$this->useBase64($useBase64);
	}

	/**
	 * @param bool $use
	 *
	 * @return void
	 */
	public function useBase64(bool $use=true):void {
		$this->useBase64 = $use;
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	public function setKey(string $key):void {
		$this->key = $key;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	private function tobase64(string $data):string {
		if($this->useBase64) return base64_encode($data);
		return $data;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	private function fromBase64(string $data):string {
		if($this->useBase64) return base64_decode($data);
		return $data;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function _encrypt(string $data):string {
		return 
			$this->tobase64($this->iv) .
			self::DELIMITER .
			$this->tobase64(openssl_encrypt($data, self::CIPHER_METHOD, $this->key, OPENSSL_RAW_DATA, $this->iv)) . 
			self::DELIMITER . 
			self::CURRENT_VERSION;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function _decrypt(string $data):string {

		$version = substr($data, -3);
		
		if($version == self::DELIMITER . self::CURRENT_VERSION) {
			list($iv, $data) = explode(self::DELIMITER, $data);
			$iv = $this->fromBase64($iv);
			return trim(openssl_decrypt($this->fromBase64($data), self::CIPHER_METHOD, $this->key, OPENSSL_RAW_DATA, $iv));
		} else {
			$iv = '1234567891011121';
			return trim(openssl_decrypt($this->fromBase64($data), self::CIPHER_METHOD_V1, $this->key, OPENSSL_RAW_DATA, $iv));
		}
	}
	
	public static function encrypt(string $data, ?string $key=null, bool $useBase64=true):string {
		return (new Crypt($key, $useBase64))->_encrypt($data);
	}

	public static function decrypt(string $data, ?string $key=null, bool $useBase64=true):string {
		return (new Crypt($key, $useBase64))->_decrypt($data);
	}
}