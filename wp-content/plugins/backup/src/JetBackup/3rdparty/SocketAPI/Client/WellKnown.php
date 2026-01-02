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
namespace JetBackup\SocketAPI\Client;

use JetBackup\Entities\Util;
use JetBackup\SocketAPI\Exception\WellKnownException;

class WellKnown {
	
	const WELL_KNOWN_FILENAME = '.jetbackup-well-known';
	const HASH_CHARS = '0123456789abcdef';
	const HASH_REGEX = '^[0-9a-f]{32}$';
	const HASH_LENGTH = 32;
	
	private $_details;
	private $_file;
	
	private function __construct() {
		$this->_details = Util::getpwuid(Util::geteuid());
		if (!$this->_details) throw new WellKnownException("[wellknown auth] Cannot get posix uid/gid details");
		$this->_file = preg_replace("#/+#", "/" ,$this->_details['dir'] . '/' . self::WELL_KNOWN_FILENAME);
	}

	private static function _generateHash() {
		$randomString = '';
		for ($i = 0; $i < self::HASH_LENGTH; $i++) $randomString .= self::HASH_CHARS[rand(0, strlen(self::HASH_CHARS)-1)];
		return $randomString;
	}

	/**
	 * @return string
	 * @throws WellKnownException
	 */
	private function _generate() {
		if(file_exists($this->_file) && !@unlink($this->_file)) throw new WellKnownException("Failed deleting old well known file");
		$password = self::_generateHash();
		$umask = umask(077);
		if(!file_put_contents($this->_file, $password)) throw new WellKnownException("Failed writing password to well known file");
		umask($umask);
		return $password;
	}

	/**
	 * @return string
	 * @throws WellKnownException
	 */
	private function _fetch() {
		if(file_exists($this->_file)) {
			$stat = stat($this->_file);
			if(
				$stat['mtime'] > (time()-3600) && 
				substr(sprintf('%o', $stat['mode']), -4) == '0600' &&
				$this->_details['uid'] == $stat['uid'] &&
				($password = file_get_contents($this->_file)) &&
				self::_validateHash($password)
			) return $password;
		}
		return $this->_generate();
	}

	private static function _validateHash($hash) {
		return preg_match("/" . self::HASH_REGEX . "/", $hash);
	}
	
	/**
	 * @return string
	 * @throws WellKnownException
	 */
	public static function getPassword() {
		return (new WellKnown())->_fetch();
	}
}
