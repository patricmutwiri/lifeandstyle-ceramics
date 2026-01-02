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
namespace JetBackup\Destination\Vendors\OneDrive;

use Exception;
use JetBackup\Destination\DestinationDiskUsage;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\DestinationWrapper;
use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationDiskUsage as iDestinationDiskUsage;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Vendors\OneDrive\Client\Client;
use JetBackup\Destination\Vendors\OneDrive\Client\ClientException;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\RegistrationException;
use JetBackup\Web\File\FileChunkIterator;
use JetBackup\Web\File\FileException;
use JetBackup\Web\File\FileStream;
use SleekDB\Exceptions\InvalidArgumentException;
use stdClass;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class OneDrive extends DestinationWrapper {

	const TYPE = 'OneDrive';

	const DRIVE = 'me/drive';
	const DRIVE_ROOT = self::DRIVE . '/root';

	const SINGLE_CHUNK_UPLOAD_SIZE = 2097152;       //2MB
	const MULTI_PART_UPLOAD_CHUNK_SIZE = 4194304;   //4MB

	const HTTP_VERSIONS = [ Client::HTTP_VERSION_DEFAULT, Client::HTTP_VERSION_1_1, Client::HTTP_VERSION_2_0 ];

	private ?Client $_client=null;

	/**
	 * @return string[]
	 */
	public function protectedFields(): array { return ['access_token','refresh_token']; }

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function getRealPath(string $path): string { return rtrim(parent::getRealPath($path), '/'); }

	/**
	 * @return int
	 */
	public function getRetries(): int { return $this->getOptions()->get('retries', 3); }

	/**
	 * @param int $retries
	 *
	 * @return void
	 */
	public function setRetries(int $retries):void { $this->getOptions()->set('retries', $retries); }

	/**
	 * @return int
	 */
	public function getHTTPVersion(): int { return $this->getOptions()->get('http_version', Client::HTTP_VERSION_DEFAULT); }

	/**
	 * @param int $version
	 *
	 * @return void
	 */
	public function setHTTPVersion(int $version):void { $this->getOptions()->set('http_version', $version); }

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	public function setAuthorizationCode(string $value):void {
		if($value && $this->getAuthorizationCode() != $value) {
			$this->setAccessToken('');
			$this->setRefreshToken('');
			$this->setAccessTokenExpiry(0);
		}
		$this->getOptions()->set('authorization_code', trim($value)); 
	}

	/**
	 * @return string
	 */
	private function getAuthorizationCode():string { return $this->getOptions()->get('authorization_code'); }

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	private function setAccessToken(string $value):void { $this->getOptions()->set('access_token', trim($value)); }

	/**
	 * @return string
	 */
	private function getAccessToken():string { return $this->getOptions()->get('access_token'); }

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	private function setRefreshToken(string $value):void { $this->getOptions()->set('refresh_token', trim($value)); }

	/**
	 * @return string
	 */
	private function getRefreshToken():string { return $this->getOptions()->get('refresh_token'); }

	/**
	 * @param int|null $value
	 *
	 * @return void
	 */
	private function setTokenFetchTime(?int $value=null):void { $this->getOptions()->set('token_fetch_time', $value ?? time()); }

	/**
	 * @return int
	 */
	private function getTokenFetchTime():int { return $this->getOptions()->get('token_fetch_time', 0); }

	/**
	 * @param int|null $value
	 *
	 * @return void
	 */
	private function setAccessTokenExpiry(?int $value=null):void { $this->getOptions()->set('access_token_expiry', $value ?? time()); }

	/**
	 * @return int
	 */
	private function getAccessTokenExpiry():int { return $this->getOptions()->get('access_token_expiry', 0); }

	/**
	 * @return string
	 */
	private function getClientId():string { return $this->getOptions()->get('client_id'); }

	/**
	 * @param string $id
	 *
	 * @return void
	 */
	private function setClientId(string $id):void { $this->getOptions()->set('client_id', $id); }

	/**
	 * @return string
	 */
	private function getClientSecret():string { return $this->getOptions()->get('client_secret'); }

	/**
	 * @param string $secret
	 *
	 * @return void
	 */
	private function setClientSecret(string $secret):void { $this->getOptions()->set('client_secret', $secret); }

	/**
	 * @return void
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {
		if(!$this->getPath()) throw new FieldsValidationException("No path provided");
		if(!str_starts_with($this->getPath(), '/')) throw new FieldsValidationException("Path must start with \"/\"");
		if(!preg_match("/^[\/a-zA-Z0-9\-_.]+$/", $this->getPath())) throw new FieldsValidationException("Invalid path provided (Allowed characters A-Z a-z 0-9 -_. and /)");
		if($this->getRetries() > 10 || $this->getRetries() < 0) throw new FieldsValidationException("Invalid retries provided. Minimum 0 and Maximum 10");
		if (!in_array($this->getHTTPVersion(), self::HTTP_VERSIONS)) throw new FieldsValidationException("Invalid HTTP version provided. Available values are 0 (default), 1 (HTTP/1.1), and 2 (HTTP/2)");
		if(!($this->getAuthorizationCode()) && !$this->getAccessToken()) throw new FieldsValidationException("No authentication code provided");
	}

	/**
	 * @return void
	 * @throws ConnectionException
	 */
	public function connect():void {
		try {
			$this->client('get', self::DRIVE_ROOT . '/children');
		} catch(Exception $e) {
			throw new ConnectionException($e->getMessage());
		}
	}

	/**
	 * @return void
	 */
	public function disconnect():void {}

	/**
	 * @return void
	 * @throws RegistrationException
	 */
	public function register():void {
		try {
			$file = self::DRIVE_ROOT . ':' . $this->getRealPath('.jetbackup-writecheck');
			$this->client('putString', $file . ':/content', 'JetBackup Write Test');
			$this->client('delete', $file);
		} catch(ClientException $e) {
			throw new RegistrationException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @return void
	 */
	public function unregister():void {}

	/**
	 * @param object $data
	 *
	 * @return void
	 */
	public function setData(object $data):void {
		if(isset($data->retries)) $this->setRetries(intval($data->retries));
		if(isset($data->http_version)) $this->setHTTPVersion(intval($data->http_version));
		if(isset($data->authorization_code)) $this->setAuthorizationCode($data->authorization_code);
		if(isset($data->access_token)) $this->setAccessToken($data->access_token);
		if(isset($data->refresh_token)) $this->setRefreshToken($data->refresh_token);
		if(isset($data->token_fetch_time)) $this->setTokenFetchTime(intval($data->token_fetch_time));
		if(isset($data->access_token_expiry)) $this->setAccessTokenExpiry(intval($data->access_token_expiry));
		if(isset($data->client_id)) $this->setClientId($data->client_id);
		if(isset($data->client_secret)) $this->setClientSecret($data->client_secret);
	}

	/**
	 * @return array
	 */
	public function getData(): array {
		return $this->getOptions()->getData();
	}

	/**
	 * @return Client
	 * @throws IOException
	 */
	public function getClient():Client {
		try {
			$this->_fetchAccessToken();
		} catch(Exception $e) {
			throw new IOException($e->getMessage(), $e->getCode());
		}

		if(!$this->_client) $this->_client = new Client();
		$this->_client->setAccessToken($this->getAccessToken());
		$this->_client->setHTTPVersion($this->getHTTPVersion());
		$this->_client->setLogController($this->getLogController());
		$this->_client->setClientId($this->getClientId());
		$this->_client->setClientSecret($this->getClientSecret());
		return $this->_client;
	}

	/**
	 * @param string $function
	 * @param ...$args
	 *
	 * @return mixed
	 * @throws ClientException
	 */
	public function client(string $function, ...$args) {
		$waittime = 333000;
		$tries = 0;
		while(true) {
			try {
				return call_user_func_array([$this->getClient(), $function], $args);
			} catch(Exception $e) {
				if(($e->getCode() < 500 && !in_array($e->getCode(), [0,1,400,403,429])) || $tries >= $this->getRetries()) throw new ClientException($e->getMessage(), $e->getCode());
				$logArgs = [];
				foreach($args as $arg) {
					if(is_array($arg)) $arg = 'Array -> ' . json_encode($arg);
					$logArgs[] = $arg;
				}

				if($function == 'putString') $logArgs[1] = 'FileContents -> length: ' . strlen($logArgs[1]);
				$this->getLogController()->logDebug("Failed $function(" . implode(", ", $logArgs). "). Error: {$e->getMessage()} (Code: {$e->getCode()})");
				if($waittime > 60000000) $waittime = 60000000;
				usleep($waittime);
				$waittime *= 2;
				$tries++;
				$this->getLogController()->logDebug("Retry $tries/{$this->getRetries()} $function(" . implode(", ", $logArgs). ")");
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
		return $this->fileExists($directory, $data);
	}

	/**
	 * @param string $file
	 * @param ?string $data
	 *
	 * @return bool
	 * @throws IOException
	 */
	public function fileExists(string $file, ?string $data=null): bool {
		
		try {
			$file = $this->getRealPath($file);
			$this->getLogController()->logDebug("[fileExists] $file");
			if(!$file || $file == '/') return true;

			$this->client('get', self::DRIVE_ROOT . ':' . $file);
		} catch(ClientException $e) {
			if($e->getCode() == 404) return false;
			throw new IOException($e->getMessage());
		}
		return true;
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

		$directory = $this->getRealPath($directory);
		if(!$directory || $directory == '/') return null;
		$this->getLogController()->logDebug("[createDir] $directory");
		$dirname = dirname($directory);
		if($dirname == '/') $dirname = '';


		try {
			$this->client('post',self::DRIVE_ROOT . ($dirname ? ":$dirname:" : '') . '/children', [], json_encode([
				'name'          => basename($directory),
				'folder'        => new stdClass(),
			]), 'application/json');
		} catch(ClientException $e) {
			if($e->getCode() == 409 && $e->getMessage() == 'Name already exists') return null;
			throw new IOException($e->getMessage());
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
		$this->getLogController()->logDebug("[removeDir] $directory");
		$this->removeFile($directory, $data);
	}

	/**
	 * @param string $file
	 * @param ?string $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeFile(string $file, ?string $data=null):void {
		try	{
			$this->getLogController()->logDebug("[removeFile] {$this->getRealPath($file)}");
			$file = $this->getRealPath($file);
			if(!$file || $file == '/') return;
			$this->client('delete', self::DRIVE_ROOT . ':' . $file);
		} catch(ClientException $e) {
			throw new IOException($e->getMessage());
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

		try {
			$this->getLogController()->logDebug("[copyFileToLocal] {$this->getRealPath($source)} -> $destination");

			$this->client('getObject', self::DRIVE_ROOT . ':' . $this->getRealPath($source) . ':/content', $destination);
		} catch(ClientException $e) {
			throw new IOException($e->getMessage());
		}
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
		try {
			$file = new FileStream($source);
		} catch(FileException $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}

		// if less than 2MB, send it in one chunk
		if($file->getSize() < self::SINGLE_CHUNK_UPLOAD_SIZE) {
			try {
				$this->client('putString', self::DRIVE_ROOT . ':' . $this->getRealPath($destination) . ':/content', $file->getSize() > 0 ? $file->read() : '');
			} catch(ClientException $e) {
				throw new IOException($e->getMessage());
			}
		} else {

			try {
				$response = $this->client('post', self::DRIVE_ROOT . ':' . $this->getRealPath($destination) . ':/createUploadSession', [], '', 'application/json');
			} catch(ClientException $e) {
				throw new IOException($e->getMessage());
			}

			$iterator = new FileChunkIterator($file, self::MULTI_PART_UPLOAD_CHUNK_SIZE);
			if(!$iterator->hasNext()) throw new IOException("Unable to get file chunks");

			while($iterator->hasNext()) {
				$chunk = $iterator->next();
				
				try {
					$this->client('putChunk', $chunk, $response->Body->uploadUrl);
				} catch(ClientException $e) {
					throw new IOException($e->getMessage());
				}
			}
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
		$this->getLogController()->logDebug("[copyFileToRemoteChunked] $source -> {$this->getRealPath($destination)}");
		return new ChunkedUpload($this, $this->getRealPath($destination));
	}

	/**
	 * @param string $directory
	 * @param ?string $data
	 *
	 * @return DestinationDirIterator
	 * @throws IOException
	 */
	public function listDir(string $directory, ?string $data=null):DestinationDirIterator {
		$this->getLogController()->logDebug("[listDir] $directory");
		return new DirIterator($this, $directory);
	}

	/**
	 * @return iDestinationDiskUsage|null
	 */
	public function getDiskInfo():?iDestinationDiskUsage {

		$usage = new DestinationDiskUsage();

		try {
			$disk = $this->client('get', self::DRIVE);
		} catch(Exception $e) {
			return $usage; 
		}
		
		$disk = $disk->Body->quota;
		
		$usage->setUsageSpace($disk->used);
		$usage->setTotalSpace($disk->total);
		$usage->setFreeSpace($disk->remaining);
		return $usage;
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	private function _fetchAccessToken():void {

		// if now the access token isn't expired that means that other thread already fetched new access token, exit as there is nothing to do
		if($this->getAccessTokenExpiry() > time()) return;

		$api = new Client();
		$api->setHTTPVersion($this->getHTTPVersion());
		$api->setRefreshToken($this->getRefreshToken());
		$api->setAuthorizationCode($this->getAuthorizationCode());
		$api->setClientId($this->getClientId());
		$api->setClientSecret($this->getClientSecret());

		/*
		if($this->getAuthId()) {
			$auth = new DestinationAuth($this->getAuthId());
			if(!$auth->getId()) throw new IOException("Invalid authentication id provided ({$this->getAuthId()})");
			$auth->remove();

			if(($auth->getCreated()+600) < time()) throw new IOException("Authentication time is up, Please try again");

			$api->setCodeVerifier($auth->getPKCE()->verifier);
		}
		*/
		
		try {
			$response = $api->fetchToken();
		} catch(ClientException|HttpRequestException $e) {
			$this->getLogController()->logError("Failed to fetch Access Token. Error: " . $e->getMessage());
			throw new IOException($e->getMessage(), 499);
		}

		$this->setTokenFetchTime();
		$this->setAccessToken($response->Body->access_token);
		$this->setAccessTokenExpiry($response->Body->expires_in + time() - 300);
		$this->setRefreshToken($response->Body->refresh_token);
		$this->setAuthorizationCode('');
		//$this->setAuthId('');
		$this->save();
	}

	/**
	 * 
	 */
	public function __destruct() {
		if($this->_client) $this->_client->close();
	}

	/**
	 * @throws IOException
	 */
	public function getFileStat(string $file): ?iDestinationFile {
		try {
			$this->getLogController()->logDebug("[getFileStat] {$this->getRealPath($file)}");

			$realPath = $this->getRealPath($file);
			if (!$realPath || $realPath == '/') {return null;}

			$response = $this->client('get', self::DRIVE_ROOT . ':' . $realPath);
			$metadata = $response->Body;

			$fileObject = new DestinationFile();
			$fileObject->setName($metadata->name ?? basename($realPath));
			$fileObject->setPath(dirname($realPath));
			$fileObject->setSize($metadata->size ?? 0); // Set size to 0 if not present
			$fileObject->setModifyTime(strtotime($metadata->lastModifiedDateTime ?? 'now'));
			$fileObject->setType( isset($metadata->folder) ? iDestinationFile::TYPE_DIRECTORY : iDestinationFile::TYPE_FILE );

			return $fileObject;

		} catch (Exception $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}

}