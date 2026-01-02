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

use JetBackup\Destination\DestinationFile;
use JetBackup\Destination\Integration\DestinationDirIterator;
use JetBackup\Destination\Integration\DestinationFile as iDestinationFile;
use JetBackup\Exception\IOException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class DirIterator implements DestinationDirIterator {

	private DropBox $_destination;
	private string $_directory;
	private array $_items=[];
	private bool $_recursive;

	/**
	 * @param DropBox $destination
	 * @param string $directory
	 * @param bool $recursive
	 *
	 * @throws IOException
	 */
	public function __construct(DropBox $destination, string $directory, bool $recursive=false) {
		
		$this->_destination = $destination;
		$this->_directory = rtrim( $destination->getRealPath( $directory ), "/" );
		$this->_recursive = $recursive;

		if(!$destination->dirExists($directory)) return;
		
		$this->rewind();
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	private function _load():void {

		try {

			$this->_items = [];

			$list = null;
			
			while (!$list || $list->Body->has_more) {
				$cursor = '';
				if($list && !($cursor = $list->Body->cursor)) return;

				if($cursor) $list = $this->_destination->client('listFolderContinue',$cursor);
				else $list = $this->_destination->client('listFolder', $this->_directory, $this->_recursive);

				foreach($list->Body->entries as $item) $this->_items[$item->name] = $item;
			}
			
			krsort($this->_items, SORT_STRING);
			
		} catch(\Exception $e) {
			throw new IOException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @return void
	 * @throws IOException
	 */
	public function rewind():void { $this->_load(); }

	/**
	 * @return bool
	 */
	public function hasNext():bool { return $this->_items && sizeof($this->_items); }

	/**
	 * @throws IOException
	 * @return iDestinationFile|null
	 */
	public function getNext():?iDestinationFile {
		if(!$this->hasNext()) return null;
		
		$nextfile = array_pop($this->_items);

		$path = $this->_destination->removeRealPath('/' . trim($nextfile->path_display, '/'));
		if(strpos($path, '/') !== false) $path = preg_replace("/^\/+/", "", dirname($path));
		else $path = '';   
		
		$is_dir = $nextfile->{'.tag'} == 'folder';
		
		$file = new DestinationFile();
		$file->setType($is_dir ? iDestinationFile::TYPE_DIRECTORY : iDestinationFile::TYPE_FILE);
		$file->setName($nextfile->name);
		$file->setPath('/' . $path);
		$file->setSize($is_dir ? 4096 : $nextfile->size);
		if(!$is_dir) $file->setModifyTime(strtotime($nextfile->client_modified));
		
		return $file;
	}
}