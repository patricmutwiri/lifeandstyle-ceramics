<?php 

namespace JetBackup\Destination\Vendors\S3;

use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\DestinationWrapper;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationDiskUsage as iDestinationDiskUsage;
use JetBackup\Destination\Integration\DestinationChunkedDownload;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Destination\Integration\DestinationChunkedUpload;
use JetBackup\Destination\Vendors\S3\Client\ClientManager;
use JetBackup\Destination\Vendors\S3\Client\Exception\ClientException;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\FieldsValidationException;
use JetBackup\Exception\IOException;
use JetBackup\Exception\RegistrationException;
use JetBackup\Wordpress\Wordpress;
use stdClass;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');


class S3 extends DestinationWrapper {

	const TYPE = 'S3';
	const MIN_CHUNK_SIZE = 5242880; //multipart upload are smaller than the minimum allowed size (5MB for Amazon S3, except for the last part).

	/**
	 * @var ClientManager|null
	 */
	private ?ClientManager $_client=null;


	/**
	 * @return string
	 */
	public function getAccessKey():string { return $this->getOptions()->get('access_key'); }

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	private function setAccessKey(string $key):void { $this->getOptions()->set('access_key', trim($key)); }

	/**
	 * @return string
	 */
	public function getSecretKey():string { return $this->getOptions()->get('secret_key'); }

	/**
	 * @param string $key
	 *
	 * @return void
	 */
	private function setSecretKey(string $key):void { $this->getOptions()->set('secret_key', trim($key)); }

	/**
	 * @return string
	 */
	public function getRegion():string { return $this->getOptions()->get('region'); }

	/**
	 * @param string $region
	 *
	 * @return void
	 */
	private function setRegion(string $region):void { $this->getOptions()->set('region', $region); }

	/**
	 * @return string
	 */
	public function getBucket():string { return $this->getOptions()->get('bucket'); }

	/**
	 * @param string $bucket
	 *
	 * @return void
	 */
	private function setBucket(string $bucket):void { $this->getOptions()->set('bucket', $bucket); }

	/**
	 * @return string
	 */
	public function getEndpoint():string { return $this->getOptions()->get('endpoint'); }

	/**
	 * @param string $endpoint
	 *
	 * @return void
	 */
	private function setEndpoint(string $endpoint):void { $this->getOptions()->set('endpoint', $endpoint); }

	/**
	 * @return bool
	 */
	public function getVerifySSL():bool { return !!$this->getOptions()->get('verifyssl', false); }

	/**
	 * @param bool $verifyssl
	 *
	 * @return void
	 */
	private function setVerifySSL(bool $verifyssl):void { $this->getOptions()->set('verifyssl', !!$verifyssl); }

	/**
	 * @return int
	 */
	public function getRetries():int { return $this->getOptions()->get('retries', 3); }

	/**
	 * @param int $retries
	 *
	 * @return void
	 */
	private function setRetries(int $retries):void { $this->getOptions()->set('retries', $retries); }

	/**
	 * @return int
	 */
	public function getKeepAliveTimeout():int { return $this->getOptions()->get('keepalive_timeout', 60); }

	/**
	 * @param int $timeout
	 *
	 * @return void
	 */
	private function setKeepAliveTimeout(int $timeout):void { $this->getOptions()->set('keepalive_timeout', $timeout); }
	private function setSelectedVendor(string $vendor):void { $this->getOptions()->set('selected_vendor', $vendor); }

	public function getSelectedVendor():string { return $this->getOptions()->get('selected_vendor'); }

	private function setQuickAccessCode(string $code):void { $this->getOptions()->set('quick_access_code', $code); }

	public function getQuickAccessCode():string { return $this->getOptions()->get('quick_access_code'); }
	/**
	 * @return int
	 */
	public function getKeepAliveRequests():int { return $this->getOptions()->get('keepalive_requests', 100); }

	/**
	 * @param int $requests
	 *
	 * @return void
	 */
	private function setKeepAliveRequests(int $requests):void { $this->getOptions()->set('keepalive_requests', $requests); }

	/**
	 * @return stdClass
	 */
	public function getExtraFields(): stdClass { return $this->getOptions()->get('extrafields', new stdClass()); }

	/**
	 * @param stdClass $fields
	 *
	 * @return void
	 */
	private function setExtraFields( stdClass $fields):void { $this->getOptions()->set('extrafields', $fields); }

	/**
	 * @return string
	 */
	private function _getParsedEndpoint():string {
		$endpoint = $this->getEndpoint();
		if(!$this->getExtraFields()) return $endpoint;
		foreach($this->getExtraFields() as $key => $value) $endpoint = str_replace('{' . $key . '}', $value, $endpoint);
		return $endpoint;
	}


