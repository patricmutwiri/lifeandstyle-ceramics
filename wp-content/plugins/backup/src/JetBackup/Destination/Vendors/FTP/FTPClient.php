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
namespace JetBackup\Destination\Vendors\FTP;

use Exception;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Filesystem\File;
use JetBackup\Web\File\FileChunk;
use JetBackup\Web\File\FileDownload;
use JetBackup\Web\File\FileException;
use JetBackup\Web\File\FileStream;
use JetBackup\Web\JetHttp;
use JetBackup\Wordpress\Wordpress;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class FTPClient {

	const SERVER_TYPE_PURE_FTPD = 'Pure-FTPd'; // ---------- Welcome to Pure-FTPd [privsep] [TLS] ----------
	const SERVER_TYPE_PRO_FTPD = 'ProFTPD'; // ---------- Welcome to Pure-FTPd [privsep] [TLS] ----------
	const SERVER_TYPE_VSFTPD = 'vsFTPd'; // (vsFTPd 3.0.2)
	const SERVER_TYPE_UNKNOWN = '';

	const SERVER_TYPES = [self::SERVER_TYPE_PURE_FTPD,self::SERVER_TYPE_PRO_FTPD,self::SERVER_TYPE_VSFTPD];

	const CODE_TRUNCATED = 1000;

	private string $_hostname;
	private int $_port;
	private string $_auth;
	private bool $_passive;
	private bool $_secure;
	private int $_timeout;
	private int $_prefer_ip;
	private ?JetHttp $_http=null;
	private array $_mkdir_dirs=[];
	private bool $_ignore_self_signed;

	/**
	 * @param string $hostname
	 * @param string $username
	 * @param string $password
	 * @param int $port
	 * @param bool $passivemode
	 * @param bool $securessl
	 * @param int $timeout
	 * @param int $prefer_ip
	 * @param bool $ignore_self_signed
	 */
	public function __construct(string $hostname, string $username, string $password, int $port=21, bool $passivemode=false, bool $securessl=false, int $timeout=30, int $prefer_ip = 0, bool $ignore_self_signed = true) {
		$this->_hostname = $hostname;
		$this->_port = $port;
		$this->_auth = $username.':'.$password;
		$this->_passive = $passivemode;
		$this->_secure = $securessl;
		$this->_timeout = $timeout;
		$this->_prefer_ip = $prefer_ip;
		$this->_ignore_self_signed = $ignore_self_signed;
	}

	/**
	 * @param string $perms
	 *
	 * @return int
	 */
	private static function _calcPermissions(string $perms):int {
		$permissions = 0;

		if($perms[0] == 's') $permissions |= 0140000;
		if($perms[0] == 'l') $permissions |= 0120000;
		if($perms[0] == '-') $permissions |= 0100000;
		if($perms[0] == 'b') $permissions |= 0060000;
		if($perms[0] == 'd') $permissions |= 0040000;
		if($perms[0] == 'c') $permissions |= 0020000;
		if($perms[0] == 'p') $permissions |= 0010000;

		if($perms[1] != '-') $permissions |= 00400;
		if($perms[2] != '-') $permissions |= 00200;
		if($perms[3] == 'x' || $perms[3] == 's' || $perms[3] == 't') $permissions |= 00100;
		if($perms[3] != '-' && $perms[3] != 'x') $permissions |= 04000;

		if($perms[4] != '-') $permissions |= 00040;
		if($perms[5] != '-') $permissions |= 00020;
		if($perms[6] == 'x' || $perms[6] == 's' || $perms[6] == 't') $permissions |= 00010;
		if($perms[6] != '-' && $perms[6] != 'x') $permissions |= 02000;

		if($perms[7] != '-') $permissions |= 00004;
		if($perms[8] != '-') $permissions |= 00002;
		if($perms[9] == 'x' || $perms[9] == 's' || $perms[9] == 't') $permissions |= 00001;
		if($perms[9] != '-' && $perms[9] != 'x') $permissions |= 01000;

		return $permissions;
	}

	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 * @throws FileException
	 * @throws Exception
	 */
	public function upload(string $source, string $destination):void {

		if(!file_exists($source) || !is_file($source)) throw new Exception("Source file not exists");
		
		$this->_init();

		try {
			$file = new FileStream($source);
			$this->_http
				->addOption(CURLOPT_FTP_FILEMETHOD, CURLFTPMETHOD_MULTICWD)
				->addOption(CURLOPT_FTP_CREATE_MISSING_DIRS, CURLFTP_CREATE_DIR_RETRY)
				->upload($this->_getURL($destination), $file);
		} catch(HttpRequestException $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
	}

	public function uploadChunk(FileChunk $chunk, $destination):void {

		$this->_init();

		try {
			$this->_http
				->addOption(CURLOPT_FTP_FILEMETHOD, CURLFTPMETHOD_MULTICWD)
				->addOption(CURLOPT_FTP_CREATE_MISSING_DIRS, CURLFTP_CREATE_DIR_RETRY)
				->addOption(CURLOPT_APPEND, 1)
				->uploadChunk($this->_getURL($destination), $chunk);
		} catch(HttpRequestException $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * @param string $source
	 * @param string $destination
	 *
	 * @return void
	 * @throws Exception
	 */
	public function download(string $source, string $destination, int $start=0, int $end=0):void {

		$file = new File($destination);
		if($file->exists() && $file->isDir()) $destination .= '/' . basename($source);

		$file = new File(dirname($destination));
		if(!$file->exists() || (!$file->isDir() || $file->isLink())) throw new Exception("Destination folder not found");

		$this->_init();

		try {
			$fileDownload = new FileDownload($destination);
			if($start || $end) $this->_http->addOption(CURLOPT_RANGE, $start . '-' . $end);
			$this->_http->download($this->_getURL($source), $fileDownload);
		} catch(HttpRequestException $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * @param string $file
	 *
	 * @return void
	 * @throws Exception
	 */
	public function delete(string $file):void {
		$this->_quote("DELE $file");
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 */
	private function _getURL(string $url):string {
		$url = preg_replace("/\/+/", "/", "/$url");
		return "ftp://$this->_hostname$url";
	}

	public function getFileSize(string $file) {
		$this->_init();

		try {
			$response = $this->_http
				->setReturnTransfer()
				->setMethod(JetHttp::METHOD_HEAD)
				->exec($this->_getURL($file));

			return $response->getHeaders()->getHeader('content-length') ?: 0;
		} catch(HttpRequestException $e) {
			if($e->getCode() == 78 || $e->getCode() == 9) return false;
			throw $e;
		}

	}
	
	/**
	 * @param string $file
	 *
	 * @return bool
	 * @throws HttpRequestException
	 */
	public function fileExists(string $file): bool {
		$this->_init();

		try {
			$response = $this->_http
				->setReturnTransfer()
				->setMethod(JetHttp::METHOD_HEAD)
				->exec($this->_getURL($file));

			// We find that in some FTP configurations, we are getting positive responses even when the file does not exist.
			// For those false-positive responses, we have noticed that the response does not contain a 'content-length' header.
			return $response->getHeaders()->getHeader('content-length') !== null;
		} catch(HttpRequestException $e) {
			if($e->getCode() == 78 || $e->getCode() == 9) return false;
			throw $e;
		}
	}

	/**
	 * @param string $directory
	 *
	 * @return void
	 * @throws Exception
	 */
	public function mkdir(string $directory):void {
		$directory = trim(preg_replace("#/+#", '/', $directory), '/');
		$parts = explode('/', $directory);

		$path = '/';
		foreach($parts as $part) {

			if(in_array($path . $part . "/", $this->_mkdir_dirs)) {
				$path .= $part . '/';
				continue;
			}
			$list = $this->listDir($path);

			foreach($list as $item) {
				if($item->getName() == $part) {
					$path .= $part . '/';
					$this->_mkdir_dirs[] = $path;
					continue 2;
				}
			}

			$this->_quote("MKD $path$part/");

			/**
			 * Wrapping the chmod with try-catch as it's not always supported and might trigger error
			 * Not supported in:
			 *  - Microsoft FTP & vsFTPd
			 *  - ProFTPD Works if AllowChmod is enabled
			 */
			try {
				$this->_quote("SITE CHMOD 0700 $path$part/");
			} catch (Exception $e) {
			    // todo - log events
			}


			$path .= $part . '/';
			$this->_mkdir_dirs[] = $path;
		}
	}

	/**
	 * @param string $directory
	 *
	 * @return void
	 * @throws Exception
	 */
	public function rmdir(string $directory):void {
		$this->_quote("RMD $directory");
	}

	/**
	 * @param string $directory
	 *
	 * @return void
	 * @throws Exception
	 */
	public function chdir(string $directory):void {
		$this->_quote("CWD $directory");
	}

	/**
	 * @param string $cmd
	 *
	 * @return void
	 * @throws Exception
	 */
	private function _quote(string $cmd):void {
		$this->_init();

		try {
			$this->_http
				->setReturnTransfer()
				->addOption(CURLOPT_QUOTE, [$cmd])
				->exec($this->_getURL('/'));
		} catch(HttpRequestException $e) {
			throw new Exception($e->getMessage(), $e->getCode());
		}
	}

	/**
	 * @param string $dir
	 *
	 * @return iDestinationFile[]
	 * @throws HttpRequestException
	 * @throws Exception
	 */
	public function listDir(string $dir): array {

		$dir = !$dir? '/': preg_replace("#/+#", "/", "$dir/");

		$this->_init();

		$response = $this->_http
			->setReturnTransfer()
			->addOption(CURLOPT_CUSTOMREQUEST, 'LIST -al')
			->exec($this->_getURL($dir));
		
		$headers = $response->getHeaders()->getHeader('ftp');

		$server_type = self::SERVER_TYPE_UNKNOWN;

		foreach($headers as $line) {

			if(!$server_type) {
				foreach(self::SERVER_TYPES as $type) {
					if(Wordpress::strContains($line, $type)) {
						$server_type = $type;
						break;
					}
				}
			}

			if(Wordpress::strContains($line, "226 Output truncated"))
				throw new Exception("The LIST command output is truncated by the server, please change the FTP service configuration to a allow higher number of files to be displayed. Server Message: ". substr($line, 6), self::CODE_TRUNCATED);
		}

		$output = [];
		
		switch($server_type) {
			case self::SERVER_TYPE_UNKNOWN:
			case self::SERVER_TYPE_PRO_FTPD:
			case self::SERVER_TYPE_PURE_FTPD:
			case self::SERVER_TYPE_VSFTPD:

				$line = strtok($response->getBody(), "\r\n");
				while ($line !== false) {
					$parts = explode(' ', preg_replace("/\s+/", ' ', $line));
					$line = strtok("\r\n");

					list($perms,,$owner,$group,$size,$month,$day,$year) = $parts;

					$filename = $parts[count($parts)-1];
					$link = '';
					if($parts[count($parts)-2] == '->') {
						$filename = $parts[count($parts)-3];
						$link = $parts[count($parts)-1];
					}

					if($filename == '.' || $filename == '..') continue;
					
					$file = DestinationFile::genFile([
						'perms'     => self::_calcPermissions($perms),
						'size'      => $size,
						'mtime'     => strtotime($month . ' ' . $day . ' ' . $year),
						'user'      => $owner,
						'group'     => $group,
						'path'      => $dir . $filename,
						'link'      => $link,
					]);

					$output[] = $file;
				}

				break;
		}

		return $output;
	}

	/**
	 * @return void
	 */
	private function _init():void {

		if(!$this->_http) $this->_http = JetHttp::request();
		$this->_http->reset();

		$this->_http->addOption(CURLOPT_FTPPORT, $this->_passive ? null : '-')
			->setAuth(null, $this->_auth, CURLAUTH_BASIC)
			->setPort($this->_port)
			->setFollowLocation()
			->setConnectionTimeout($this->_timeout);
		
		if($this->_secure) {
			if(defined('CURL_SSLVERSION_MAX_TLSv1_2')) $this->_http->addOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_MAX_TLSv1_2);
			$this->_http->addOption(CURLOPT_USE_SSL, CURLUSESSL_ALL);

			if($this->_ignore_self_signed) {
				$this->_http->addOption(CURLOPT_USE_SSL, CURLUSESSL_ALL)
				            ->addOption(CURLOPT_SSL_VERIFYPEER, false) // Disable certificate verification
				            ->addOption(CURLOPT_SSL_VERIFYHOST, 0);    // Disable hostname verification
			}

		}

		if($this->_prefer_ip)
			$this->_http->addOption(CURLOPT_IPRESOLVE, $this->_prefer_ip == 4? CURL_IPRESOLVE_V4: CURL_IPRESOLVE_V6);
	}

	/**
	 * @return void
	 */
	public function close():void {
		$this->_http = null;
	}
}
