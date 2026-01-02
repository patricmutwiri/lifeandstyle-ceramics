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
namespace JetBackup\Destination\Vendors\Box;

use Exception;
use JetBackup\Destination\DestinationDiskUsage;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\DestinationWrapper;
use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationDiskUsage as iDestinationDiskUsage;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Vendors\Box\Client\Client;
use JetBackup\Destination\Vendors\Box\Client\ClientException;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\RegistrationException;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use SleekDB\Exceptions\InvalidArgumentException;
use stdClass;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class Box extends DestinationWrapper {

	const TYPE = 'Box';

	public const MIN_CHUNK_SIZE = 20971520; // https://developer.box.com/guides/uploads/chunked/#restrictions
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
	 * @param string $code
	 *
	 * @return void
	 */
	public function setAccessCode(string $code):void {
		if($this->getAccessCode() && $code && $this->getAccessCode() != $code) $this->setAccessToken('');
		$this->getOptions()->set('access_code', $code);
	}

	/**
	 * @return string
	 */
	public function getAccessToken():string { return $this->getOptions()->get('token'); }

	/**
	 * @param string $token
	 *
	 * @return void
	 */
	public function setAccessToken(string $token):void { $this->getOptions()->set('token', $token); }

	/**
	 * @param string $token
	 *
	 * @return void
	 */
	public function setRefreshToken(string $token):void { $this->getOptions()->set('refresh_token', $token); }

	/**
	 * @return string
	 */
	public function getRefreshToken():string { return $this->getOptions()->get('refresh_token'); }

	/**
	 * @return int
	 */
	public function getAccessTokenExpires():int { return $this->getOptions()->get('token_expires', 0); }

	/**
	 * @param int $expires
	 *
	 * @return void
	 */
	public function setAccessTokenExpires(int $expires):void { $this->getOptions()->set('token_expires', $expires); }

	/**
	 * @return void
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {
		if(!$this->getPath()) throw new FieldsValidationException("No path provided");
		if(!str_starts_with($this->getPath(), '/')) throw new FieldsValidationException("Path must start with \"/\"");
		if(!preg_match("/^[\/a-zA-Z0-9\-_.]+$/", $this->getPath())) throw new FieldsValidationException("Invalid path provided (Allowed characters A-Z a-z 0-9 -_. and /)");
		if($this->getRetries() > 10 || $this->getRetries() < 0) throw new FieldsValidationException("Invalid retries provided. Minimum 0 and Maximum 10");
		if($this->getChunkSize() < self::MIN_CHUNK_SIZE) throw new FieldsValidationException("Invalid chunk size provided. Minimum 20MB");
	}

	/**
	 * @return void
	 * @throws ConnectionException
	 * @throws JBException
	 */
	public function connect():void {
		try {
			if(!$this->getClient(true)) throw new ConnectionException("Unable to retrieve Box service");
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
			if(!$this->getClient()) throw new RegistrationException("Unable to retrieve Box service");
	
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
		if(isset($data->token_expires)) $this->setAccessTokenExpires($data->token_expires);
		if(isset($data->retries)) $this->setRetries($data->retries);
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
			$directory = $this->getRealPath($directory);
			if(($id = $this->getFolderId($directory))) {

				$file = $this->_retries(function() use ($id) {
					return $this->getClient()->getFolderInfo($id);
				}, "Failed fetching file");

				return $file->type == Client::MIMITYPE_DIR;
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

			$file = $this->getRealPath($file);
			$this->getLogController()->logDebug("[fileExists] file: $file");

			if(($id = $this->getFileId($file))) {

				$file = $this->_retries(function() use ($id) {
					return $this->getClient()->getFileInfo($id);
				}, "Failed fetching file");

				return $file->type == Client::MIMITYPE_FILE;
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
			$file = $this->getRealPath($file);
			if(($id = $this->getFileId($file))) {

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
	 * @param string $filePath The full path to the file (e.g., "/backup/meta.json").
	 * @return string|null The file ID if found, or null if not found.
	 * @throws IOException
	 */
	public function getFileId(string $filePath): ?string {
		$this->getLogController()->logDebug("[getFileId] Resolving file path: $filePath");

		// Extract the directory path and file name
		$directoryPath = dirname($filePath);
		$fileName = basename($filePath);

		// Get the folder ID for the parent directory
		$folderId = $this->getFolderId($directoryPath);

		if ($folderId === null) {
			$this->getLogController()->logError("[getFileId] Failed to resolve folder ID for: $directoryPath");
			return null;
		}

		$this->getLogController()->logDebug("[getFileId] Folder ID resolved for $directoryPath: $folderId");

		// List files in the resolved folder to find the target file
		try {
			$list = $this->getClient()->listFolder($folderId);
		} catch (ClientException $e) {
			$this->getLogController()->logError("[getFileId] Failed to fetch folder contents: " . $e->getMessage());
			throw new IOException($e->getMessage(), $e->getCode());
		}

		// Search for the file in the folder contents
		foreach ($list->getFiles() as $item) {
			if ($item->getMimeType() === Client::MIMITYPE_FILE && $item->getName() === $fileName) {
				$this->getLogController()->logDebug("[getFileId] Found file: $filePath with ID: {$item->getId()}");
				return $item->getId();
			}
		}

		$this->getLogController()->logError("[getFileId] File not found: $filePath");
		return null;
	}


	/**
	 * @param string $path
	 *
	 * @return string|null
	 * @throws IOException
	 */
	public function getFolderId(string $path): ?string {
		$this->getLogController()->logDebug("[getFolderId] Resolving Path: $path");

		$parentId = Client::ROOT_FOLDER;
		if (!$path || $path === '/') {
			return $parentId;
		}

		$folders = explode(JetBackup::SEP, trim($path, JetBackup::SEP));
		$cache = $this->_cache;
		$currentPath = '';

		foreach ($folders as $folder) {
			if (empty($folder)) {
				continue;
			}

			// Build the full path for the current folder
			$currentPath .= JetBackup::SEP . $folder;

			$this->getLogController()->logDebug("[getFolderId] Resolving folder: $folder under parent ID: $parentId");

			// Check cache with full path as the key
			if ($cache->has($currentPath)) {
				$parentId = $cache->get($currentPath);
				$this->getLogController()->logDebug("[getFolderId] Cache HIT for $currentPath with ID: $parentId");
				continue;
			}

			// List folders under the current parent ID
			$list = $this->getClient()->listFolder($parentId);

			$found = false;
			foreach ($list->getFiles() as $item) {
				if ($item->getName() === $folder && $item->getMimeType() === Client::MIMITYPE_DIR) {
					$parentId = $item->getId();
					// Cache the folder ID using the full path
					$cache->set($currentPath, $parentId);
					$this->getLogController()->logDebug("[getFolderId] Folder found: $folder with ID: $parentId");
					$found = true;
					break;
				}
			}

			if (!$found) {
				$this->getLogController()->logError("[getFolderId] Folder $folder not found under parent ID: $parentId");
				return null;
			}
		}

		$this->getLogController()->logDebug("[getFolderId] Final resolved ID for $path: $parentId");
		return $parentId;
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
		if(!($client = $this->getClient())) throw new RegistrationException("Unable to retrieve Box service");
		$source = $this->getRealPath($source);
		if(!($id = $this->getFileId($source))) throw new IOException("File not found ($source).");
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
	 * @param string|null $data
	 *
	 * @return DestinationChunkedDownload
	 * @throws IOException
	 * @throws JBException
	 */
	public function copyFileToLocalChunked(string $source, string $destination, ?string $data=null):DestinationChunkedDownload {
		if(!$this->getClient()) throw new RegistrationException("Unable to retrieve Box service");
		$source = $this->getRealPath($source);
		if(!($id = $this->getFileId($source))) throw new IOException("File not found ($source).");

		return new ChunkedDownload($this, $id, $destination);
	}

	/**
	 * @param string $source
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @throws JBException
	 * @throws IOException
	 * @return string|null
	 */
	public function copyFileToRemote(string $source, string $destination, ?string $data = null): ?string {
		if (!($client = $this->getClient())) throw new RegistrationException("Unable to retrieve Box service");

		$this->getLogController()->logDebug("[copyFileToRemote] Destination: $destination");
		$this->getLogController()->logDebug("[copyFileToRemote] Source: $source");

		$destination = $this->getRealPath($destination);

		// Create parent directory if needed
		$parentId = $this->createDir(dirname($destination), true, $data);
		$this->getLogController()->logDebug("[copyFileToRemote] Folder ID return from createDir: $parentId");

		// Check if the file already exists
		if ($fileId = $this->getFileId($destination, $parentId)) {
			$this->getLogController()->logDebug("[copyFileToRemote] EXIST HIT: File $destination exists, skipping [ID: $fileId]");
			return json_encode(['id' => $fileId]);
		}

		// Retry logic for uploading the file
		return $this->_retries(function () use ($client, $source, $destination, $parentId) {
			if (!file_exists($source)) {
				throw new IOException("[copyFileToRemote] The file $source doesn't exist; it may have been removed.");
			}

			try {
				$status = $client->upload($source, $destination, $parentId);
			} catch (ClientException $e) {
				throw new IOException($e->getMessage());
			}

			if (!isset($status->entries[0]->id)) {
				throw new IOException("[copyFileToRemote] Failed uploading file $source");
			}

			return json_encode(['id' => $status->entries[0]->id]);
		}, "Failed uploading file $source");
	}


	/**
	 * @param string $destination
	 * @param string|null $data
	 *
	 * @return DestinationChunkedUpload
	 * @throws IOException
	 * @throws JBException
	 */
	public function copyFileToRemoteChunked(string $source, string $destination, ?string $data=null):DestinationChunkedUpload {
		if(!$this->getClient()) throw new RegistrationException("Unable to retrieve Box service");

		$this->getLogController()->logDebug("[copyFileToRemoteChunked] Destination: $destination");
		// Create parent directory if needed
		$parentId = $this->createDir(dirname($destination), true, $data);
		$this->getLogController()->logDebug("[copyFileToRemoteChunked] Folder ID return from createDir: $parentId");

		return new ChunkedUpload($this, $source, $destination, $parentId);
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
		$directory = $this->getRealPath($directory);
		$this->getLogController()->logDebug("[listDir] Listing dir: $directory");
		return new DirIterator($this, $directory, $this->getFolderId($directory));
	}

	/**
	 * @return iDestinationDiskUsage|null
	 * @throws IOException
	 * @throws JBException
	 */
	public function getDiskInfo():?iDestinationDiskUsage {
		$usage = new DestinationDiskUsage();
		
		try {
			$about = $this->getClient()->getAccountInfo();
		} catch(ClientException $e) {
			return $usage;
		}

		if(!isset($about->space_used) || !isset($about->space_amount)) return $usage;
		
		$usage->setUsageSpace($about->space_used);
		$usage->setTotalSpace($about->space_amount);
		$usage->setFreeSpace($about->space_amount - $about->space_used);
		return $usage;
	}

	/**
	 * @param bool $renew
	 *
	 * @return Client
	 * @throws IOException
	 * @throws JBException
	 */
	public function getClient(bool $renew=false):Client {

		$this->getLogController()->logDebug("[getClient]");

		if(!$this->_client || $renew) {
			$this->_client = new Client();
			$this->_client->setLogController($this->getLogController());
			$this->_client->setCache($this->_cache);
			$this->getLogController()->logDebug("[getClient] Renew client");

			if(!$this->getAccessToken()) {

				$this->getLogController()->logDebug("[getClient] No AccessToken");

				$this->_client->setAccessCode($this->getAccessCode());
				try {
					$this->getLogController()->logDebug("[getClient] Trying to fetch access token");
					$response = $this->_client->fetchToken();
				} catch(ClientException $e) {
					$this->getLogController()->logError("[getClient] Error: ".$e->getMessage());
					throw new IOException($e->getMessage());
				}
				if(!isset($response->refresh_token) || !$response->refresh_token) throw new IOException("No refresh token was received while authenticating your box account");
				$this->setRefreshToken($response->refresh_token);
				$this->setAccessToken($response->access_token);
				$this->setRefreshToken($response->refresh_token);
				$this->setAccessTokenExpires(time() + $response->expires_in);
				$this->setAccessCode('');
			}

			if($this->getAccessToken()) {
				$this->getLogController()->logDebug("[getClient] Setting NEW Access Token");
				$this->_client->setAccessToken($this->getAccessToken());
			}
		}

		if($this->getAccessToken()) {
			$this->getLogController()->logDebug("[getClient] Using active access token");
			try {
				$this->_refreshAccessToken();
			} catch (IOException $e) {
				$this->getLogController()->logError("[getClient] Failed to refresh token: " . $e->getMessage());
				throw new IOException($e->getMessage());
			}

			try {
				// TODO - This is a temporary workaround, need to remove this after verified the issues with the expiry times
				// Test the token by making a lightweight call (e.g., get account info)
				$this->_client->getAccountInfo();
			} catch (ClientException $e) {
				if ($e->getCode() === 401) {
					$this->getLogController()->logError("[getClient] Token is invalid, forcing refresh...");
					$this->_refreshAccessToken(true);
				} else {
					$this->getLogController()->logError("[getClient] Error: " . $e->getMessage() );
					throw new IOException($e->getMessage());
				}
			}

		}

		return $this->_client;
	}

	/**
	 * @param bool $force
	 *
	 * @return void
	 * @throws IOException
	 * @throws DBException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	private function _refreshAccessToken( bool $force = false):void {

		if(!$this->getRefreshToken() || ( $this->getAccessTokenExpires() - ( time() - 1200 ) ) > 0 || !$force) {
			//$this->getLogController()->logDebug("[_refreshAccessToken] Token is valid");
			//$this->getLogController()->logDebug("[_refreshAccessToken] Token time expiry: " . $this->getAccessTokenExpires());
			//$this->getLogController()->logDebug("[_refreshAccessToken] Token time now: " . (time() - 900));
			//$this->getLogController()->logDebug( "[_refreshAccessToken] Token time result: " . ( $this->getAccessTokenExpires() - ( time() - 900 ) ));
			return;
		}

		// pass the access token to the Box client
		$this->_client->setAccessToken($this->getAccessToken());
		$this->_client->setRefreshToken($this->getRefreshToken());

		$this->getLogController()->logDebug("[_refreshAccessToken] setAccessToken & setRefreshToken to the client class");

		try {
			$this->getLogController()->logDebug("[_refreshAccessToken] Trying to fetch new refresh token");
			$response = $this->_client->fetchToken();
		}catch (Exception $e) {
			$this->getLogController()->logError("Failed to fetch Refresh Token: " . $e->getMessage());
			throw new IOException($e->getMessage());
		}
		
		$this->setAccessToken($response->access_token);
		$this->setRefreshToken($response->refresh_token);
		$this->setAccessTokenExpires(time() + $response->expires_in);

		$this->getLogController()->logDebug("[_refreshAccessToken] Renewed access_token, refresh_token & expiry");

		$this->save();

		$this->getLogController()->logDebug("[_refreshAccessToken] New details saved successfully");


		$this->_client->setAccessToken($this->getAccessToken());
		$this->_client->setRefreshToken($this->getRefreshToken());

		$this->getLogController()->logDebug("[_refreshAccessToken] Updated _client class memory");

	}


	/**
	 * @param string $path
	 * @param string|null $parent_id
	 *
	 * @return string|null
	 * @throws ClientException
	 * @throws HttpRequestException
	 * @throws IOException
	 * @throws JBException
	 */
	private function _createDir(string $path, ?string $parent_id = null): ?string {
		if (!$path || $path === '/') {
			return Client::ROOT_FOLDER; // Root folder ID
		}

		$this->getLogController()->logDebug("[_createDir] Path: $path");

		// Initialize cache
		$cache = $this->_cache;
		$folders = explode(JetBackup::SEP, trim($path, JetBackup::SEP));
		$parentId = $parent_id ?? Client::ROOT_FOLDER;

		foreach ($folders as $folder) {
			if (empty($folder)) continue;

			$currentPath = ($parentId === Client::ROOT_FOLDER)
				? $folder
				: implode(JetBackup::SEP, [$parentId, $folder]);

			$this->getLogController()->logDebug("[_createDir] Checking/Creating folder: $folder under Parent ID: $parentId");

			// Check if the folder exists in cache
			if ($cache->has($currentPath)) {
				$parentId = $cache->get($currentPath);
				$this->getLogController()->logDebug("[_createDir] Cache HIT for folder: $folder with ID: $parentId");
				continue;
			}

			// Get list of items in the parent folder
			$list = $this->getClient()->listFolder($parentId);

			// Look for the folder in the current directory
			$found = false;
			foreach ($list->getFiles() as $item) {
				if ($item->getName() === $folder && $item->getMimeType() === Client::MIMITYPE_DIR) {
					$parentId = $item->getId();
					$cache->set($currentPath, $parentId);
					$found = true;
					$this->getLogController()->logDebug("[_createDir] Found existing folder: $folder with ID: $parentId");
					break;
				}
			}

			// Create the folder if not found
			if (!$found) {
				$parentId = $this->_createSingleDir($parentId, $folder);
				$cache->set($currentPath, $parentId);
				$this->getLogController()->logDebug("[_createDir] Created folder: $folder with new ID: $parentId");
			}
		}

		return $parentId; // Return the ID of the last folder created/checked
	}


	/**
	 * @param string $parent_id
	 * @param string $dirname
	 *
	 * @return string
	 * @throws IOException
	 * @throws JBException
	 */
	private function _createSingleDir(string $parent_id, string $path):string {
		try {
			$this->getLogController()->logDebug("[_createSingleDir] ParentID: $parent_id, dirname: $path");
			$cache = $this->_cache;
			if($cache->has($path)) {
				$parent_id = $cache->get($path);
				$this->getLogController()->logDebug("[_createSingleDir] Cache HIT for [$path] [ID: $parent_id]");
				return $parent_id;
			}


			$file = $this->getClient()->createFolder($path, $parent_id);
			$this->getLogController()->logDebug("[_createSingleDir] File ID: " . $file->id);
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


	public function getFileStat(string $file):?iDestinationFile {
		try {
			$this->getLogController()->logDebug("[getFileStat] Fetching stat for file $file");
			$file = $this->getRealPath($file);
			if (!($id = $this->getFileId($file))) return null;

			$fileMetadata = $this->getClient()->getFileInfo($id);

			$fileObject = new DestinationFile();
			$fileObject->setName($fileMetadata->name);
			$fileObject->setPath($this->getPath());
			$fileObject->setSize($fileMetadata->size ?? 0); // Size is 0 for directories
			$fileObject->setModifyTime(strtotime($fileMetadata->modified_at ?? 'now'));
			$fileObject->setType(iDestinationFile::TYPE_FILE);

			return $fileObject;

		} catch (Exception $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}

	public function protectedFields(): array { return ['token','refresh_token']; }
}