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
namespace JetBackup\Destination\Vendors\GoogleDrive\Client;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class ListFiles {
	
	private array $_files=[];
	private ?string $_nextPageToken=null;

	/**
	 * @param string|null $token
	 *
	 * @return void
	 */
	public function setNextPageToken(?string $token):void { $this->_nextPageToken = $token; }

	/**
	 * @return string|null
	 */
	public function getNextPageToken():?string { return $this->_nextPageToken; }

	/**
	 * @param File[] $files
	 *
	 * @return void
	 */
	public function setFiles(array $files):void { $this->_files = $files; }

	/**
	 * @return File[]
	 */	
	public function getFiles():array { return $this->_files; }

	/**
	 * @param File $file
	 *
	 * @return void
	 */
	public function addFile(File $file):void { 
		$this->_files[] = $file; 
	}
}