<?php

namespace JetBackup\Entities;

use DateTime;
use DateTimeZone;
use Exception;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\Filesystem\File;
use JetBackup\JetBackup;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Util {

	public const WEB_CONFIG_FILE = "%sweb.config";
	const HTACCESS_FILE = "%s.htaccess";
	const INDEX_HTML_FILE = "%sindex.html";

	private function __construct() {}
	
	/**
	 * @param string $directory
	 * @param bool $remove_main
	 * @param bool $verify
	 *
	 * @return void
	 * @throws IOException
	 */
	public static function rm(string $directory, bool $remove_main=true):void {

		if(!$directory) return;

		$file = new File($directory);
		if(!$file->exists()) return;

		if($file->isFile() || $file->isLink()) {
			unlink($directory);
			return;
		}

		$main_length = strlen($directory);
		if (($dir = @dir($directory)) === false) throw new IOException("Cannot delete directory $directory");
		$queue = [$dir];

		while($queue) {
			$obj = array_shift($queue);

			while ($obj !== false && ($fileName = $obj->read()) !== false) {
				if($fileName == '.' || $fileName == '..') continue;

				$file = new File($obj->path . '/' . $fileName);

				if($file->isLink() || !$file->isDir()) {
					if(!@unlink($file->path()))
						throw new IOException("cannot remove file \"{$file->path()}\"");
					continue;
				}
				array_unshift($queue, $obj);
				$obj = dir($file->path());
			}

			if($obj !== false && ($remove_main || strlen($obj->path) != $main_length) && !@rmdir($obj->path))
				throw new IOException("cannot remove \"$obj->path\": Directory not empty");

		}
	}

	public static function cp(string $source, string $destination, int $permissions=0777, array $excludes=[]):void {
		if(!file_exists($source)) throw new IOException("Source dir does not exist");
		if(is_file($source)) {
			foreach($excludes as $exclude) if(preg_match("#$exclude#", $source)) return;
			if(!copy($source, $destination))
				throw new IOException("Failed copping file \"$source\"");
			return;
		}

		if(!file_exists($destination) || !is_dir($destination)) throw new IOException("Destination dir does not exist");

		$queue = [$source];

		while($queue) {
			$dirName = array_shift($queue);
			$dirObj = dir($dirName);

			while (($fileName = $dirObj->read()) !== false) {
				if($fileName == '.' || $fileName == '..') continue;

				$filePath = $dirObj->path . '/' . $fileName;
				$destFile = trim(preg_replace('/^' . preg_quote($source, '/') . '/', '', $dirObj->path), '/');

				@mkdir($destination . '/' . $destFile, $permissions);

				if(is_dir($filePath)) {
					$queue[] = $filePath;
					continue;
				}

				foreach($excludes as $exclude) if(preg_match("#$exclude#", $filePath)) continue 2;
				if(!copy($filePath, $destination . '/' . $destFile . '/' . $fileName))
					throw new IOException("Failed copping file \"$filePath\"");
			}
		}
	}

	public static function generateRandomString(int $length = 12): string {
		try {
			return substr(bin2hex(random_bytes(ceil($length / 2))), 0, $length);
		} catch (Exception $e) {
			// Fallback in case /dev/urandom is not readable
			return substr(base64_encode(uniqid(mt_rand(), true)), 0, $length);
		}
	}


	/**
	 * @param string|int $time
	 *
	 * @return DateTime
	 * @throws Exception
	 */
	public static function getDateTime($time='now'): DateTime {
		if(is_int($time)) $time = '@' . $time;
		return new DateTime($time, new DateTimeZone(Factory::getSettingsGeneral()->getTimeZone()));
	}

	/**
	 * @throws Exception
	 */
	public static function date(string $format, $timestamp=0): string {
		if (!$timestamp || $timestamp  == 0) $timestamp = time();
		return self::getDateTime($timestamp)->format($format);
	}

	public static function generateUniqueId(): string {
		static $inc;
		if(!$inc) $inc = 0;
		return sprintf("%08x%08x%08x", time(), floatval(microtime())*1000000, $inc++);
	}

	public static function generatePassword(int $length = 20    ): string {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+';
		$charLength = strlen($chars);
		$password = '';

		while (strlen($password) < $length) {
			$rand = random_int(0, PHP_INT_MAX);
			$password .= $chars[$rand % $charLength];
		}

		return $password;
	}


	public static function humanReadableToBytes($sSize) {
		if (is_numeric($sSize)) return $sSize;

		$iValue = intval($sSize);
		$sSuffix = strtoupper(substr($sSize, strlen($iValue)));

		switch ($sSuffix) {
			case 'PB': case 'P': $iValue *= 1125899906842624; break;
			case 'TB': case 'T': $iValue *= 1099511627776; break;
			case 'GB': case 'G': $iValue *= 1073741824; break;
			case 'MB': case 'M': $iValue *= 1048576; break;
			case 'KB': case 'K': $iValue *= 1024; break;
		}
		return $iValue;
	}

	public static function bytesToHumanReadable($bytes, $precision = 2): string {
		if (!is_numeric($bytes)) {
			// Handle the error, return an error message, or throw an exception
			return 'Invalid numeric value';
		}

		$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= (1 << (10 * $pow));

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public static function mb_basename(string $path):string {
		if(!$path) return '';
		$path = preg_replace("#/+#", "/", $path);
		if($path == '/') return '';
		$path = preg_replace("#/$#", "", $path);
		$pos = mb_strrpos(mb_convert_encoding($path, 'UTF-8'), "/");
		return $pos !== false ? mb_substr($path, $pos+1) : $path;
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public static function mb_dirname(string $path):string {
		if(!$path) return '.';
		$path = preg_replace("#/+#", "/", $path);
		if($path == '/') return '/';
		$path = preg_replace("#/$#", "", $path);
		$pos = mb_strrpos(mb_convert_encoding($path, 'UTF-8'), "/");
		return $pos !== false ? mb_substr($path, 0, $pos) : $path;
	}

	public static function generateTimeZoneList(): array {
			$timezones = DateTimeZone::listAbbreviations();
			$timezone_readable = [];
			foreach ($timezones as $timezone_area) {
				foreach ($timezone_area as $timezone) {
					// Ensure timezone_id is not null before trimming
					if (is_null($timezone['timezone_id']) || trim($timezone['timezone_id']) == '') continue;
					if (trim($timezone['timezone_id']) == 'GB') continue;
					$hours = ($timezone['offset'] / 3600);
					$offset = floor($hours); // Round the result to the nearest 0.5
					$timezone_readable[$timezone['timezone_id']] = $offset;
				}
			}
			ksort($timezone_readable);
			return $timezone_readable;
	}

	public static function IISWebConfig(): string {

		return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <security>
            <authorization>
                <remove users="*" roles="" verbs="" />
                <add accessType="Deny" users="*" />
            </authorization>
        </security>
    </system.webServer>
</configuration>
XML;

	}

	public static function has_posix_getpwuid () : bool {return function_exists('posix_getpwuid');}
	public static function has_posix_getgrgid () : bool {return function_exists('posix_getgrgid');}
	public static function has_posix_geteuid () : bool {return function_exists('posix_geteuid');}


	public static function getpwuid ($uid = null) : ?array {
		if (!$uid) return null;
		return self::has_posix_getpwuid() ? posix_getpwuid($uid) : null;
	}

	public static function getgrgid ($gid = null) : ?array {
		if (!$gid) return null;
		return self::has_posix_getgrgid() ? posix_getgrgid($gid) : null;
	}

	public static function geteuid () : ?int {
		return self::has_posix_geteuid() ? posix_geteuid() : null;
	}





	public static function secureFolder($folder): void {

		$config_file = sprintf(self::WEB_CONFIG_FILE, $folder . JetBackup::SEP);
		$htaccess_file = sprintf(self::HTACCESS_FILE, $folder . JetBackup::SEP);
		$html_file = sprintf(self::INDEX_HTML_FILE, $folder . JetBackup::SEP);

		if(!file_exists($folder)) mkdir($folder, 0700, true);
		if(!file_exists($htaccess_file)) file_put_contents($htaccess_file, "Deny from all");
		if(!file_exists($html_file)) file_put_contents($html_file, "");
		if(!file_exists($config_file)) file_put_contents($config_file, self::IISWebConfig());

	}

    /**
     * Normalizes a path format by replacing double forward slashes (//)
     *
     * @param string $path  The path to be converted.
     * @return string The converted Windows-style path.
     */
    public static function normalizePath(string $path): string
    {
        return str_replace('\\', JetBackup::SEP, $path);
    }
}