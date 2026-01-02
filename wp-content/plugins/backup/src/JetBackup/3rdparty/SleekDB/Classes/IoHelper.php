<?php

namespace SleekDB\Classes;

use Closure;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SleekDB\Exceptions\IOException;
use SleekDB\Exceptions\JsonException;

/**
 * Class IoHelper
 * Helper to handle file input/ output.
 */
class IoHelper {

	/**
	 * @param string $path
	 * @throws IOException
	 */
	public static function checkWrite(string $path)
	{
		if(file_exists($path) === false){
			$path = dirname($path);
		}
		// Check if PHP has write permission
		if (!is_writable($path)) {
			throw new IOException(
				"Directory or file is not writable at \"$path\". Please change permission."
			);
		}
	}

	/**
	 * @param string $path
	 * @throws IOException
	 */
	public static function checkRead(string $path)
	{
		// Check if PHP has read permission
		if (!is_readable($path)) {
			throw new IOException(
				"Directory or file is not readable at \"$path\". Please change permission."
			);
		}
	}


	public static function skipFile($name) {

		$skipList = array(

			'.',
			'..',
			'.htaccess',
			'index.html',
			'web.config'
		);

		return in_array($name, $skipList);
	}


	/**
	 * @throws IOException
	 */
	public static function getFileContentRaw(string $filePath): string
	{

		self::checkRead($filePath);

		if(!file_exists($filePath)) {
			throw new IOException("File does not exist: $filePath");
		}

		$content = false;
		$fp = fopen($filePath, 'rb');
		if(flock($fp, LOCK_SH)){
			$content = stream_get_contents($fp);
		}
		flock($fp, LOCK_UN);
		fclose($fp);

		if($content === false) {
			throw new IOException("Could not retrieve the content of a file. Please check permissions at: $filePath");
		}

		return $content;
	}


	/**
	 * @param string $filePath
	 * @return string
	 * @throws IOException
	 */
	public static function getFileContent(string $filePath): string
	{

		try {

			self::checkRead($filePath);

			if(!file_exists($filePath)) {
				throw new IOException("File does not exist: $filePath");
			}

			$content = false;
			$fp = @fopen($filePath, 'rb');
			if(flock($fp, LOCK_SH)){
				$content = stream_get_contents($fp);
			}
			flock($fp, LOCK_UN);
			fclose($fp);

			if($content === false) {
				throw new IOException("Could not retrieve the content of a file. Please check permissions at: $filePath");
			}

			$_swap_content = self::checkSwap($filePath);
			$_valid_json = self::validateJson($content);

			if($_swap_content && !$_valid_json) {
				self::AtomicWrite($filePath, $_swap_content);
				return $_swap_content;
			}

			return $content;

		} catch (Exception $e) {
			throw new IOException($e->getMessage(), $e->getCode());
		}

	}


	public static function isSwapFile($file): bool {

		$ending = '.swap';
		$endingLength = strlen($ending);
		if ($endingLength > strlen($file)) return false;
		$fileEnding = substr($file, -$endingLength);
		return $fileEnding === $ending;

	}

