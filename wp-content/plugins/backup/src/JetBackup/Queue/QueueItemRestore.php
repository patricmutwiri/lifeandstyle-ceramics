<?php

namespace JetBackup\Queue;

if (!defined('__JETBACKUP__')) die('Direct access is not allowed');

class QueueItemRestore extends aQueueItem {

	const SNAPSHOT_ID = 'snapshot_id';
	const SNAPSHOT_PATH = 'snapshot_path';
	const QUEUE_GROUP_ID = 'queue_group_id';
	const RESTORE_URL = 'restore_url';
	const OPTIONS = 'options';
	const EXCLUDE_DATABASE = 'exclude_database';
	const INCLUDE_DATABASE = 'include_database';
	const EXCLUDE = 'exclude';
	const INCLUDE = 'include';
	const ADMIN_USER = 'admin_user';
	const FILE_MANAGER = 'file_manager';

	// General restore
	const OPTION_RESTORE_DATABASE_ENTIRE    = 1 << 0;  // import full db dump, no includes/excludes
	const OPTION_RESTORE_FILES_ENTIRE      = 1 << 1; // restore full homedir path, no includes/excludes

	// Multisite Restore Types
	const OPTION_MULTISITE_ENTIRE_NETWORK = 1 << 2;
	const OPTION_MULTISITE_SPECIFIC_SITE  = 1 << 3;

	// Restore Methods
	const OPTION_MULTISITE_AS_DOMAIN      = 1 << 4;
	const OPTION_MULTISITE_STAND_ALONE    = 1 << 5;

	// Database Restore Options

	const OPTION_RESTORE_DATABASE_EXCLUDE = 1 << 6; // restore tables, without excluded listed
	const OPTION_RESTORE_DATABASE_SKIP    = 1 << 7; // DO NOT restore database (skip the step)
	const OPTION_RESTORE_DATABASE_INCLUDE    = 1 << 8; // restore only selected db tables

	// Files Restore Options
	const OPTION_RESTORE_FILES_EXCLUDE    = 1 << 9;  // restore homedir, without excluded listed
	const OPTION_RESTORE_FILES_SKIP       = 1 << 10; // DO NOT restore database (skip the step)
	const OPTION_RESTORE_FILES_INCLUDE       = 1 << 11;  // restore only selected files/folders

	public function setSnapshotId(int $_id): void { $this->set(self::SNAPSHOT_ID, $_id); }
	public function getSnapshotId(): int { return (int) $this->get(self::SNAPSHOT_ID, 0); }

	public function setSnapshotPath(string $path): void { $this->set(self::SNAPSHOT_PATH, $path); }
	public function getSnapshotPath(): string { return $this->get(self::SNAPSHOT_PATH); }

	public function setQueueGroupId(string $id): void { $this->set(self::QUEUE_GROUP_ID, $id); }
	public function getQueueGroupId(): string { return $this->get(self::QUEUE_GROUP_ID); }

	public function setRestoreURL(string $url): void { $this->set(self::RESTORE_URL, $url); }
	public function getRestoreURL(): string { return $this->get(self::RESTORE_URL); }

	public function setOptions(int $options): void { $this->set(self::OPTIONS, $options); }
	public function getOptions(): int { return $this->get(self::OPTIONS, 0); }

	public function setExcludes(array $exclude): void { $this->set(self::EXCLUDE, $exclude); }
	public function getExcludes(): array { return $this->get(self::EXCLUDE, []); }
	public function setIncludes(array $include): void { $this->set(self::INCLUDE, $include); }
	public function getIncludes(): array { return $this->get(self::INCLUDE, []); }

	public function setAdminUser(string $user): void { $this->set(self::ADMIN_USER, $user); }
	public function getAdminUser(): string { return $this->get(self::ADMIN_USER); }

	public function setExcludedDatabases(array $exclude): void { $this->set(self::EXCLUDE_DATABASE, $exclude); }
	public function getExcludedDatabases(): array { return $this->get(self::EXCLUDE_DATABASE, []); }

	public function setIncludedDatabases(array $include): void { $this->set(self::INCLUDE_DATABASE, $include); }
	public function getIncludedDatabases(): array { return $this->get(self::INCLUDE_DATABASE, []); }

	public function setFileManager(array $files): void { $this->set(self::FILE_MANAGER, $files); }
	public function getFileManager(): array { return $this->get(self::FILE_MANAGER, []); }

	public function isOption(int $option): bool { return ($this->getOptions() & $option); }
	public function isRestoreDatabase(): bool { return !$this->isOption(self::OPTION_RESTORE_DATABASE_SKIP); }
	public function isRestoreHomedir(): bool { return !$this->isOption(self::OPTION_RESTORE_FILES_SKIP); }

	public function getDisplay(): array {
		return [
			self::SNAPSHOT_ID       => $this->getSnapshotId(),
			self::SNAPSHOT_PATH     => $this->getSnapshotPath(),
			self::QUEUE_GROUP_ID    => $this->getQueueGroupId(),
			self::RESTORE_URL       => $this->getRestoreURL(),
			self::OPTIONS           => $this->getOptions(),
			self::EXCLUDE           => $this->getExcludes(),
			self::EXCLUDE_DATABASE  => $this->getExcludedDatabases(),
			self::INCLUDE_DATABASE  => $this->getIncludedDatabases(),
			self::FILE_MANAGER      => $this->getFileManager(),
		];
	}
}