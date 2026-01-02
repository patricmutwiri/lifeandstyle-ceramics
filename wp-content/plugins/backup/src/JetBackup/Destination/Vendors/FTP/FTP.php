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
use JetBackup\Destination\DestinationWrapper;
use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationDiskUsage;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class FTP extends DestinationWrapper {

	const TYPE = 'FTP';

	private ?FTPClient $_connection=null;
	
	/**
	 * @return string[]
	 */
	public function protectedFields():array { return ['password']; }

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function getRealPath(string $path): string { return rtrim(parent::getRealPath($path), '/'); }

	/**
	 * @return string
	 */
	public function getServer():string { return $this->getOptions()->get('server'); }

	/**
	 * @param string $server
	 *
	 * @return void
	 */
	private function setServer(string $server):void { $this->getOptions()->set('server', trim($server)); }

	/**
	 * @return int
	 */
	public function getPort():int { return (int) $this->getOptions()->get('port', 21); }

	/**
	 * @param int $port
	 *
	 * @return void
	 */
	private function setPort(int $port):void { $this->getOptions()->set('port', $port); }

	/**
	 * @return string
	 */
	public function getUsername():string { return $this->getOptions()->get('username'); }

	/**
	 * @param string $username
	 *
	 * @return void
	 */
	private function setUsername(string $username):void { $this->getOptions()->set('username', $username); }

	/**
	 * @return string
	 */
	public function getPassword():string { return $this->getOptions()->get('password'); }

	/**
	 * @param string $password
	 *
	 * @return void
	 */
	private function setPassword(string $password):void { $this->getOptions()->set('password', $password); }

	/**
	 * @return int
	 */
	public function getConnectionTimeout():int { return (int) $this->getOptions()->get('timeout', 30); }

	/**
	 * @param int $timeout
	 *
	 * @return void
	 */
	private function setConnectionTimeout(int $timeout):void { $this->getOptions()->set('timeout', $timeout); }

	/**
	 * @return int
	 */
	public function getRetries():int { return (int) $this->getOptions()->get('retries', 0); }

	/**
	 * @param int $retries
	 *
	 * @return void
	 */
	private function setRetries(int $retries):void { $this->getOptions()->set('retries', $retries); }

	/**
	 * @return bool
	 */
	public function getSecureSSL():bool { return !!$this->getOptions()->get('ssl'); }
	public function getIgnoreSelfSigned():bool { return !!$this->getOptions()->get('ignore_self_signed'); }

	/**
	 * @param bool $secure
	 *
	 * @return void
	 */
	private function setSecureSSL(bool $secure):void { $this->getOptions()->set('ssl', $secure); }

	private function setIgnoreSelfSigned(bool $ignore_self_signed):void { $this->getOptions()->set('ignore_self_signed', $ignore_self_signed); }

	/**
	 * @return bool
	 */
	public function getPassive():bool { return !!$this->getOptions()->get('passive_mode'); }

	/**
	 * @param bool $passive
	 *
	 * @return void
	 */
	private function setPassive(bool $passive):void { $this->getOptions()->set('passive_mode', $passive); }

	/**
	 * @return int
	 */
	public function getPreferIp():int { return (int) $this->getOptions()->get('prefer_ip', 4); }

	/**
	 * @param int $prefer_ip
	 *
	 * @return void
	 */
	private function setPreferIp(int $prefer_ip):void { $this->getOptions()->set('prefer_ip', $prefer_ip); }

	/**
	 * @return void
	 * @throws ConnectionException
	 */
	public function connect():void {

		if($this->_connection) return;

		$connection = new FTPClient(
			$this->getServer(),
			$this->getUsername(),
			$this->getPassword(),
			$this->getPort(),
			$this->getPassive(),
			$this->getSecureSSL(),
			$this->getConnectionTimeout(),
			$this->getPreferIp(),
			$this->getIgnoreSelfSigned()
		);

		try {
			$connection->chdir('/');
		} catch(Exception $e) {
			throw new ConnectionException("Failed connecting FTP server {$this->getServer()} on port {$this->getPort()}. Error: {$e->getMessage()}");
		}
		
		$this->_connection = $connection;
	}

	/**
	 * @param string $function
	 * @param ...$args
	 *
	 * @return mixed
	 * @throws IOException
	 */
	public function _client(string $function, ...$args) {
		$waittime = 333000;
		$tries = 0;

		while(true) {
			try {
				$this->connect();
				return $this->_connection->{$function}(...$args);
			} catch(Exception $e) {
				if($tries >= $this->getRetries() || $e->getCode() == 9 || $e->getCode() == FTPClient::CODE_TRUNCATED) throw new IOException($e->getMessage(), $e->getCode());
				if ($waittime > 60000000) $waittime = 60000000;
				usleep($waittime);
				$waittime *= 2;
				$tries++;
				$this->getLogController()->logDebug("Retry $tries/{$this->getRetries()} {$e->getMessage()}");
			}
		}
	}

	/**
	 * @return void
	 */
	public function disconnect():void {
		if(!$this->_connection) return;
		$this->_connection->close();
		$this->_connection = null;
	}

	/**
	 * @return void
	 */
	public function register():void {}

	/**
	 * @return void
	 */
	public function unregister():void {}

	/**
	 * @return void
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {
		if(!$this->getPath()) throw new FieldsValidationException("No path provided");
		if(str_starts_with($this->getPath(), './')) throw new FieldsValidationException("Path cannot start with \"./\"");
		if(!preg_match("/^[\/a-zA-Z0-9\-_.]+$/", $this->getPath())) throw new FieldsValidationException("Invalid path provided (Allowed characters A-Z a-z 0-9 _ - . and /)");

		if(!$this->getServer()) throw new FieldsValidationException("No server address provided");
		if(!$this->getPort()) throw new FieldsValidationException("No port provided");
		if(!$this->getUsername()) throw new FieldsValidationException("No username provided");
		if(!$this->getPassword()) throw new FieldsValidationException("No password provided");
		if($this->getConnectionTimeout() < 10 || $this->getConnectionTimeout() > 300) throw new FieldsValidationException("Invalid connection timeout provided. valid value is between 10 to 300 seconds");
		if($this->getRetries() < 0 || $this->getRetries() > 10) throw new FieldsValidationException("Invalid connection retries provided. valid value is between 0 to 10 retries");
		if(!in_array($this->getPreferIp(), [0, 4, 6])) throw new FieldsValidationException("Invalid Prefer IP version provided. valid values are: 0 - for default route, 4 - for using IPv4, and 6 - for using IPv6");
	}

	/**
	 * @param object $data
	 *
	 * @return void
	 */
	public function setData(object $data):void {
		if(isset($data->server)) $this->setServer($data->server);
		if(isset($data->port)) $this->setPort($data->port);
		if(isset($data->username)) $this->setUsername($data->username);
		if(isset($data->password)) $this->setPassword($data->password);
		if(isset($data->timeout)) $this->setConnectionTimeout($data->timeout);
		if(isset($data->retries)) $this->setRetries($data->retries);
		if(isset($data->ssl)) $this->setSecureSSL($data->ssl);
		if(isset($data->ignore_self_signed)) $this->setIgnoreSelfSigned($data->ignore_self_signed);
		if(isset($data->passive_mode)) $this->setPassive($data->passive_mode);
		if(isset($data->prefer_ip)) $this->setPreferIp($data->prefer_ip);
	}

	/**
	 * @return array
	 */
	public function getData(): array {
		return $this->getOptions()->getData();
	}

	/**
	 * @param string $directory
	 * @param ?string $data
	 *
	 * @return bool
	 * @throws IOException
	 */
	public function dirExists(string $directory, ?string $data=null): bool {
		$this->getLogController()->logDebug("[dirExists] Checking dirExists for $directory");

		// Check the cache before listing the directory
		if(preg_replace("#/+#", '/', $directory) == '/') return true;

		$dir = $this->listDir(dirname($directory));
		while($dir->hasNext()) {
			$details = $dir->getNext();
			if($details->getType() == iDestinationFile::TYPE_DIRECTORY && $details->getName() == basename($directory)) return true;
		}
		return false;
	}

	/**
	 * @param string $file
	 * @param ?string $data
	 *
	 * @return bool
	 * @throws IOException
	 */
	public function fileExists(string $file, ?string $data=null): bool {
		$this->getLogController()->logDebug("[fileExists] Checking file exists for $file");
		return $this->_client('fileExists', $this->getRealPath($file));
	}

	/**
	 * @param string $directory
	 * @param bool $recursive
	 * @param ?string $data
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function createDir(string $directory, bool $recursive, ?string $data=null):? string {
		try {
			$this->getLogController()->logDebug("[createDir] Creating folder $directory");
			$this->_client('mkdir', $this->getRealPath($directory));
		} catch(IOException $e) {
			throw new IOException("Failed creating dir '{$this->getRealPath($directory)}'. Error: {$e->getMessage()}");
		}

		return null;
	}

	/**
	 * @param string $directory
	 * @param ?string $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeDir(string $directory, ?string $data=null):void {

		$this->getLogController()->logDebug("[removeDir] Removing $directory");
		if(!$this->dirExists($directory)) return;

		$list = $this->listDir($directory);

		$stack = [];
		$stack[] = [$list, $directory];

		while (!empty($stack)) {
			list($dir_iterator, $dir_path) = array_pop($stack);

			while ($dir_iterator->hasNext()) {
				$file = $dir_iterator->getNext();
				$filename = $file->getName();
				$path = sprintf("%s/%s", $dir_path, $filename);
				if ($file->getType() == iDestinationFile::TYPE_DIRECTORY) {
					// Add the current dir iterator to the stack and continue with the new dir iterator.
					$stack[] = [$dir_iterator, $dir_path];
					// Update the 'dir_path' to the new path and use 'listDir' to get a new dir iterator.
					$dir_path = $path;
					$dir_iterator = $this->listDir($dir_path);
				}
				else $this->removeFile($path);
			}
			try {
				$this->_client('rmdir', $this->getRealPath($dir_path));
			} catch(IOException $e) {
				throw new IOException("Failed deleting dir '{$this->getRealPath($dir_path)}'. Error: {$e->getMessage()}");
			}
		}
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param ?string $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function copyFileToLocal(string $source, string $destination, ?string $data=null):void {
		$this->getLogController()->logDebug("[copyFileToLocal] Checking destination: $destination");
		if (file_exists($destination)) {
			$this->getLogController()->logDebug("[copyFileToLocal] Destination exists already");
			// The destination is a directory to copy the file to (using the original file name).
			if (is_dir($destination)) {
				$this->getLogController()->logDebug("[copyFileToLocal] Destination is a directory");
				$destination .= "/" . basename($source);
			}
		} else if(!file_exists(dirname($destination)) &&
		          !@mkdir(dirname($destination), 0755, true))
			throw new IOException("Failed creating local dir '" . dirname($destination) . "'");

		if(!is_dir(dirname($destination))) throw new IOException("Local path '" . dirname($destination) . "' isn't a directory");

		try {
			$this->getLogController()->logDebug("[copyFileToLocal] Starting download {$this->getRealPath($source)} -> $destination");
			$this->_client('download', $this->getRealPath($source), $destination);
		} catch(IOException $e) {
			throw new IOException("Failed downloading file '$source'. Error: {$e->getMessage()}");
		}
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedDownload
	 * @throws ConnectionException
	 */
	public function copyFileToLocalChunked( string $source, string $destination, ?string $data = null ): DestinationChunkedDownload {
		$this->connect();
		$source = $this->getRealPath($source);
		$this->getLogController()->logDebug("[copyFileToRemote] $source -> $destination");
		return new ChunkedDownload($this, $source, $destination);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param ?string $data
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function copyFileToRemote(string $source, string $destination, ?string $data=null):? string {
		try {
			$destination = $this->getRealPath($destination);
			$this->getLogController()->logDebug("[copyFileToRemote] $source -> $destination");
			$this->_client('upload', $source, $destination);
		} catch(IOException $e) {
			throw new IOException("Failed uploading file '$source' to '{$destination}'. Error: {$e->getMessage()}");
		}
		return null;
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedUpload
	 */
	public function copyFileToRemoteChunked( string $source, string $destination, ?string $data = null ): DestinationChunkedUpload {
		$this->getLogController()->logDebug("[copyFileToRemoteChunked] $source -> $destination");
		return new ChunkedUpload($this, $this->getRealPath($destination));
	}

	/**
	 * @param string $directory
	 * @param ?string $data
	 *
	 * @return DestinationDirIterator
	 * @throws IOException
	 */
	public function listDir(string $directory, ?string $data=null): DestinationDirIterator {
		$this->getLogController()->logDebug("[listDir] $directory");
		return new DirIterator($this, $directory);
	}

	/**
	 * @param string $directory
	 *
	 * @return iDestinationFile[]
	 * @throws IOException
	 */
	public function _listDir(string $directory): array {
		$directory = $this->getRealPath($directory);
		$this->getLogController()->logDebug("[_listDir] $directory");
		return $this->_client('listDir', $directory);
	}

	/**
	 * @return DestinationDiskUsage|null
	 */
	public function getDiskInfo():?DestinationDiskUsage { return null; }

	/**
	 * @param string $file
	 * @param ?string $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeFile(string $file, ?string $data=null):void {
		try {
			$file = $this->getRealPath($file);
			$this->getLogController()->logDebug("[removeFile] $file");

			$this->_client('delete', $file);
		} catch(IOException $e) {
			throw new IOException("Failed deleting file '$file'. Error: {$e->getMessage()}");
		}
	}

	/**
	 * @param string $file
	 *
	 * @return iDestinationFile|null
	 * @throws IOException
	 */
	public function getFileStat(string $file):?iDestinationFile {
		$this->getLogController()->logDebug("[getFileStat] $file");
		$dir = $this->listDir(dirname($file));

		while($dir->hasNext()) {
			$details = $dir->getNext();
			if($details->getType() == DestinationFile::TYPE_FILE && $details->getName() == basename($file)) return $details;
		}

		return null;

	}

	/**
	 * 
	 */
	public function __destruct() {
		$this->disconnect();
	}
}