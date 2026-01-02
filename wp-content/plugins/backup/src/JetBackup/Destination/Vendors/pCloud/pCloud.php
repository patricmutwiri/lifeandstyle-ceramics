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
namespace JetBackup\Destination\Vendors\pCloud;

use Exception;
use JetBackup\Destination\DestinationDiskUsage;
use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\DestinationWrapper;
use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationDiskUsage as iDestinationDiskUsage;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Vendors\Box\Cache;
use JetBackup\Destination\Vendors\pCloud\Client\Client;
use JetBackup\Destination\Vendors\pCloud\Client\ClientException;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\HttpRequestException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\JBException;
use JetBackup\Exception\RegistrationException;
use JetBackup\Log\LogController;
use JetBackup\Web\File\FileStream;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class pCloud extends DestinationWrapper {

	const TYPE = 'pCloud';

    const PARENT_DIR_NOT_FOUND = 2002;

    private ?Client $_client = null;
	private Cache $_cache;

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
	 * @param string $url
	 *
	 * @return void
	 */
	public function setAPIUrl(string $url) { $this->getOptions()->set('api_url', $url); }

	/**
	 * @return string
	 */
	public function getAPIUrl():string { return $this->getOptions()->get('api_url'); }
	
	/**
	 * @return void
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {
		if(!$this->getPath()) throw new FieldsValidationException("No path provided");
		if(!str_starts_with($this->getPath(), '/')) throw new FieldsValidationException("Path must start with \"/\"");
		if(!preg_match("/^[\/a-zA-Z0-9\-_.]+$/", $this->getPath())) throw new FieldsValidationException("Invalid path provided (Allowed characters A-Z a-z 0-9 -_. and /)");
		if($this->getRetries() > 10 || $this->getRetries() < 0) throw new FieldsValidationException("Invalid retries provided. Minimum 0 and Maximum 10");
		if(!($this->getAccessCode()) && !$this->getAccessToken()) throw new FieldsValidationException("No access code provided");
	}

	/**
	 * @return void
	 * @throws ConnectionException
	 * @throws JBException
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
	
			$this->createDir('/', false);
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
		if(isset($data->api_url)) $this->setAPIUrl($data->api_url);
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
			$file = $this->_retries(function() use ($directory) {
				return $this->getClient()->getFolderInfo($directory);
			}, "Failed fetching file");
			return $file->metadata->isfolder;
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
            $file = $this->_retries(function () use ($file) {
                     return $this->getClient()->getFileInfo($this->getRealPath($file));
                    }, "Failed fetching file");
            			return !$file->metadata->isfolder;
		} catch(Exception $e) {
			if($e->getCode() == Client::CODE_FILE_NOT_FOUND ||  $e->getCode() == self::PARENT_DIR_NOT_FOUND ) {
                return false;
            }
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
	public function createDir(string $directory, bool $recursive, ?string $data = null): ?string {
		if (!$directory || $directory === '/') {
			$this->getlogController()->logDebug("[createDir] Root directory requested, skipping creation.");
			return null;
		}
		$directory = $this->getRealPath($directory);
		$this->getlogController()->logDebug("[createDir] Creating folder $directory, Recursive: " . ($recursive ? 'YES' : 'NO'));

		$path = '';
		$dirs = explode('/', trim($directory, '/'));

		if($this->_cache->has($directory)) {
			$this->getlogController()->logDebug("[createDir] Cache HIT for $directory");
			return null; // Folder already created
		}

		foreach ($dirs as $dir) {
			$path .= '/' . $dir;

			// Skip root
			if ($path === '/') {
				continue;
			}

			$this->getlogController()->logDebug("[createDir] Ensuring folder exists: $path");


			try {
				$this->_createSingleDir($path);
			} catch (ClientException $e) {
				if ($e->getCode() == Client::CODE_DIR_EXISTS) {
					$this->getlogController()->logDebug("[createDir] Folder already exists: $path");
					$this->_cache->set($path, $path);
					continue; // Folder exists, continue with the next level
				} else {
					$this->getlogController()->logError("[createDir] Failed to create folder: $path. Error: {$e->getMessage()}");
					throw $e; // Rethrow the exception for other errors
				}
			}
		}
		$this->_cache->set($directory, $directory);
		return null;
	}

	/**
	 * @param string $directory
	 *
	 * @return string|null
	 * @throws ClientException
	 * @throws IOException
	 * @throws JBException
	 * @throws HttpRequestException
	 */
	private function _createSingleDir(string $directory): ?string {

		try {
			$this->getlogController()->logDebug("[_createSingleDir] Attempting to create folder: $directory");
			$this->getClient()->createFolder($directory);
		} catch (ClientException $e) {
			if ($e->getCode() == Client::CODE_DIR_EXISTS) {
				$this->getlogController()->logDebug("[_createSingleDir] Folder already exists: $directory");
				return null; // Directory already exists
			}
			throw $e; // Rethrow other exceptions
		}

		$this->getlogController()->logDebug("[_createSingleDir] Successfully created folder: $directory");
		return null;
	}

	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @throws IOException
	 */
	public function removeDir(string $directory, ?string $data=null):void {
		try	{
			$directory = $this->getRealPath($directory);
			$this->getlogController()->logDebug("[removeDir] Removing folder $directory");
			$this->_retries(function() use ($directory) {
				$this->getClient()->deleteFolder($directory);
			}, "Failed deleting directory");
			$this->_cache->remove($directory);
		} catch(Exception $e) {
			throw new IOException($e->getMessage());
		}
	}

	/**
	 * @param string $file
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeFile(string $file, ?string $data = null): void {
		try {
			$file = $this->getRealPath($file);
			$this->getlogController()->logDebug("[removeFile] Attempting to remove file: $file");

			// Check if the file exists before trying to delete it
			if (!$this->fileExists($file)) {
				$this->getlogController()->logDebug("[removeFile] File does not exist: $file. Skipping deletion.");
				return;
			}

			$this->_retries(function () use ($file) {
				$this->getClient()->deleteFile($file);
			}, "Failed deleting file");

			$this->getlogController()->logDebug("[removeFile] Successfully removed file: $file");
		} catch (Exception $e) {
			// Log the exception but do not fail if the file does not exist
			if ($e->getCode() === Client::CODE_FILE_NOT_FOUND) {
				$this->getlogController()->logDebug("[removeFile] File not found during deletion: $file. Skipping.");
				return;
			}

			// Rethrow for other exceptions
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
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
				if(in_array($e->getCode(), [Client::CODE_FILE_NOT_FOUND,Client::CODE_DIR_NOT_FOUND]) || $tries >= $this->getRetries()) throw new IOException($e->getMessage(), $e->getCode());
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
		if(!file_exists(dirname($destination))) throw new IOException("Destination path not found ($destination).");
		if(file_exists($destination) && is_dir($destination)) $destination .= "/". basename($source);

		$this->_retries(function() use ($client, $source, $destination) {

			try {
				$client->download($this->getRealPath($source), $destination);
			} catch(ClientException $e) {
				throw new IOException($e->getMessage());
			}

		}, "Failed downloading file \"$source\"");


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
		if(!$this->getClient()) throw new RegistrationException("Unable to retrieve google drive service");
		return new ChunkedDownload($this, $this->getRealPath($source), $destination);
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
	public function copyFileToRemote(string $source, string $destination, ?string $data=null):?string {

		if(!($client = $this->getClient())) throw new RegistrationException("Unable to retrieve google drive service");
		// create parent dir if needed and return the parent dir id
		$this->createDir(dirname($destination), true, $data);
		return $this->_retries(function() use ($client, $source, $destination) {
			if(!file_exists($source)) throw new IOException("The file $source doesn't exists, looks like this file has vanished");
			try {
				if (filesize($source) === 0) {
					$client->createEmptyFile($this->getRealPath($destination));
				} else {
					$client->upload(new FileStream($source), $this->getRealPath($destination));
				}
			} catch(ClientException $e) {
				throw new IOException($e->getMessage());
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
	 * @throws IOException
	 * @throws JBException
	 * @throws RegistrationException
	 */
	public function copyFileToRemoteChunked(string $source, string $destination, ?string $data=null):DestinationChunkedUpload {
		if(!$this->getClient()) throw new RegistrationException("Unable to retrieve google drive service");
		$this->getlogController()->logDebug("[copyFileToRemoteChunked] Copying file $source to $destination");

		// create parent dir if needed and return the parent dir id
		$this->createDir(dirname($destination), true, $data);

		return new ChunkedUpload($this, $this->getRealPath($destination));
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
		return new DirIterator($this, $directory);
	}

	/**
	 * @return iDestinationDiskUsage|null
	 */
	public function getDiskInfo():?iDestinationDiskUsage {
		$usage = new DestinationDiskUsage();
		
		try {
			$about = $this->getClient()->getAccountInfo();
		} catch(Exception $e) {
			return $usage;
		}

		if(!isset($about->usedquota) || !isset($about->quota)) return $usage;
		
		$usage->setUsageSpace($about->usedquota);
		$usage->setTotalSpace($about->quota);
		$usage->setFreeSpace($about->quota - $about->usedquota);
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
		if(!$this->_client || $renew) {
			$this->_client = new Client();
			$this->_client->setLogController($this->getLogController());
			$this->_client->setChunkSize($this->getChunkSize());
			if(!$this->getAccessToken()) {
				
				try {

					$details = json_decode(base64_decode($this->getAccessCode()));

					if(!$details || !isset($details->code) || !isset($details->hostname))
						throw new IOException("Invalid access code provided");
					
					$this->setAccessCode($details->code);
					$this->setAPIUrl($details->hostname);

					$this->_client->setAuthorizationCode($this->getAccessCode());
					$this->_client->setAPIUrl($this->getAPIUrl());

					$response = $this->_client->fetchToken();
					
					if(!isset($response->access_token) || !$response->access_token) 
						throw new IOException("No access token was received while authenticating your pCloud account");

					$this->setAccessToken($response->access_token);
					$this->setAccessCode('');
					$this->save();

				} catch(ClientException $e) {
					throw new IOException($e->getMessage());
				}
			}

			$this->_client->setAccessToken($this->getAccessToken());
			$this->_client->setAPIUrl($this->getAPIUrl());
		}
		
		return $this->_client;
	}

	public function getFileStat(string $file):?iDestinationFile {
		try {
			$file = $this->getRealPath($file);
			$fileMetadata = $this->getClient()->getFileInfo($file);
			$fileMetadata = $fileMetadata->metadata;
			$fileObject = new DestinationFile();
			$fileObject->setName($fileMetadata->name);
			$fileObject->setPath(dirname($file));
			$fileObject->setSize($fileMetadata->size ?? 0); // Size is 0 for directories
			$fileObject->setModifyTime(strtotime($fileMetadata->modified ?? 'now'));
			$fileObject->setType(iDestinationFile::TYPE_FILE);

			return $fileObject;

		} catch (Exception $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}

	public function protectedFields(): array { return ['token','refresh_token']; }
}