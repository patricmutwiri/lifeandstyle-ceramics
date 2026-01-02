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
namespace JetBackup\Destination\Vendors\GoogleDrive;

use Exception;
use JetBackup\Destination\DestinationDiskUsage;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\DestinationWrapper;
use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationDiskUsage as iDestinationDiskUsage;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Vendors\GoogleDrive\Client\Client;
use JetBackup\Destination\Vendors\GoogleDrive\Client\ClientException;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\RegistrationException;
use JetBackup\Log\LogController;
use JetBackup\Web\File\FileException;
use SleekDB\Exceptions\InvalidArgumentException;
use stdClass;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class GoogleDrive extends DestinationWrapper {

	const TYPE = 'GoogleDrive';

	private ?Client $_client = null;
	private Cache $_cache;

	/**
	 * @param int $chunk_size
	 * @param LogController|null $logController
	 * @param string|null $name
	 * @param int $id
	 */
	public function __construct(int $chunk_size, string $path, ?LogController $logController=null, ?string $name=null, int $id=0) {
		parent::__construct($chunk_size, $path, $logController, $name, $id);
		$this->_cache = new Cache();
	}

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
	public function setRetries(int $retries): void { $this->getOptions()->set('retries', $retries); }

	/**
	 * @return string
	 */
	public function getAccessCode(): string { return $this->getOptions()->get('access_code'); }

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
	 * @param string $code
	 *
	 * @return void
	 */
	public function setAccessCode(string $code):void {
		if($this->getAccessCode() && $code && $this->getAccessCode() != $code) $this->setAccessToken('');
		$this->getOptions()->set('access_code', $code);
	}

	/**
	 * @return array|null
	 */
	public function getAccessToken():?array {
		$token = $this->getOptions()->get('token', null);
		return $token ? json_decode($token, true) : null;
	}

	/**
	 * @param string|array $token
	 *
	 * @return void
	 */
	public function setAccessToken($token):void {
		if(is_array($token)) $token = json_encode($token);
		$this->getOptions()->set('token', $token);
	}

	/**
	 * @param string $token
	 *
	 * @return void
	 */
	public function setRefreshToken(string $token):void { $this->getOptions()->set('refresh_token', $token); }

	/**
	 * @return string|null
	 */
	public function getRefreshToken():?string { return $this->getOptions()->get('refresh_token', null); }

	/**
	 * @return void
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {
		if(!$this->getPath()) throw new FieldsValidationException("No path provided");
		if(!str_starts_with($this->getPath(), '/')) throw new FieldsValidationException("Path must start with \"/\"");
		if(!preg_match("/^[\/a-zA-Z0-9\-_.]+$/", $this->getPath())) throw new FieldsValidationException("Invalid path provided (Allowed characters A-Z a-z 0-9 -_. and /)");
		if($this->getRetries() > 10 || $this->getRetries() < 0) throw new FieldsValidationException("Invalid retries provided. Minimum 0 and Maximum 10");
		if(!$this->getAccessCode() && !$this->getRefreshToken()) throw new FieldsValidationException("No access code provided");
	}

	/**
	 * @return void
	 * @throws ConnectionException
	 * @throws DBException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function connect():void {
		try {
			if(!$this->getClient(true)) throw new ConnectionException("Unable to retrieve google drive service");
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
	 * @throws JBException
	 * @throws RegistrationException
	 */
	public function register():void {
		try {
			if(!$this->getClient()) throw new RegistrationException("Unable to retrieve google drive service");
	
			try {
				$this->getFileId($this->getRealPath('/'));
			} catch(Exception $e) {
				$this->_createDir($this->getRealPath('/'));
			}
		} catch(IOException $e) {
			throw new RegistrationException($e->getMessage());
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
		if(isset($data->token)) $this->setAccessToken($data->token);
		if(isset($data->access_code)) $this->setAccessCode($data->access_code);
		if(isset($data->refresh_token)) $this->setRefreshToken($data->refresh_token);
		if(isset($data->retries)) $this->setRetries($data->retries);
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
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return bool
	 */
	public function dirExists(string $directory, ?string $data=null): bool {
		try {
			$this->getLogController()->logDebug("[dirExists] $directory");
			if(($id = $this->_getId($directory, $data))) {

				$file = $this->_retries(function() use ($id) {
					return $this->getClient()->getFile($id);
				}, "Failed fetching file");

				return $file->mimeType == Client::MIMITYPE_DIR;
			}
		} catch(Exception $e) {}
		return false;
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
			$this->getLogController()->logDebug("[fileExists] $file");
			if(($id = $this->_getId($file, $data))) {

				$file = $this->_retries(function() use ($id) {
					return $this->getClient()->getFile($id);
				}, "Failed fetching file");

				return $file->mimeType != Client::MIMITYPE_DIR;
			}
		} catch(Exception $e) {
			if($e->getCode() == 404) return false;
			throw new IOException($e->getMessage());
		}
		return false;
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
		$this->getLogController()->logDebug("[createDir] {$this->getRealPath($directory)}");
		$data = self::_parseData($data);
		return $this->_createDir($this->getRealPath($directory), $data->id ?? null);
	}

	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @throws IOException
	 */
	public function removeDir(string $directory, ?string $data=null):void {
		$this->getLogController()->logDebug("[removeDir] $directory");
		$this->removeFile($directory, $data);
	}

	/**
	 * @param string $file
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeFile(string $file, ?string $data=null):void {
		try	{
			$this->getLogController()->logDebug("[removeFile] $file");
			if(($id = $this->_getId($file, $data))) {

				$this->_retries(function() use ($id) {
					$this->getClient()->deleteFile($id);
				}, "Failed deleting file");

			} 
		} catch(Exception $e) {
			throw new IOException($e->getMessage());
		}

		$this->_cache->remove(trim($this->getRealPath($file), '/'));
	}

	/**
	 * @param callable $callback
	 * @param string $message
	 *
	 * @return mixed
	 * @throws IOException
	 */
	public function _retries(callable $callback, string $message) {
		$waittime = 333000;
		$tries = 0;

		while(true) {
			try {
				return $callback();
			} catch(Exception $e) {
				$error = json_decode($e->getMessage());
				$error_message = $error->error->message ?? $e->getMessage();
				if(($e->getCode() < 500 && !in_array($e->getCode(), [0,1,400,403,429])) || $tries >= $this->getRetries()) throw new IOException($error_message, $e->getCode());
				$this->getLogController()->logDebug("$message. Error: $error_message");
				if($waittime > 60000000) $waittime = 60000000;
				usleep($waittime);
				$waittime *= 2;
				$tries++;
				$this->getLogController()->logDebug("Retry $tries/{$this->getRetries()} $message");
			}
		}
		
	}
	
	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @throws JBException
	 * @throws IOException
	 * @return void
	 */
	public function copyFileToLocal(string $source, string $destination, ?string $data=null):void {
		if(!($client = $this->getClient())) throw new RegistrationException("Unable to retrieve google drive service");
		if(!($id = $this->_getId($source, $data))) throw new IOException("File not found ($source).");
		if(!file_exists(dirname($destination))) throw new IOException("Destination path not found ($destination).");
		if(file_exists($destination) && is_dir($destination)) $destination .= "/". basename($source);
		$this->getLogController()->logDebug("[copyFileToLocal] $source -> $destination");

		$this->_retries(function() use ($client, $id, $destination) {

			try {
				$client->download($id, $destination);
			} catch(ClientException $e) {
				throw new IOException($e->getMessage());
			}

		}, "Failed downloading file \"$source\" ($id)");


	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedDownload
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws JBException
	 * @throws RegistrationException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function copyFileToLocalChunked(string $source, string $destination, ?string $data=null):DestinationChunkedDownload {
		if(!($client = $this->getClient())) throw new RegistrationException("Unable to retrieve google drive service");
		if(!($id = $this->_getId($source, $data))) throw new IOException("File not found ($source).");
		$this->getLogController()->logDebug("[copyFileToLocalChunked] $source -> $destination");

		return new ChunkedDownload($this, $id, $destination);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return string|null
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws RegistrationException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function copyFileToRemote(string $source, string $destination, ?string $data=null):?string {
		if(!($client = $this->getClient())) throw new RegistrationException("Unable to retrieve google drive service");
		$this->getLogController()->logDebug("[copyFileToRemote] $source -> $destination");

		// remove the file if exists
		try { $this->removeFile($destination); } catch(Exception $e) {}

		// create parent dir if needed and return the parent dir id
		$id = $this->createDir(dirname($destination), true, $data);

		return $this->_retries(function() use ($client, $source, $destination, $id) {
			
			if(!file_exists($source))
				throw new IOException("The file $source doesn't exists, looks like this file has vanished");

			try {
				$status = $client->upload($source, $destination, $id);
			} catch(ClientException $e) {
				throw new IOException($e->getMessage());
			}

			if(!isset($status->id)) throw new IOException("Failed uploading file $source");

			return json_encode([ 'id' => $status->id ]);
			
		}, "Failed uploading file $source");
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedUpload
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws RegistrationException
	 * @throws \SleekDB\Exceptions\IOException|FileException
	 */
	public function copyFileToRemoteChunked(string $source, string $destination, ?string $data=null):DestinationChunkedUpload {
		if(!($client = $this->getClient())) throw new RegistrationException("Unable to retrieve google drive service");
		$this->getLogController()->logDebug("[copyFileToRemoteChunked] $source -> $destination");

		// create parent dir if needed and return the parent dir id
		$id = $this->createDir(dirname($destination), true, $data);

		return new ChunkedUpload($this, $source, $destination, $id);
	}
	
	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return DestinationDirIterator
	 * @throws IOException
	 * @throws JBException
	 */
	public function listDir(string $directory, ?string $data=null):DestinationDirIterator {
		$this->getLogController()->logDebug("[listDir] Get ID for $directory");
		return new DirIterator($this, $directory, $this->_getId($directory, $data));
	}

	/**
	 * @return iDestinationDiskUsage|null
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function getDiskInfo():?iDestinationDiskUsage {
		$usage = new DestinationDiskUsage();
		
		try {
			$about = $this->getClient()->about();
		} catch(ClientException $e) {
			return $usage;
		}

		if(!isset($about->quotaBytesUsed) || !isset($about->quotaBytesTotal)) return $usage;
		
		$usage->setUsageSpace($about->quotaBytesUsed);
		$usage->setTotalSpace($about->quotaBytesTotal);
		$usage->setFreeSpace($about->quotaBytesTotal - $about->quotaBytesUsed);
		return $usage;
	}

	/**
	 * @param bool $renew
	 *
	 * @return Client
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws \SleekDB\Exceptions\IOException
	 */
	public function getClient(bool $renew=false):Client {
		if(!$this->_client || $renew) {
			$this->_client = new Client();
			$this->_client->setClientId($this->getClientId());
			$this->_client->setClientSecret($this->getClientSecret());

			if(!$this->getAccessToken()) {
				try {
					$authData = $this->_client->fetchAccessTokenWithAuthCode($this->getAccessCode());
				} catch(ClientException $e) {
					throw new IOException($e->getMessage());
				}
				if(!isset($authData['refresh_token']) || !$authData['refresh_token']) throw new IOException("No refresh token was received while authenticating your google account");
				$this->setAccessToken($authData);
				$this->setRefreshToken($authData['refresh_token']);
				$this->setAccessCode('');
			}
			if($this->getAccessToken()) $this->_client->setAccessToken($this->getAccessToken());
		}

		if($this->getAccessToken()) $this->_refreshAccessToken();

		return $this->_client;
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	private function _refreshAccessToken():void {
		if(!$this->getRefreshToken() || !$this->_client->isAccessTokenExpired()) return;

		// pass the access token to the Google client
		$this->_client->setAccessToken($this->getAccessToken());
		// if now the access token isn't expired that means that other thread already fetched new access token, exit as there is nothing to do
		if(!$this->_client->isAccessTokenExpired()) return;
		
		try {
			$this->_client->setAccessToken($this->getAccessToken());
			$this->_client->setRefreshToken($this->getRefreshToken());
			$this->_client->fetchAccessTokenWithRefreshToken();
		}catch (Exception $e) {
			$this->getLogController()->logDebug("Failed to fetch Refresh Token");
			throw new IOException($e->getMessage());
		}
		$this->setAccessToken($this->_client->getAccessToken());
		$this->save();
	}

	/**
	 * @param string $path
	 * @param string|null $parent_id
	 *
	 * @return string|null
	 * @throws IOException
	 * @throws JBException
	 */
	public function getFileId(string $path, ?string $parent_id=null):?string {
		$this->getLogController()->logDebug("[getFileId] Get ID for $path");
		$id = $parent_id ?? Client::ROOT_ID;
		if(!$path) return $id;
		$cache = $this->_cache;
		
		$path = preg_replace('#^/#', '', $path);
		if($cache->has($path)) return $cache->get($path);

		$directories = explode('/', $path);
		if(!$this->getClient()) throw new IOException("Unable to retrieve google drive service");

		$_tmp_cached = [];

		foreach($directories as $directory) {
			if(!$directory || preg_match("/^(\.?\.)$/", $directory)) continue;
			$_tmp_cached[] = $directory;
			$_tmp_cached_str = implode("/", $_tmp_cached);
		
			if($cache->has($_tmp_cached_str)){
				$id = $cache->get($_tmp_cached_str);
				continue;
			}

			try {

				try {
					$output = $this->getClient()->listFiles([
						'fields'    => 'files',
						'q'			=> "'$id' in parents and name = '$directory' and trashed = false",
					]);
				} catch(ClientException $e) {
					if($e->getCode() != 404) throw new IOException($e->getMessage(), $e->getCode());
					return null;
				}

				$list = $output->getFiles();
				
				if(!isset($list[0])) return null;

				$id = $list[0]->getId();
				
				$cache->set($_tmp_cached_str, $id);
			}
			catch(Exception $e){
				throw new IOException($e->getMessage());
			}
		}
		return $id;
	}

	/**
	 * @param string $path
	 * @param string|null $parent_id
	 *
	 * @return string|null
	 * @throws IOException
	 */
	private function _createDir(string $path, ?string $parent_id=null):?string {

		try {
			$this->getLogController()->logDebug("[_createDir] Create folder $path");
			if(($id = $this->getFileId($path))) {
				return $id;
			}

			$dirs = explode('/', $path);
			if(!$parent_id) $parent_id = Client::ROOT_ID;

			do {
				$c_dir = array_shift($dirs);

				$tmp = null;
				try{ $tmp = $this->getFileId($c_dir, $parent_id); }
				catch(Exception $e){}

				if(!$tmp){
					$tmp = $this->_createSingleDir($parent_id, basename($c_dir));
					$this->_cache->set($c_dir, $tmp);
				}
				$parent_id = $tmp;

			} while(sizeof($dirs) > 0);
		} catch(Exception $e) {
			throw new IOException($e->getMessage());
		}

		return $parent_id;
	}

	/**
	 * @param string $parent_id
	 * @param string $dirname
	 *
	 * @return string
	 * @throws IOException
	 * @throws JBException
	 */
	private function _createSingleDir(string $parent_id, string $dirname):string {
		try {
			$this->getLogController()->logDebug("[_createSingleDir] Create folder $dirname");
			$file = $this->getClient()->createDir($dirname, $parent_id);
			return $file->id;
		}catch(ClientException $e){
			throw new IOException($e->getMessage());
		}
	}

	/**
	 * @param string|null $data
	 *
	 * @return stdClass
	 */
	private static function _parseData(?string $data=null): stdClass {
		if($data) $data = json_decode($data);
		return $data ?: new stdClass();
	}

	/**
	 * @param string $path
	 * @param string|null $data
	 *
	 * @return string|null
	 * @throws IOException
	 * @throws JBException
	 */
	private function _getId(string $path, ?string $data=null):?string {
		$data = self::_parseData($data);
		return $data->id ?? $this->getFileId($this->getRealPath($path));
	}

	/**
	 * @throws IOException
	 */
	public function getFileStat(string $file): ?iDestinationFile {
		try {

			$this->getLogController()->logDebug("[getFileStat] Checking stat for $file");
			$id = $this->_getId($file);
			if (!$id) return null;

			$fileMetadata = $this->getClient()->getFile($id);
			$fileObject = new DestinationFile();

			$fileObject->setName($fileMetadata->name);
			$fileObject->setPath($this->getRealPath(dirname($file)));
			$fileObject->setSize($fileMetadata->size ?? 0); // Size is 0 for directories
			$fileObject->setModifyTime(strtotime($fileMetadata->modifiedTime ?? 'now'));
			$fileObject->setType( $fileMetadata->mimeType === Client::MIMITYPE_DIR ? iDestinationFile::TYPE_DIRECTORY : iDestinationFile::TYPE_FILE);

			return $fileObject;

		} catch (Exception $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}


	public function protectedFields(): array { return ['token','refresh_token']; }
}