	/**
	 * @return ClientManager
	 */
	public function getClient():ClientManager {
		if(!$this->_client) {
			$this->_client = new ClientManager($this->getLogController());
			$this->_client->setAccessKey($this->getAccessKey());
			$this->_client->setSecretKey($this->getSecretKey());
			$this->_client->setRegion($this->getRegion());
			$this->_client->setBucket($this->getBucket());
			$this->_client->setEndpoint($this->_getParsedEndpoint());
			$this->_client->setVerifySSL($this->getVerifySSL());
			$this->_client->setKeepAliveTimeout($this->getKeepAliveTimeout());
			$this->_client->setKeepAliveRequests($this->getKeepAliveRequests());
			$this->_client->setRetries($this->getRetries());
			$this->_client->setChunkSize($this->getChunkSize());
		}
		return $this->_client;
	}

	/**
	 * @return void
	 * @throws ConnectionException
	 */
	public function connect():void {
		try {
			$this->getClient()->listObjects('', 1);
		} catch(ClientException $e) {
			throw new ConnectionException($e->getMessage(), $e->getCode(), $e);
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
			$this->getClient()->createObject($this->getRealPath('/.writecheck'));
			$this->getClient()->deleteObject($this->getRealPath('/.writecheck'));
		} catch( ClientException $e) {
			throw new RegistrationException($e->getMessage(), $e->getCode(), $e);
		}
	}

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

