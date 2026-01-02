<?php

namespace JetBackup\DirIterator;

use JetBackup\Exception\DirIteratorException;
use JetBackup\Exception\DirIteratorFileVanishedException;
use JetBackup\Filesystem\File;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class DirIteratorFile {

	private $_file;
	private $_filesize;
	private $_archive_position;

	/**
	 * @throws DirIteratorFileVanishedException
	 * @throws DirIteratorException
	 */
	public function __construct($filename) {
		// Debug: Log the initial filename
		//error_log("Initial filename: $filename");

		$filename = preg_replace_callback("/" . DirIterator::POSITION_EOL_REGEX . "/", function($match) {
			switch($match[1]) {
				case 'r': return "\r";
				case 'n': return "\n";
			}
			return '';
		}, $filename);

		// Debug: Log the filename after EOL replacement
		//error_log("Filename after EOL replacement: $filename");


		$status = $this->cleanFilename($filename);
		$this->_archive_position = $status->pos;
		$filename = $status->filename;

		if(!file_exists($filename)) {
			throw new DirIteratorFileVanishedException("The file '$filename' has vanished");
		}

		$this->_file = new File($filename);
		$this->_filesize = $this->_file->size();
	}


	/**
	 * @throws DirIteratorException
	 */
	private function cleanFilename($filename): \stdClass {

		// Debug: Log the initial filename
		//error_log("cleanFilename Initial filename: $filename");

		// Regular expression to find the first occurrence of {pos: and capture everything after it
		$pattern = "/\{pos:\d+.*$/";

		// Find the first occurrence of {pos: and everything after it
		if (preg_match($pattern, $filename, $matches)) {
			// Debug: Log the match found
			//error_log("cleanFilename Match found: " . print_r($matches[0], true));

			// Extract the matched substring
			$matchedSubstring = $matches[0];

			// Calculate the length of the match
			$matchLength = strlen($matchedSubstring);

			// Extract the position value
			preg_match("/\d+/", $matchedSubstring, $posMatches);
			$lastPos = end($posMatches);

			// Remove the matched substring from the original filename
			$cleanedFilename = substr($filename, 0, -$matchLength);

			// Ensure no trailing spaces or curly braces are left
			$cleanedFilename = trim($cleanedFilename);

			// Debug: Log the cleaned filename and position
			//error_log("cleanFilename Cleaned filename: $cleanedFilename with pos: $lastPos");

			// Initialize the return object
			$result = new \stdClass();
			$result->filename = $cleanedFilename;
			$result->pos = $lastPos;
			return $result;

		} else {

			throw new DirIteratorException('Could not determine archive seek position for ' . $filename);

		}


	}




	public function getName() { return $this->_file->path(); }
	public function getSize() { return $this->_filesize; }
	public function getArchivePosition() { return $this->_archive_position; }

	public static function safe_filesize($filename) : int {
		// Check if the filename contains a null byte
		if (strpos($filename, "\0") !== false) return 0;
		return @filesize($filename);
	}
}