	public static function validateJson($json): bool {
		json_decode($json);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	public static function checkSwap($path) {
		$_swap_file = $path . '.swap';

		if (file_exists($_swap_file)) {
			$_swap_contents = @file_get_contents($_swap_file);
			if (self::validateJson($_swap_contents)) {
				// Ensure valid JSON before proceeding
				if (file_exists($path)) {
					@unlink($_swap_file);
					return $_swap_contents;
				}
			} else {
				// Handle invalid JSON in swap file, clean up
				@unlink($_swap_file);
			}
		}

		return null;
	}


	/**
	 * @throws IOException
	 */
	/**
	 * @throws IOException
	 */
	public static function AtomicWrite(string $path, string $content): bool
	{
		$_swap_file = $path . '.swap';

		try {

			// Write content to a temporary file
			if (file_put_contents($_swap_file, $content, LOCK_EX) === false) {
				$error = error_get_last();
				throw new IOException("Failed to write to temporary file: $_swap_file. Error: " . $error['message']);
			}


			// rename swap to target
			if (!@rename($_swap_file, $path)) {
				if (!file_exists($_swap_file) && file_exists($path)) return true;
				$error = error_get_last();
				throw new IOException("Failed to rename temporary file to target file: $path. Error: " . $error['message']);
			}

			return true;

		} catch (IOException $e) {
			throw new IOException($e->getMessage(), $e->getCode());
		}

	}


	/**
	 * @param string $filePath
	 * @param string $content
	 * @throws IOException
	 */
	public static function writeContentToFile(string $filePath, string $content){

		try {

			self::checkWrite($filePath);
			if(!self::AtomicWrite($filePath, $content)) throw new IOException("Failed writing to DB File $filePath");

		} catch (Exception $e) {
			throw new IOException($e->getMessage(), $e->getCode());
		}

	}



	/**
	 * @param string $folderPath
	 * @return bool
	 * @throws IOException
	 */
	public static function deleteFolder(string $folderPath): bool
	{
		self::checkWrite($folderPath);
		$it = new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS);
		$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
		foreach ($files as $file) {
			self::checkWrite($file);
			if ($file->isDir()) {
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}
		return rmdir($folderPath);
	}

	/**
	 * @param string $folderPath
	 * @param int $chmod
	 * @throws IOException
	 */
	public static function createFolder(string $folderPath, int $chmod){
		// We don't need to create a folder if it already exists.
		if(file_exists($folderPath) === true){
			return;
		}
		self::checkWrite($folderPath);
		// Check if the data_directory exists or create one.
		if (!file_exists($folderPath) && !mkdir($folderPath, $chmod, true) && !is_dir($folderPath)) {
			throw new IOException(
				'Unable to create the a directory at ' . $folderPath
			);
		}
	}

	/**
	 * @param string $filePath
	 * @param Closure $updateContentFunction Has to return a string or an array that will be encoded to json.
	 * @return string
	 * @throws IOException
	 * @throws JsonException
	 */
	public static function updateFileContent(string $filePath, Closure $updateContentFunction): string
	{
		self::checkRead($filePath);
		self::checkWrite($filePath);

		$content = false;

		$fp = fopen($filePath, 'rb');
		if(flock($fp, LOCK_SH)){
			$content = stream_get_contents($fp);
		}
		flock($fp, LOCK_UN);
		fclose($fp);

		if($content === false){
			throw new IOException("Could not get shared lock for file: $filePath");
		}


		$_swap_content = self::checkSwap($filePath);
		$_valid_json = self::validateJson($content);
		if($_swap_content && !$_valid_json) return $_swap_content;

		$content = $updateContentFunction($content);

		if(!is_string($content)){
			$encodedContent = json_encode($content);
			if($encodedContent === false){
				$content = (!is_object($content) && !is_array($content) && !is_null($content)) ? $content : gettype($content);
				throw new JsonException("Could not encode content with json_encode. Content: \"$content\".");
			}
			$content = $encodedContent;
		}


		if(!self::AtomicWrite($filePath, $content)) throw new IOException("Could not write content to file. Please check permissions at: $filePath");
		return $content;
	}

	/**
	 * @param string $filePath
	 * @return bool
	 */
	public static function deleteFile(string $filePath): bool
	{

		if(false === file_exists($filePath)){
			return true;
		}
		try{
			self::checkWrite($filePath);
		}catch(Exception $exception){
			return false;
		}

		return (@unlink($filePath) && !file_exists($filePath));
	}

	/**
	 * @param array $filePaths
	 * @return bool
	 */
	public static function deleteFiles(array $filePaths): bool
	{
		foreach ($filePaths as $filePath){
			// if a file does not exist, we do not need to delete it.
			if(true === file_exists($filePath)){
				try{
					self::checkWrite($filePath);
					if(false === @unlink($filePath) || file_exists($filePath)){
						return false;
					}
				} catch (Exception $exception){
					// TODO trigger a warning or exception
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Strip string for secure file access.
	 * @param string $string
	 * @return string
	 */
	public static function secureStringForFileAccess(string $string): string
	{
		return (str_replace(array(".", "/", "\\"), "", $string));
	}

	/**
	 * Appends a slash ("/") to the given directory path if there is none.
	 * @param string $directory
	 */
	public static function normalizeDirectory(string &$directory){
		if(!empty($directory) && substr($directory, -1) !== DIRECTORY_SEPARATOR) {
			$directory .= DIRECTORY_SEPARATOR;
		}
	}

	/**
	 * Returns the amount of files in folder.
	 * @param string $folder
	 * @return int
	 * @throws IOException
	 */
	public static function countFolderContent(string $folder): int
	{
		self::checkRead($folder);
		$fi = new \FilesystemIterator($folder, \FilesystemIterator::SKIP_DOTS);
		return iterator_count($fi);
	}
}