		if(!$this->getAccessKey()) throw new FieldsValidationException("No access key provided");
		if(!$this->getSecretKey()) throw new FieldsValidationException("No secret key provided");
		if(!$this->getBucket()) throw new FieldsValidationException("No bucket provided");
		if(!preg_match("/^[a-z0-9][a-z0-9\-.]{2,62}$/", $this->getBucket())) throw new FieldsValidationException("Invalid bucket provided. Bucket name must be lowercase and start with a letter or number. They can be between 3 and 63 characters long and may contain dashes and periods.");
		if(!$this->getEndpoint()) throw new FieldsValidationException("No endpoint provided");
		if($this->getRetries() > 10 || $this->getRetries() < 0) throw new FieldsValidationException("Invalid retries provided. Minimum 0 and Maximum 10");
		if($this->getChunkSize() < self::MIN_CHUNK_SIZE) throw new FieldsValidationException("Invalid chunk size provided. Minimum 5MB");
		if($this->getKeepAliveTimeout() > 600 || $this->getKeepAliveTimeout() < 0) throw new FieldsValidationException("Invalid keep alive timeout provided. Minimum 0 seconds (disabled) and Maximum 600 seconds");
		if($this->getKeepAliveRequests() > 1000 || $this->getKeepAliveRequests() < 0) throw new FieldsValidationException("Invalid keep alive requests provided. Minimum 0 requests (Determined by the remote vendor) and Maximum 1000 requests");
		if(Wordpress::strContains($this->getEndpoint(), '{region}') && !$this->getRegion()) throw new FieldsValidationException("No region provided");
	}

	/**
	 * @param object $data
	 *
	 * @return void
	 */
	public function setData(object $data):void {

		if(isset($data->selected_vendor)) $this->setSelectedVendor($data->selected_vendor);
		if(isset($data->quick_access_code)) $this->setQuickAccessCode($data->quick_access_code);

		if ($this->getSelectedVendor() === 'jetstorage' && $this->getQuickAccessCode()) {

			if (($decoded = base64_decode($this->getQuickAccessCode(), true)) === false) throw new FieldsValidationException("Invalid Base64 in access code");
			if (($uncompressed = @gzdecode($decoded)) === false) throw new FieldsValidationException("Failed to decompress access code");
			if (!is_object($quick_access = json_decode($uncompressed))) throw new FieldsValidationException("Access code does not contain valid JSON");
			foreach (['access_key', 'secret_key', 'region', 'bucket'] as $field) if (!property_exists($quick_access, $field) || empty($quick_access->$field)) throw new FieldsValidationException("Missing field '$field' in access code");

			$this->setAccessKey($quick_access->access_key);
			$this->setSecretKey($quick_access->secret_key);
			$this->setRegion($quick_access->region);
			$this->setBucket($quick_access->bucket);
			$this->setEndpoint($quick_access->region.'.storage.jetbackup.com');

		} else {

			if(isset($data->access_key)) $this->setAccessKey($data->access_key);
			if(isset($data->secret_key)) $this->setSecretKey($data->secret_key);
			if(isset($data->region)) $this->setRegion($data->region);
			if(isset($data->bucket)) $this->setBucket($data->bucket);
			if(isset($data->endpoint)) $this->setEndpoint($data->endpoint);

		}


		if(isset($data->verifyssl)) $this->setVerifySSL(!!$data->verifyssl);
		if(isset($data->retries)) $this->setRetries(intval($data->retries));
		if(isset($data->extrafields)) $this->setExtraFields((object) $data->extrafields);
		if(isset($data->keepalive_timeout)) $this->setKeepAliveTimeout(intval($data->keepalive_timeout));
		if(isset($data->keepalive_requests)) $this->setKeepAliveRequests(intval($data->keepalive_requests));
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
		return  true;
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
			$this->getLogController()->logDebug("[fileExists] file exist for $file");
			return !$this->getClient()->getObject($this->getRealPath($file))->isDir();
		} catch(ClientException $e) {
			if($e->getCode() == 404) return false;
			throw new IOException($e->getMessage());
		}
	}

	/**
	 * @param string $directory
	 * @param bool $recursive
	 * @param string|null $data
	 *
	 * @return string|null
	 */
	public function createDir( string $directory, bool $recursive, ?string $data=null):?string {
		return null;
	}

	/**
	 * @param string $directory
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeDir( string $directory, ?string $data=null):void {
		if(!str_ends_with($directory, '/')) $directory .= '/';
		$this->getLogController()->logDebug("[removeDir] Remove directory $directory");
		$checked = [];
		$queue = [$directory];

		while($queue) {
			$dir = array_pop($queue);

			$iterator = $this->listDir($dir);

			if(!$iterator->hasNext() || isset($checked[$dir])) {
				$this->removeFile($dir);
				unset($checked[$dir]);
				continue;
			}

			$queue[] = $dir;
			$checked[$dir] = true;

			while($iterator->hasNext()) {
				$file = $iterator->getNext();

				if( $file->getType() == iDestinationFile::TYPE_DIRECTORY) {
					$dir = $file->getFullPath() . ((!str_ends_with($file->getFullPath(), '/')) ? '/' : '');
					$queue[] = $dir;
					continue;
				}

				$this->removeFile($file->getFullPath());
			}
		}
	}

	/**
	 * @param string $file
	 * @param string|null $data
	 *
	 * @return void
	 * @throws IOException
	 */
	public function removeFile(string $file, ?string $data=null):void {
		try {
			$this->getLogController()->logDebug("[removeFile] Remove file $file");
			$this->getClient()->deleteObject($this->getRealPath($file));
		} catch(ClientException $e) {
			if($e->getCode() == 404) return;
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
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
		try {
			$this->getLogController()->logDebug("[copyFileToLocal] Downloading file $source -> $destination");
			$this->getClient()->getObject($this->getRealPath($source), $destination);
		} catch(ClientException $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @param string $source
	 * @param string|null $data
	 *
	 * @return DestinationChunkedDownload
	 */
	public function copyFileToLocalChunked(string $source, string $destination, ?string $data=null):DestinationChunkedDownload {
		$this->getLogController()->logDebug("[copyFileToLocalChunked] Downloading chunked file $source -> $destination");
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
			$this->getLogController()->logDebug("[copyFileToRemote] Copy file $source -> $destination");
			$this->getClient()->putObject($source, $this->getRealPath($destination));
		} catch(ClientException $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
		return null;
	}

	/**
	 * @param string $destination
	 *
	 * @return DestinationChunkedUpload
	 */
	public function copyFileToRemoteChunked(string $source, string $destination, ?string $data=null):DestinationChunkedUpload {
		$this->getLogController()->logDebug("[copyFileToRemoteChunked] Copy Chunked file $source -> $destination");
		return new ChunkedUpload($this->getClient(), $this->getRealPath($destination));
	}

	/**
	 * @throws IOException
	 */
	public function listDir(string $directory, ?string $data=null):DestinationDirIterator {
		$this->getLogController()->logDebug("[listDir] Requested listDir for $directory");
		return new DirIterator($this, $directory);
	}

	/**
	 * @return iDestinationDiskUsage|null
	 */
	public function getDiskInfo():?iDestinationDiskUsage {
		return null;
	}

	/**
	 * @param string $file
	 *
	 * @return iDestinationFile|null
	 * @throws IOException
	 */
	public function getFileStat(string $file):?iDestinationFile {
		try {

			$this->getLogController()->logDebug("[getFileStat] Requested stat for $file");
			if(!($object = $this->getClient()->getObject($this->getRealPath($file)))) return null;

			$dirname = '';

			if(Wordpress::strContains($object->getKey(), '/')) {
				$remove = preg_replace("/^\/+/", "", $this->getRealPath('/'));
				$dirname = substr($object->getKey(), strlen($remove), strrpos($object->getKey(), '/'));
			}

			$file = new DestinationFile();
			$file->setName(basename($object->getKey()));
			$file->setPath('/' . preg_replace("/^\/+/", "", $dirname));
			$file->setModifyTime($object->getMtime());
			$file->setSize($object->isDir() ? 4096 : $object->getSize());
			$file->setType($object->isDir() ? DestinationFile::TYPE_DIRECTORY : DestinationFile::TYPE_FILE);

			return $file;
		} catch(ClientException $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	public function protectedFields():array { return ['secret_key']; }
}