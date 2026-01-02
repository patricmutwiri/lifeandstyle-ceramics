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
namespace JetBackup\Destination\Vendors\DropBox;

use Exception;
use JetBackup\Destination\DestinationDiskUsage;
use JetBackup\Destination\DestinationWrapper;
use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationDiskUsage as iDestinationDiskUsage;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Vendors\DropBox\Client\Client;
use JetBackup\Destination\Vendors\DropBox\Client\ClientException;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\JetBackup;
use JetBackup\Web\File\FileException;
use JetBackup\Web\File\FileStream;
use SleekDB\Exceptions\InvalidArgumentException;
use stdClass;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DropBox extends DestinationWrapper {

	const TYPE = 'DropBox';

	private ?Client $_client=null;

	/**
	 * @return string[]
	 */
	public function protectedFields():array { return ['access_token','refresh_token']; }

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
	 * @return string
	 */
	private function getAccessToken():string { return $this->getOptions()->get('access_token'); }

	/**
	 * @param string $token
	 *
	 * @return void
	 */
	private function setAccessToken(string $token):void { $this->getOptions()->set('access_token', $token); }

	/**
	 * @return string
	 */
	private function getRefreshToken():string { return $this->getOptions()->get('refresh_token'); }

	/**
	 * @param string $token
	 *
	 * @return void
	 */
	private function setRefreshToken(string $token):void { $this->getOptions()->set('refresh_token', $token); }

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
	 * @param int $expiry
	 *
	 * @return void
	 */
	private function setAccessTokenExpiry(int $expiry):void { $this->getOptions()->set('access_token_expiry', $expiry); }

	/**
	 * @return int
	 */
	private function getAccessTokenExpiry():int { return $this->getOptions()->get('access_token_expiry', 0); }

	/**
	 * @return int
	 */
	public function getRetries(): int { return $this->getOptions()->get('retries', 5); }

	/**
	 * @param int $retries
	 *
	 * @return void
	 */
	public function setRetries(int $retries):void { $this->getOptions()->set('retries', $retries); }

	/**
	 * @return void
	 * @throws ConnectionException
	 */
	public function connect():void {
		try {
			if(!$this->getClient()) throw new ConnectionException("Unable to retrieve dropbox client");
			$this->_validateConnection();
		} catch(IOException $e) {
			throw new ConnectionException($e->getMessage());
		}
	}

	/**
	 * @return void
	 */
	public function disconnect():void {}

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
		if(!str_starts_with($this->getPath(), '/')) throw new FieldsValidationException("Path must start with \"/\"");
		if(!preg_match("/^[\/a-zA-Z0-9\-_.]+$/", $this->getPath())) throw new FieldsValidationException("Invalid path provided (Allowed characters A-Z a-z 0-9 _ - . and /)");
		if($this->getRetries() > 10 || $this->getRetries() < 0) throw new FieldsValidationException("Invalid retries provided. Minimum 0 and Maximum 10");
		if(!$this->getAuthorizationCode() && !$this->getAccessToken()) throw new FieldsValidationException("No access code provided");
	}

	/**
	 * @param object $data
	 *
	 * @return void
	 */
	public function setData(object $data):void {
		if(isset($data->retries)) $this->setRetries(intval($data->retries));
		if(isset($data->access_token)) $this->setAccessToken($data->access_token);
		if(isset($data->refresh_token)) $this->setRefreshToken($data->refresh_token);
		if(isset($data->token_fetch_time)) $this->setTokenFetchTime(intval($data->token_fetch_time));
		if(isset($data->access_token_expiry)) $this->setAccessTokenExpiry(intval($data->access_token_expiry));
		if(isset($data->authorization_code)) $this->setAuthorizationCode($data->authorization_code);
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
				
				foreach($args as $i => $value) $logArgs[$i] = is_string($value) || is_int($value) || is_bool($value) ? $value : '**REPLACED**';
				if($function == 'upload') $logArgs[1] = "**FILE CONTENT**";
				
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
	 * @param string|null $data
	 *
	 * @return bool
	 * @throws IOException
	 */
	public function dirExists(string $directory, ?string $data=null): bool {
		try {
			$this->getLogController()->logDebug("[dirExists] $directory");
			$meta = $this->_getObject($directory);
			return $meta->Body->{'.tag'} == 'folder';
		} catch(IOException $e) {
			if($e->getCode() >= 400 && $e->getCode() < 500) return false;
			throw $e;
		}
	}

	/**
	 * @param string $file
	 * @param string|null $data
	 *
	 * @return bool
	 * @throws IOException
	 */
	public function fileExists(string $file, ?string $data=null): bool {
		try {
			$meta = $this->_getObject($file);
			$this->getLogController()->logDebug("[fileExists] $file");
			return $meta->Body->{'.tag'} == 'file';
		} catch(IOException $e) {
			if($e->getCode() >= 400 && $e->getCode() < 500) return false;
			throw $e;
		}
	}

	/**
	 * @param string $directory
	 * @param bool $recursive
	 * @param string|null $data
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function createDir(string $directory, bool $recursive, ?string $data=null):?string {
		$directory = self::_fixPath($this->getRealPath($directory));
		$this->getLogController()->logDebug("[createDir] $directory");

		try {
			$this->client('createFolder', $directory);
		} catch(Exception $e) {
			if($e->getCode() == 409) return null;
			throw new IOException("Failed creating directory \"$directory\". Error: " . $e->getMessage() . " (Code: {$e->getCode()})");
		}
		
		return null;
	}

	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeDir(string $directory, ?string $data=null):void {
		$this->getLogController()->logDebug("[removeDir] $directory");
		$this->removeFile($directory, $data);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function copyFileToLocal(string $source, string $destination, ?string $data=null):void {

		if(is_dir($destination)) $destination .= "/".basename($source);
		$source = self::_fixPath($this->getRealPath($source));
		$this->getLogController()->logDebug("[copyFileToLocal] $source -> $destination");

		try {
			$this->client('download', $source, $destination);
		} catch(Exception $e) {
			throw new IOException("Failed downloading file \"$source\" to \"$destination\". Error: " . $e->getMessage());
		}
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedDownload
	 * @throws IOException
	 */
	public function copyFileToLocalChunked( string $source, string $destination, ?string $data = null ): DestinationChunkedDownload {
		if(is_dir($destination)) $destination .= JetBackup::SEP . basename($source);
		$this->getLogController()->logDebug("[copyFileToLocalChunked] {$this->getRealPath($source)} -> $destination");
		return new ChunkedDownload($this->getClient(), $this->getRealPath($source), $destination);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return string|null
	 * @throws IOException
	 */	
	public function copyFileToRemote(string $source, string $destination, ?string $data=null):?string {

		try {
			$file = new FileStream($source);
		} catch(FileException $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}

		if($this->dirExists($destination)) $destination .= "/" . basename($source);
		$destination = self::_fixPath($this->getRealPath($destination));
		$this->getLogController()->logDebug("[copyFileToRemote] $source -> $destination");

		try {
			$this->client('upload', $destination, $file->getSize() > 0 ? $file->read() : '');
		} catch(Exception $e) {
			throw new IOException("Failed uploading file \"$source\" to \"$destination\". Error: " . $e->getMessage());
		}

		return null;
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedUpload
	 * @throws IOException
	 */
	public function copyFileToRemoteChunked( string $source, string $destination, ?string $data = null ): DestinationChunkedUpload {
		if($this->dirExists($destination)) $destination .= "/" . basename($source);
		$destination = self::_fixPath($this->getRealPath($destination));
		$this->getLogController()->logDebug("[copyFileToRemoteChunked] $source -> $destination");

		return new ChunkedUpload($this->getClient(), $source, $destination);
	}

	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return DestinationDirIterator
	 */
	public function listDir(string $directory, ?string $data=null):DestinationDirIterator {
		$this->getLogController()->logDebug("[listDir] folder: $directory");
		return new DirIterator($this, $directory);
	}

	/**
	 * @return iDestinationDiskUsage|null
	 */
	public function getDiskInfo():?iDestinationDiskUsage {

		$usage = new DestinationDiskUsage();

		try {
			$disk = $this->client('getSpaceUsage');
		} catch(Exception $e) {
			return $usage;
		}

		$usage->setUsageSpace($disk->Body->used);
		$usage->setTotalSpace($disk->Body->allocation->allocated);
		$usage->setFreeSpace($usage->getTotalSpace() - $usage->getUsageSpace());
		return $usage;
	}

	/**
	 * @param string $file
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeFile(string $file, ?string $data=null):void {

		$file = $this->getRealPath($file);
		$this->getLogController()->logDebug("[removeFile] file: $file");

		try {
			$this->client('delete', $file);
		} catch(Exception $e) {
			if($e->getCode() >= 400 && $e->getCode() < 500) return;
			throw new IOException("Failed deleting file $file Error: " . $e->getMessage());
		}
	}

	/**
	 * @param string $path
	 *
	 * @return stdClass
	 * @throws IOException
	 */
	private function _getObject(string $path): stdClass {
		$path = self::_fixPath($this->getRealPath($path));
		$this->getLogController()->logDebug("[_getObject] Path: $path");
		try {
			return $this->client('getObjectMetadata', $path);
		} catch(Exception $e) {
			throw new IOException("Failed fetching object \"$path\". Error: " . $e->getMessage(), $e->getCode());			
		}
	}

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	private static function _fixPath(string $path):string {
		return rtrim($path, "/");
	}

	/**
	 * According to Dropbox support: To verify the validity of an access token with the Dropbox API, the recommended approach is to make an API call and examine the response.
	 * One effective method is to perform a simple call, such as /2/users/get_current_account, which has no side effects but provides relevant information for this purpose.
	 * 
	 * @return void
	 * @throws ConnectionException
	 */
	private function _validateConnection():void {
		try{
			$this->client('getAccountInfo');
		} catch (Exception $e){
			throw new ConnectionException("Unable to connect to Dropbox destination. Error: " . $e->getMessage());
		}
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
		$api->setRefreshToken($this->getRefreshToken());
		$api->setAuthorizationCode($this->getAuthorizationCode());

		try {
			$response = $api->fetchToken();
		} catch(ClientException|HttpRequestException $e) {
			$this->getLogController()->logError("Failed to fetch Access Token. Error: " . $e->getMessage());
			throw new IOException($e->getMessage(), 499);
		}

		$this->setTokenFetchTime();
		$this->setAccessToken($response->Body->access_token);
		$this->setAccessTokenExpiry($response->Body->expires_in + time() - 300);
		if(isset($response->Body->refresh_token) && $response->Body->refresh_token) $this->setRefreshToken($response->Body->refresh_token);
		$this->setAuthorizationCode('');
		$this->save();
	}

	/**
	 * 
	 */
	public function __destruct() {
		if($this->_client) $this->_client->close();
	}


	/**
	 * @param string $file
	 *
	 * @return iDestinationFile|null
	 * @throws IOException
	 */
	public function getFileStat(string $file): ?iDestinationFile {

		$this->getLogController()->logDebug("[getFileStat] Fetching stat for file $file");

		try {
			$meta = $this->_getObject($file);
		} catch (IOException $e) {
			if ($e->getCode() >= 400 && $e->getCode() < 500) return null; // File does not exist
			throw $e;
		}

		if ($meta->Body->{'.tag'} !== 'file') return null; // object is not a file, return null

		// Populate DestinationFile object
		$fileObject = new \JetBackup\Destination\DestinationFile();
		$fileObject->setName($meta->Body->name ?? basename($file));
		$fileObject->setPath($this->getRealPath(dirname($file)));
		$fileObject->setSize($meta->Body->size);
		$fileObject->setModifyTime(strtotime($meta->Body->client_modified ?? 'now'));
		$fileObject->setType( iDestinationFile::TYPE_FILE );

		return $fileObject;

	}

}