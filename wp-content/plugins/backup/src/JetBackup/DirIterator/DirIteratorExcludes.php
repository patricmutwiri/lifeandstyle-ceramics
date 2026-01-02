<?php

namespace JetBackup\DirIterator;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

// Shows "file manager" for the excludes

use DateTime;
use DirectoryIterator;
use Exception;
use JetBackup\Config\System;
use JetBackup\JetBackup;

class DirIteratorExcludes {

	private string $_source;

	public function __construct() {

	}

	public function setSource( $source ) {
		$this->_source = $source;
	}

	public function ListTree($files, &$currentPosition, $skip): array {

		$totalNumbers = count($files);
		$numbersPerPage = 10;
		$newPosition = $currentPosition + $skip;
		$currentPosition = max(0, min($newPosition, $totalNumbers - $numbersPerPage));
		return array_slice($files, $currentPosition, $numbersPerPage);

	}


	/**
	 * @throws Exception
	 */
	public function ExcludeTree ($path = null): array {

		$list = array();
		$_is_windows = System::isWindowsOS();

		if ($path) {

			if ($_is_windows) {

				$_path = explode('\\', $path);
				$_clean_value = '';

				foreach ($_path as $value) {

					if (!trim($value) || $value == '') continue;

					$value  = ltrim(rtrim($value, '/'), '/');
					$value  = ltrim(rtrim($value, '\\'), '\\');

					$_clean_value .= $value . '\\';

				}

				$this->_source = rtrim($_clean_value, JetBackup::SEP);

			} else {

				$this->_source = ltrim( rtrim( $this->_source, JetBackup::SEP ), JetBackup::SEP );
				$path          = ltrim( rtrim( $path, JetBackup::SEP ), JetBackup::SEP );
				if ( strpos( $path, basename( $this->_source ) ) !== false ) {
					$path = str_replace( basename( $this->_source ), '', $path );
				}
				$this->_source = JetBackup::SEP . $this->_source . $path;
			}
		}

		$iterator = new DirectoryIterator($this->_source);
		foreach ($iterator as $fileinfo) {
			$file = array();

			if ($fileinfo->isDot()) continue;

			if ($fileinfo->isFile()) {

				$file['icon'] = 'file';
				$file['type'] = 'File';
			}

			if ($fileinfo->isDir()) {

				$file['icon'] = 'dir';
				$file['type'] = 'Directory';
			}

			$file['name'] = $fileinfo->getFilename();
			$file['size'] = $fileinfo->getSize();
			$file['created'] = (new DateTime())->setTimestamp($fileinfo->getMTime())->format('Y-m-d H:i:s');
			$file['fullpath'] = $fileinfo->getPath();
			$file['hash'] = md5($file['type'].'|'.$fileinfo->getPath().'|'.$fileinfo->getFilename());

			$list[] = $file;

		}


		return $list;


	}

}