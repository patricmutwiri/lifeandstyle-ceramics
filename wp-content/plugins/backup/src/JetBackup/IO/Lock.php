<?php

namespace JetBackup\IO;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Lock {

	private static $_files;

	private static function _getFile($filename) {
		if (!file_exists($filename)) touch($filename);
		if (!isset(self::$_files[$filename])) self::$_files[$filename] = @fopen($filename, 'r');
		return self::$_files[$filename];
	}

	private static function _closeFile($filename) {
		if (!isset(self::$_files[$filename])) return;
		@fclose(self::$_files[$filename]);
		unset(self::$_files[$filename]);
	}

	public static function LockFile($filename, $block = false): bool {
		if (($fd = self::_getFile($filename)) === false) return false;
		$flag = LOCK_EX;
		if (!$block) $flag |= LOCK_NB;
		$wouldblock = 0;
		$ret = flock($fd, $flag, $wouldblock);
		if ((!$block && $wouldblock) || $ret === false) return false;
		return true;
	}

	public static function UnlockFile($filename) {
		if (
			($fd = self::_getFile($filename)) === false ||
			flock($fd, LOCK_UN) === false
		) return;
		self::_closeFile($filename);
	}
}