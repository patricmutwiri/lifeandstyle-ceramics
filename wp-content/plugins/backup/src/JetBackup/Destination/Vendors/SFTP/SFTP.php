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
namespace JetBackup\Destination\Vendors\SFTP;

use JetBackup\Destination\DestinationDiskUsage;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\DestinationWrapper;
use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationDiskUsage as iDestinationDiskUsage;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP as lSFTP;
use Exception;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class SFTP extends DestinationWrapper {

	const TYPE = 'SFTP';

	const PATH_FILTER = "/(((\/+|^)\.{2})+(\/+|$)|\/{2,})/";
	const FILE_NAME_FILTER = '/([ `;\\\!\$&*()|><#\'"])/';

	const MIN_PHP = '8.1.0';

	private ?lSFTP $_connection=null;

	/**
	 * @return string[]
	 */
	public function protectedFields():array { return ['password','passphrase']; }

	private static function _escapePath(string $path):string {
		return preg_replace([self::PATH_FILTER, self::FILE_NAME_FILTER], ["/","\\\\$1"], $path);
	}

	/**
	 * @param string $path
	 * @param bool $escape
	 *
	 * @return string
	 */
	public function getRealPath(string $path, bool $escape=true):string {
		$path = parent::getRealPath($path);
		return $escape ? self::_escapePath(self::_escapePath($path)) : $path;
	}

	/**
	 * @return string
	 */
	private function getHost():string { return $this->getOptions()->get('host'); }

	/**
	 * @param string $host
	 *
	 * @return void
	 */
	private function setHost(string $host):void { $this->getOptions()->set('host', $host); }

	/**
	 * @return int
	 */
	private function getPort():int { return $this->getOptions()->get('port', 22); }

	/**
	 * @param int $port
	 *
	 * @return void
	 */
	private function setPort(int $port):void { $this->getOptions()->set('port', $port); }

	/**
	 * @return string
	 */
	private function getUsername():string { return $this->getOptions()->get('username'); }

	/**
	 * @param string $username
	 *
	 * @return void
	 */
	private function setUsername(string $username):void { $this->getOptions()->set('username', $username); }

	/**
	 * @return int
	 */
	private function getTimeout():int { return $this->getOptions()->get('timeout', 60); }

	/**
	 * @param int $timeout
	 *
	 * @return void
	 */
	private function setTimeout(int $timeout):void { $this->getOptions()->set('timeout', $timeout); }

	/**
	 * @return string
	 */
	private function getPassword():string { return $this->getOptions()->get('password'); }

	/**
	 * @param string $password
	 *
	 * @return void
	 */
	private function setPassword(string $password):void { $this->getOptions()->set('password', $password); }

	/**
	 * @return string
	 */
	private function getPrivateKey():string { return $this->getOptions()->get('privatekey'); }

	/**
	 * @param string $privatekey
	 *
	 * @return void
	 */
	private function setPrivateKey(string $privatekey):void { $this->getOptions()->set('privatekey', $privatekey); }

	/**
	 * @return string
	 */
	private function getPassphrase():string { return $this->getOptions()->get('passphrase'); }

	/**
	 * @param string $passphrase
	 *
	 * @return void
	 */
	private function setPassphrase(string $passphrase):void { $this->getOptions()->set('passphrase', $passphrase); }

	/**
	 * @return int
	 */
	private function getRetries(): int { return $this->getOptions()->get('retries', 3); }

	/**
	 * @param int $retries
	 *
	 * @return void
	 */
	private function setRetries(int $retries):void { $this->getOptions()->set('retries', $retries); }

	/**
	 * @return void
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {
		if(!$this->getPath()) throw new FieldsValidationException("No path provided");
		if( !str_starts_with($this->getPath(), './')
		) throw new FieldsValidationException('Path must start with "./" example: ./backup');
		if(strlen($this->getPath()) <= 1) throw new FieldsValidationException("Path must point to a directory and can't be only \"/\"");
		if(!preg_match("/^[\/a-zA-Z0-9\-_.]+$/", $this->getPath())) throw new FieldsValidationException("Invalid path provided (Allowed characters A-Z a-z 0-9 -_. and /)");

		if(!$this->getHost()) throw new FieldsValidationException("No hostname provided");

		if(
			!preg_match('/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/', $this->getHost()) &&
			!preg_match('/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/', $this->getHost()) &&
			!filter_var($this->getHost(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
		) throw new FieldsValidationException("Invalid hostname provided");

		if(!$this->getPort()) throw new FieldsValidationException("No port provided");
		if(!$this->getUsername()) throw new FieldsValidationException("No username provided");
		if(!$this->getTimeout()) throw new FieldsValidationException("No timeout provided");

		if(!$this->getPassword() && !$this->getPrivateKey()) throw new FieldsValidationException("You must provide Password or Private Keys");
		if($this->getPassword() && $this->getPrivateKey()) throw new FieldsValidationException("You must choose either Password or Private Keys");
		if($this->getPrivateKey() && (!file_exists($this->getPrivateKey()) || !is_file($this->getPrivateKey()))) throw new FieldsValidationException("The provided Private Key not exists");

		if($this->getRetries() > 10 || $this->getRetries() < 0) throw new FieldsValidationException("Invalid retries provided. Minimum 0 and Maximum 10");
	}
	
	/**
	 * @throws ConnectionException
	 * @return void
	 */
	public function connect():void {

		if (version_compare(PHP_VERSION, self::MIN_PHP, '<')) {
			throw new ConnectionException("Minimum PHP version required is " . self::MIN_PHP . " Your current PHP version is " . PHP_VERSION);
		}

		if($this->_connection && $this->_connection->isConnected()) return;
		
		try {
			$hostname = filter_var($this->getHost(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $this->getHost() . ']' : $this->getHost();
			$connection = new lSFTP($hostname, $this->getPort(), $this->getTimeout());
			$connection->disableStatCache();

			$password = '';
			if($this->getPassword()) $password = $this->getPassword();
			elseif($this->getPrivateKey()) $password = PublicKeyLoader::load(file_get_contents($this->getPrivateKey()), $this->getPassphrase() ?: false);

			$connection->setKeepAlive(5);
			
			if(!$connection->login($this->getUsername(), $password))
				throw new ConnectionException("Invalid auth details provided");
		} catch(Exception $e) {
			throw new ConnectionException("Failed connecting. Error: " . $e->getMessage());
		}
		
		$this->_connection = $connection;
	}

	/**
	 * @return lSFTP
	 * @throws ConnectionException
	 */
	public function getConnection():lSFTP {
		$this->connect();
		return $this->_connection;
	}

	/**
	 * @return void
	 */
	public function disconnect():void {
		if(!$this->_connection) return;
		$this->_connection->disconnect();
		$this->_connection = null;
	}

	/**
	 * This function triggers every time that this destination fields changes
	 *
	 * @return void
	 */
	public function register():void {}


	/**
	 * This function triggers every time that this destination fields changes
	 * 
	 * @return void
	 */
	public function unregister():void {}

	/**
	 * @param object $data
	 * 
	 * @return void
	 */
	public function setData(object $data):void {
		if(isset($data->username)) $this->setUsername($data->username);
		if(isset($data->password)) $this->setPassword($data->password);
		if(isset($data->privatekey)) $this->setPrivateKey($data->privatekey);
		if(isset($data->passphrase)) $this->setPassphrase($data->passphrase);
		if(isset($data->host)) $this->setHost($data->host);
		if(isset($data->port)) $this->setPort(intval($data->port));
		if(isset($data->timeout)) $this->setTimeout(intval($data->timeout));
		if(isset($data->retries)) $this->setRetries(intval($data->retries));
	}

	/**
	 * @return array
	 */
	public function getData(): array {
		return $this->getOptions()->getData();
	}

	/**
	 * @param callable $callback
	 * @param string $message
	 *
	 * @return mixed
	 * @throws IOException
	 */
	public function retries(callable $callback, string $message) {
		$waittime = 333000;
		$tries = 0;

		while(true) {
			try {
				return $callback();
			} catch(Exception $e) {
				if ($tries >= $this->getRetries()) throw new IOException($e->getMessage(), $e->getCode());
				$this->getLogController()->logDebug("$message. Error: {$e->getMessage()}");
				if($waittime > 60000000) $waittime = 60000000;
				usleep($waittime);
				$waittime *= 2;
				$tries++;
				$this->getLogController()->logDebug("Retry $tries/{$this->getRetries()} $message");
			}
		}
	}

	/**
	 * @param string $directory
	 * @param ?string $data
	 *
	 * @return bool
	 * @throws IOException
	 */
	public function dirExists(string $directory, ?string $data=null): bool {
		$file = $this->getFileStat($directory);
		return $file && $file->getType() == iDestinationFile::TYPE_DIRECTORY;
	}

	/**
	 * @param string $file
	 * @param ?string $data
	 *
	 * @return bool
	 * @throws IOException
	 */
	public function fileExists(string $file, ?string $data=null): bool {
		$this->getLogController()->logDebug("[fileExists] $file");
		$file = $this->getFileStat($file);
		return $file && $file->getType() != iDestinationFile::TYPE_DIRECTORY;
	}

	/**
	 * @param string $directory
	 * @param bool $recursive
	 * @param ?string $data
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function createDir(string $directory, bool $recursive, ?string $data=null):?string {
		$this->getLogController()->logDebug("[createDir] {$this->getRealPath($directory)}");
		return $this->retries(function() use ($directory, $recursive) {
			
			try {
				if($this->dirExists($directory)) return null;
				$connection = $this->getConnection();
				if(!$connection->mkdir($this->getRealPath($directory), 0700, $recursive)) throw new IOException("Failed creating directory");
			} catch(Exception $e) {
				throw new IOException($e->getMessage());
			}

			return null;
		}, "Failed creating directory \"$directory\"");
	}

	/**
	 * @param string $directory
	 * @param ?string $data
	 *
	 * @throws IOException
	 * @return void
	 */
	public function removeDir(string $directory, ?string $data=null):void {
		$this->getLogController()->logDebug("[removeDir] $directory");
		if (!$this->dirExists($directory)) return;
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
				$this->retries(function() use ($dir_path) {
					try {
						$connection = $this->getConnection();
						if(!$connection->rmdir($this->getRealPath($dir_path))) throw new IOException("Failed deleting directory");
					} catch(Exception $e) {
						throw new IOException($e->getMessage());
					}
				}, "Failed deleting directory \"$directory\"");
			} catch(IOException $e) {
				throw new IOException("Failed deleting directory '{$this->getRealPath($directory)}'. Error: {$e->getMessage()}");
			}
		}
	}

	/**
	 * @param string $file
	 * @param ?string $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeFile(string $file, ?string $data=null): void {
		$this->getLogController()->logDebug("[removeFile] $file");
		$this->retries(function() use ($file) {
			if(!$this->fileExists($file)) return;
			try {
				$connection = $this->getConnection();
				if(!$connection->delete($this->getRealPath($file))) throw new IOException("Failed deleting file");
			} catch(Exception $e) {
				throw new IOException($e->getMessage());
			}
		}, "Failed deleting file \"$file\"");
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
		$this->getLogController()->logDebug("[copyFileToLocal] {$this->getRealPath($source)} -> $destination");
		$this->retries(function() use ($source, $destination) {
			try {
				$connection = $this->getConnection();
				if(!$connection->get($this->getRealPath($source), $destination)) throw new IOException("Failed downloading file");
			} catch(Exception $e) {
				throw new IOException("Failed copping file to local. Error: " . $e->getMessage());
			}
		}, "Failed downloading file \"$source\"");
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedDownload
	 */
	public function copyFileToLocalChunked( string $source, string $destination, ?string $data = null ): DestinationChunkedDownload {
		$this->getLogController()->logDebug("[copyFileToLocalChunked] {$this->getRealPath($source)} -> $destination");
		return new ChunkedDownload($this, $this->getRealPath($source), $destination);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param ?string $data
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function copyFileToRemote(string $source, string $destination, ?string $data=null):?string {
		$this->getLogController()->logDebug("[copyFileToRemote] $source -> {$this->getRealPath($destination)}");
		$this->createDir(dirname($destination), true);
		return $this->retries(function() use ($source, $destination) {
			try {
				$connection = $this->getConnection();
				if(!$connection->put($this->getRealPath($destination), $source, lSFTP::SOURCE_LOCAL_FILE)) throw new IOException("Failed uploading file");
			} catch(Exception $e) {
				throw new IOException("Failed copping file to remote. Error: " . $e->getMessage());
			}
			return null;
		}, "Failed uploading file $source");
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedUpload
	 */
	public function copyFileToRemoteChunked( string $source, string $destination, ?string $data = null ): DestinationChunkedUpload {
		$this->getLogController()->logDebug("[copyFileToRemoteChunked] $source -> {$this->getRealPath($destination)}");
		return new ChunkedUpload($this, $this->getRealPath($destination));
	}

	/**
	 * @param string $directory
	 * @param ?string $data
	 *
	 * @throws IOException
	 * @return DestinationDirIterator
	 */
	public function listDir(string $directory, ?string $data=null): DestinationDirIterator {
		$this->getLogController()->logDebug("[listDir] $directory");
		return new DirIterator($this, $directory);
	}

	/**
	 * @param string $filename
	 *
	 * @return iDestinationFile|null
	 * @throws IOException
	 */
	public function getFileStat(string $filename):?iDestinationFile {
		$this->getLogController()->logDebug("[getFileStat] $filename");
		return $this->retries(function() use ($filename) {
			try {
				$connection = $this->getConnection();
				if(!($details = $connection->lstat($this->getRealPath($filename)))) return null;
			} catch(Exception $e) {
				throw new IOException("Failed fetching file information for file: '$filename'. Error: " . $e->getMessage());
			}

			$details['path'] = $filename;
			$details['perms'] = $details['mode'];
			return DestinationFile::genFile($details);

		}, "Failed fetching file information for file: '$filename'");
	}

	/**
	 * @return void
	 */
	public function routineTasks():void {}

	/**
	 * @return iDestinationDiskUsage|null
	 * @throws IOException
	 */
	public function getDiskInfo():?iDestinationDiskUsage {
		
		try {
			if(!$this->dirExists('/')) $this->createDir('/', true);
		} catch(Exception $e) { return new DestinationDiskUsage(); }

		return $this->retries(function() {

			$usage = new DestinationDiskUsage();

			try {
				$connection = $this->getConnection();
				if(!($output = $connection->exec('df -P -T ' . $this->getPath()))) return $usage;
			} catch(Exception $e) {
				return $usage;
			}

			$output = explode("\n", trim($output));

			if(!isset($output[1])) return $usage;

			$match = preg_split("/\s+/", $output[1]);
			if(!sizeof($match)) return $usage;

			$blockSize = intval(preg_split("/\s+/", $output[0])[2]);
			$blockSize = $blockSize > 0 ? $blockSize : 1024;

			$usage->setUsageSpace($match[3]*$blockSize);
			$usage->setTotalSpace(($match[3] + $match[4])*$blockSize);
			$usage->setFreeSpace($usage->getTotalSpace() - $usage->getUsageSpace());
			return $usage;

		}, "Failed fetching disk usage");
	}

	/**
	 * 
	 */
	public function __destruct() {
		$this->disconnect();
	}
}