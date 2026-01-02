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
namespace JetBackup\Archive\File;

use JetBackup\Archive\Data\SetterGetter;
use JetBackup\Archive\Header\Header;
use JetBackup\Entities\Util;
use JetBackup\Exception\ArchiveException;
use JetBackup\JetBackup;
use JetBackup\Wordpress\Wordpress;

class FileInfo extends SetterGetter {

	private $_is_socket;
	
    /**
     * initialize dynamic defaults
     *
     * @param string $path The path of the file, can also be set later through setPath()
     */
    public function __construct(string $path='') {
		parent::__construct();
		$this->setPath($path);
    }
	
    /**
     * Factory to build FileInfo from existing file or directory
     *
     * @param string $path path to a file on the local file system
     * @param string $as   optional path to use inside the archive
     * @throws ArchiveException
     * @return FileInfo
     */
    public static function fromPath(string $path, string $as=''):FileInfo {
        clearstatcache(false, $path);

        if (!file_exists($path)) {
            throw new ArchiveException("$path does not exist");
        }

        $stat = lstat($path);
        $file = new FileInfo();

		$group = Util::getgrgid($stat['gid']);
	    $group = $group ? $group['name'] : '';

		$user = Util::getpwuid($stat['uid']);
	    $user = $user ? $user['name'] : '';

		// If user set alternate wp-content folder, we will put it in its original location inside the archive
	    // We will handle re-creating links during the restore procedure
	    $isAlternateWpContent = Wordpress::getAlternateContentDir() && basename(Wordpress::getAlternateContentDir()) === basename($path) && is_link($path) ;

        $file->setPath($path);
        $file->setMode($isAlternateWpContent ? 0040755 : $stat['mode']);
        $file->setOwner($user);
        $file->setGroup($group);
        $file->setSize($stat['size']);
        $file->setUid($stat['uid']);
        $file->setGid($stat['gid']);
        $file->setMtime($stat['mtime']);
		// get the device type (major and minor)
	    if($stat['rdev']) {
			$file->setDevMajor(($stat['rdev'] >> 8) & 0xFF);
		    $file->setDevMinor($stat['rdev'] & 0xFF);
	    }

	    if(!$isAlternateWpContent && $file->isLink() && ($link_source = @readlink($path)) !== false) $file->setLink($link_source);

		if($stat['size'] > 0) {
			// Calculate the maximum number of blocks that could be allocated
			$maxBlocks = ceil($stat['size'] / $stat['blksize']);

			// Calculate sparseness as the ratio of allocated blocks to the maximum possible blocks
			$sparseness = ($maxBlocks > 0) ? ($stat['blocks'] / $maxBlocks) : 1.0;

			$file->setSparseness($sparseness);
		}

	    if ($as) $file->setPath($as);

        return $file;
    }

    public function getSize(): int { return !$this->isDir() && !$this->isLink() ? $this->get('size', 0) : 0; }
    public function setSize(int $size):void { $this->set('size', $size); }

    public function getCompressedSize(): int { return $this->get('csize', 0); }
    public function setCompressedSize(int $csize):void { $this->set('csize', $csize); }

    public function getMtime(): int { return $this->get('mtime', time()); }
    public function setMtime(int $mtime):void { $this->set('mtime', $mtime); }

    public function getGid(): int { return $this->get('gid', 0); }
    public function setGid(int $gid):void { $this->set('gid', $gid); }

    public function getUid(): int { return $this->get('uid', 0); }
    public function setUid(int $uid):void { $this->set('uid', $uid); }

    public function getComment(): string { return $this->get('comment'); }
    public function setComment(string $comment):void { $this->set('comment', $comment); }

	public function getOwner(): string { return $this->get('owner'); }
	public function setOwner(string $owner):void { $this->set('owner', $owner); }

    public function getGroup(): string { return $this->get('group'); }
    public function setGroup(string $group):void { $this->set('group', $group); }

	public function getFlagType(): string { return $this->get('flag', Header::REGTYPE); }
	public function setFlagType(string $flag):void { $this->set('flag', $flag); }

	public function getLink(): string { return $this->get('link'); }
	public function setLink(string $link):void { $this->set('link', $link); }

	public function getDevMajor(): string { return $this->get('device_major'); }
	public function setDevMajor(string $device):void { $this->set('device_major', $device); }

	public function getDevMinor(): string { return $this->get('device_minor'); }
	public function setDevMinor(string $device):void { $this->set('device_minor', $device); }

	public function getPath(): string { return $this->get('path'); }
	public function setPath(string $path):void { $this->set('path', $this->cleanPath($path)); }

	public function getSparseness(): float { return $this->get('sparseness', 0.0); }
	public function setSparseness(float $sparseness):void { $this->set('sparseness', $sparseness); }

    public function isDir():bool { return $this->getFlagType() == Header::DIRTYPE; }
    public function isLink():bool { return $this->getFlagType() == Header::SYMTYPE; }
    public function isSocket():bool { return !!$this->_is_socket; }

    public function getMode(): int { return $this->get('mode', 33188); }
    public function setMode(int $mode):void {
		switch($mode & 0170000) {
			//default: throw new ArchiveException("Failed to determent the file type");
			case 0100000: $this->setFlagType(Header::REGTYPE); break; // regular file
			// add '\0' -> AREGTYPE // regular file
			// add '1' -> LNKTYPE // link (hrwxrwxrwx 2.txt link to 1.txt)
			case 0120000: $this->setFlagType(Header::SYMTYPE); break; // reserved (symlink)
			case 0020000: $this->setFlagType(Header::CHRTYPE); break; // character special
			case 0060000: $this->setFlagType(Header::BLKTYPE); break; // block special
			case 0040000: $this->setFlagType(Header::DIRTYPE); break; // directory
			case 0010000: $this->setFlagType(Header::FIFOTYPE); break; // FIFO special
			// add '7' -> CONTTYPE // reserved 
			case 0140000: $this->_is_socket = true; break; // FIFO special
		}

	    $this->set('mode', $mode);
    }

    /**
     * Cleans up a path and removes relative parts, also strips leading slashes
     *
     * @param string $path
     * @return string
     */
    protected function cleanPath(string $path):string {
        $path = Util::normalizePath($path);
        $path = explode(JetBackup::SEP , $path);
        $newpath = array();
        foreach ($path as $p) {
            if ($p === '' || $p === '.') {
                continue;
            }
            if ($p === '..') {
                array_pop($newpath);
                continue;
            }
	        $newpath[] = $p;
        }
        return trim(implode(JetBackup::SEP, $newpath), JetBackup::SEP);
    }
}