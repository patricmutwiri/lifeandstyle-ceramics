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
namespace JetBackup\JetBackupLinux;

use JetBackup\Exception\JetBackupLinuxException;

defined( '__JETBACKUP__' ) or die( 'Restricted access' );

class QueueItem extends JetBackupLinuxObject {

	const ITEM_NAME = 'QueueItem';
	
	const OWNER             = 'owner';
	const OWNER_NAME        = 'owner_name';
	const CREATED           = 'created';
	const LOG_FILE          = 'file';
	const STARTED           = 'started';
	const ENDED             = 'ended';
	const TYPE              = 'type';
	const GROUP_ID          = 'group_id';
	const PROGRESS_ID       = 'progress_id';
	const PROGRESS          = 'progress';
	const PRIORITY          = 'priority';
	const RETRIED           = 'retried';
	const STATUS            = 'status';
	const MESSAGE           = 'message';
	const DATA              = 'data';
	const EXECUTION_TIME    = 'execution_time';

	const TYPE_BACKUP           = 1<<0;
	const TYPE_RESTORE          = 1<<1;
	const TYPE_DOWNLOAD         = 1<<2;
	const TYPE_REINDEX          = 1<<3;
	const TYPE_CLONE            = 1<<4;
	const TYPE_SECURITY         = 1<<5;
	const TYPE_INTEGRITY_CHECK  = 1<<6;
	const TYPE_SNAPSHOT_DELETE  = 1<<7;
	
	const TYPES_ALL         =
		self::TYPE_BACKUP |
		self::TYPE_RESTORE |
		self::TYPE_DOWNLOAD |
		self::TYPE_REINDEX |
		self::TYPE_CLONE |
		self::TYPE_SECURITY |
		self::TYPE_INTEGRITY_CHECK |
		self::TYPE_SNAPSHOT_DELETE;

	public function __construct(?string $_id=null) {
		parent::__construct(self::ITEM_NAME);
		if($_id) $this->load($_id);
	}

	/**
	 * @return Query
	 * @throws JetBackupLinuxException
	 */
	public static function query():Query {
		return Query::api('listQueueItems');
	}
	
	/**
	 * @return string
	 */
	public function getLogFile():string { return $this->get(self::LOG_FILE); }

	/**
	 * @return string
	 */
	public function getGroupId():string { return $this->get(self::GROUP_ID); }

	/**
	 * @return string
	 */
	public function getProgressId():string { return $this->get(self::PROGRESS_ID); }

	/**
	 * @return array
	 */
	public function getProgress():array { return $this->get(self::PROGRESS, []); }

	/**
	 * @return string
	 */
	public function getOwner():string { return $this->get(self::OWNER); }

	/**
	 * @return string
	 */
	public function getExecutionTime():string { return $this->get(self::EXECUTION_TIME); }

	/**
	 * @return string
	 */
	public function getOwnerName():string { return $this->get(self::OWNER_NAME); }

	/**
	 * @return int
	 */
	public function getPriority():int { return $this->get(self::PRIORITY, 0); }

	/**
	 * @return int
	 */
	public function getActionType():int { return $this->get(self::TYPE, 0); }

	/**
	 * @return bool
	 */
	public function isRetried():bool { return !!$this->get(self::RETRIED, false); }

	/**
	 * @return int
	 */
	public function getStatus():int { return $this->get(self::STATUS, 0); }

	/**
	 * @return string
	 */
	public function getMessage():string { return $this->get(self::MESSAGE); }

	/**
	 * @return int
	 */
	public function getCreated():int { return $this->get(self::CREATED, 0); }

	/**
	 * @return int
	 */
	public function getStarted():int { return $this->get(self::STARTED, 0); }

	/**
	 * @return int
	 */
	public function getEnded():int { return $this->get(self::ENDED, 0); }

	/**
	 * @return string
	 */
	public function getItemData():string { return $this->get(self::DATA); }

	// don't do anything, this API call isn't supporting modify, create and delete
	public function save():void {}
	public function delete():void {}
	
	/**
	 * @return array
	 */
	public function getDisplay():array {

		return [
			self::ID_FIELD      => $this->getId(),
			self::OWNER         => $this->getOwner(),
			self::OWNER_NAME    => $this->getOwnerName(),
			self::CREATED       => $this->getCreated(),
			self::STARTED       => $this->getStarted(),
			self::ENDED         => $this->getEnded(),
			self::EXECUTION_TIME=> $this->getExecutionTime(),
			self::TYPE          => $this->getActionType(),
			self::GROUP_ID      => $this->getGroupId(),
			self::PRIORITY      => $this->getPriority(),
			self::STATUS        => $this->getStatus(),
			self::MESSAGE       => $this->getMessage(),
			self::PROGRESS_ID   => $this->getProgressId(),
			self::PROGRESS      => $this->getProgress(),
			self::LOG_FILE      => $this->getLogFile(),
			self::DATA          => $this->getItemData(),
		];
	}
}