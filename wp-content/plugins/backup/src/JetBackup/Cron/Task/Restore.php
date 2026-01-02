<?php

namespace JetBackup\Cron\Task;

use __PHP_Incomplete_Class;
use Exception;
use JetBackup\BackupJob\BackupJob;
use JetBackup\Data\Engine;
use JetBackup\Data\Mysqldump;
use JetBackup\Encryption\Crypt;
use JetBackup\Entities\Util;
use JetBackup\Exception\DBException;
use JetBackup\Exception\ExecutionTimeException;
use JetBackup\Exception\JetBackupLinuxException;
use JetBackup\Exception\RestoreException;
use JetBackup\Exception\SnapshotMetaException;
use JetBackup\Exception\TaskException;
use JetBackup\Factory;
use JetBackup\Filesystem\File;
use JetBackup\JetBackup;
use JetBackup\JetBackupLinux\JetBackupLinux;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Queue\QueueItemRestore;
use JetBackup\Snapshot\Snapshot;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Restore extends Task {

	const LOG_FILENAME = 'restore';

	const PLUGINS_PATH = JetBackup::WP_ROOT_PATH . JetBackup::SEP . 'wp-content' . JetBackup::SEP . 'plugins';
	const MU_PLUGINS_PATH = JetBackup::WP_ROOT_PATH . JetBackup::SEP . 'wp-content' . JetBackup::SEP . 'mu-plugins';

	const PROTECTED_PLUGINS = ['backup']; // Add this protected attribute to store protected plugin names.
	const CACHE_PLUGINS = ['redis-cache'];

	private QueueItemRestore $_queue_item_restore;
	private Mysqldump $_mysql;
	private array $_disabled_plugins=[];

	/**
	 *
	 */
	public function __construct() {
		parent::__construct(self::LOG_FILENAME);
	}

	/**
	 * @return void
	 * @throws IOException
	 * @throws InvalidArgumentException
	 * @throws TaskException
	 * @throws ExecutionTimeException
	 */
	public function execute():void {
		parent::execute();

		$this->_queue_item_restore = $this->getQueueItem()->getItemData();

		if($this->getQueueItem()->getStatus() == Queue::STATUS_RESTORE_WAITING_FOR_RESTORE) {
			$this->getLogController()->logMessage("Starting restore");

			$this->getQueueItem()->getProgress()->setTotalItems( count(Queue::STATUS_RESTORE_NAMES) + 3);
			$this->getQueueItem()->save();
			$this->getQueueItem()->updateProgress('Starting restore');


		} else if($this->getQueueItem()->getStatus() > Queue::STATUS_RESTORE_WAITING_FOR_RESTORE) {
			$this->getLogController()->logMessage('Resumed Restore');
		}

		try {

			if(!$this->_queue_item_restore->getSnapshotId() && !$this->_queue_item_restore->getSnapshotPath())
				throw new RestoreException("No snapshot id or path provided");
			
			$this->getLogController()->logDebug('Item data: ' . print_r($this->_queue_item_restore, 1));

			$snapshot = $this->getSnapshot();

			if($snapshot->getEngine() == Engine::ENGINE_JB) {
				$this->func([$this, '_addToQueue']);
				$this->func([$this, '_updateQueue']);
			} else {
				$mysql_auth = $this->_fetchMySQLAuth();
				$this->_mysql = new Mysqldump($mysql_auth->db_name, $mysql_auth->db_user, $mysql_auth->db_password, $mysql_auth->db_host);
				$this->_mysql->setLogController($this->getLogController());

				$this->func([$this, '_preFetchAdminUser']);
				$this->func([$this, '_database']);
				$this->func([$this, '_files']);
				$this->func([$this, '_postRestoreDBPrefix']);
				$this->func([$this, '_rewriteViewDefinersAndPrefixes']);
				$this->func([$this, '_validateViewsCompile']);
				$this->func([$this, '_postRestoreDomainMigration']);
				$this->func([$this, '_postInsertAdminUser']);
				$this->func([$this, '_postRestoreHealthCheck']);
				$this->func([$this, '_postRestoreActions']); // must be called after WordPress is loaded
			}

			if($this->getQueueItem()->getStatus() < Queue::STATUS_DONE) $this->getQueueItem()->updateStatus(Queue::STATUS_DONE);
			$this->getLogController()->logMessage('Completed!');
		} catch(ExecutionTimeException $e) {
			throw $e;
		} catch( Exception $e) {
			$this->getQueueItem()->updateStatus(Queue::STATUS_FAILED);
			$this->getLogController()->logError($e->getMessage());
			$this->getLogController()->logMessage('Failed!');
		}

		$this->getQueueItem()->updateProgress($this->getQueueItem()->getStatus() == Queue::STATUS_DONE ? 'Restore Completed' : 'Error during restore', QueueItem::PROGRESS_LAST_STEP);
		$this->getLogController()->logMessage('Total time: ' . $this->getExecutionTimeElapsed());
	}

	public function _validateViewsCompile(): void {
		$views = $this->_mysql->query_exec(
			"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = DATABASE()"
		) ?? [];

		$workspace     = $this->getQueueItem()->getWorkspace();
		$database_path = $workspace . JetBackup::SEP . Snapshot::SKELETON_DATABASE_DIRNAME;
		$visited = [];

		foreach ($views as $v) {
			$name = $v->TABLE_NAME ?? current((array)$v);
			try {
				// Force dependency resolution
				$this->_mysql->query_exec("SELECT * FROM `{$name}` LIMIT 0");
			} catch (\Throwable $e) {
				$msg = $e->getMessage();
				$this->getLogController()->logError("View `{$name}` fails to resolve: " . $msg);

				// 1) Try to (re)import this view from snapshot dumps (pulls its deps too)
				try {
					$dumpPath = self::_dumpPathFor($database_path, $name);
					if ($dumpPath) {
						$this->getLogController()->logMessage("Attempting to (re)import missing/broken view `{$name}` from dump...");
						$this->_importWithDeps($database_path, $name, $visited);

						// Re-test after import
						$this->_mysql->query_exec("SELECT * FROM `{$name}` LIMIT 0");
						$this->getLogController()->logMessage("View `{$name}` now resolves after re-import.");
						continue; // good now
					} else {
						$this->getLogController()->logMessage("No dump found for view `{$name}`.");
					}
				} catch (\Throwable $e2) {
					$this->getLogController()->logError("Re-import attempt for `{$name}` failed: " . $e2->getMessage());
				}

				// 2) Last resort: drop the broken view so it won’t crash the site at runtime
				try {
					$this->getLogController()->logMessage("Dropping unresolved view `{$name}` to avoid runtime failures.");
					$this->_mysql->query_exec("DROP VIEW IF EXISTS `{$name}`");
				} catch (\Throwable $e3) {
					$this->getLogController()->logError("Failed dropping unresolved view `{$name}`: " . $e3->getMessage());
				}
			}
		}
	}


	public function _updateQueue() {
		if(!$this->_queue_item_restore->getQueueGroupId()) throw new RestoreException("Can't find queue group");

		$this->getLogController()->logMessage('Monitoring JetBackup Linux queue item status');

		while(true) {

			try {
				$response = JetBackupLinux::getQueueGroup($this->_queue_item_restore->getQueueGroupId());
			} catch(JetBackupLinuxException $e) {
				throw new RestoreException("Failed fetching JetBackup Linux queue item status. Error: " . $e->getMessage());
			}

			$remote_progress = $response['progress'];
			$progress = $this->getQueueItem()->getProgress();

			$current_status = JetBackupLinux::QUEUE_STATUS_RESTORE_MAPPING[$response['status']] ?? 0;
			$old_status = $this->getQueueItem()->getStatus();

			if($current_status) {
				$name = JetBackupLinux::QUEUE_STATUS_RESTORE_ACCOUNT_NAMES[$response['status']];
				if($current_status != $old_status) {
					$this->getQueueItem()->updateStatus($current_status);
					$this->getQueueItem()->updateProgress($name);
				}

				if($response['status'] == JetBackupLinux::QUEUE_STATUS_RESTORE_ACCOUNT_HOMEDIR) {
					$name .= " ({$remote_progress['completed_files']}/{$remote_progress['total_files']})";
				}

				$this->getLogController()->logMessage($name . '...');
			}

			if($response['status'] == JetBackupLinux::QUEUE_STATUS_RESTORE_ACCOUNT_HOMEDIR && isset($remote_progress['total_files']) && intval($remote_progress['total_files']) > 1) {
				$progress->setTotalSubItems((int) $remote_progress['total_files']);
				$progress->setCurrentSubItem((int) $remote_progress['completed_files']);
			} else {
				if(isset($remote_progress['subtotal'])) $progress->setTotalSubItems($remote_progress['subtotal']);
				if(isset($remote_progress['subcompleted'])) $progress->setCurrentSubItem($remote_progress['subcompleted']);
			}

			if($response['status'] >= JetBackupLinux::QUEUE_STATUS_COMPLETED) {
				$this->getQueueItem()->setStatus(JetBackupLinux::QUEUE_STATUS_MAPPING[$response['status']]);
				$this->getQueueItem()->save();
				return;
			} else {
				$progress->setMessage(JetBackupLinux::QUEUE_STATUS_RESTORE_ACCOUNT_NAMES[$response['status']] ?? 'Processing');
				$this->getQueueItem()->save();
			}

			sleep(5);
		}
	}

	/**
	 * Recreate all views so they:
	 *  - reference the **local** table prefix (not the backup’s)
	 *  - have NO explicit DEFINER
	 *  - use SQL SECURITY INVOKER
	 *  - are created with CREATE OR REPLACE (idempotent)
	 *  - are applied in dependency order (retry passes)
	 */
	public function _rewriteViewDefinersAndPrefixes(): void {
		$this->getLogController()->logMessage("Rewriting view definers and prefixes...");

		$mysql_auth    = $this->_fetchMySQLAuth();
		$local_prefix  = $mysql_auth->table_prefix;
		$backup_prefix = $this->_getDatabasePrefix();

		if (!$backup_prefix || $backup_prefix === $local_prefix) {
			$this->getLogController()->logMessage("No prefix change detected; skipping view rewrite.");
			return;
		}

		// Fetch all view names in current DB
		$rows = $this->_mysql->query_exec(
			"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = DATABASE()"
		) ?? [];

		$views = [];
		foreach ($rows as $r) {
			$views[] = $r->TABLE_NAME ?? current((array)$r);
		}

		// Helper: does an object (table or view) exist now?
		$exists = function(string $name): bool {
			try { return $this->_mysql->tableExists($name, true); } catch (\Exception $e) { return false; }
		};

		/**
		 * Build a queue: for each source view, prepare:
		 *  - the final (normalized, prefix-fixed) DDL
		 *  - the new target name (with local prefix)
		 *  - the conservative list of referenced objects (to order creation)
		 */
		$queue = []; // [oldName => ['new' => newName, 'ddl' => ddl, 'deps' => []]]
		foreach ($views as $oldName) {
			if (!$oldName) continue;

			$row = $this->_mysql->query_exec("SHOW CREATE VIEW `{$oldName}`");
			$ddl = (!empty($row) && isset($row[0])) ? ($row[0]->{'Create View'} ?? null) : null;
			if (!$ddl) {
				$this->getLogController()->logMessage("SHOW CREATE VIEW returned empty for `{$oldName}`, skipping.");
				continue;
			}

			// 1) Normalize header (remove DEFINER/ALGORITHM, force OR REPLACE + INVOKER)
			$ddl = $this->_normalizeCreateViewSQL($ddl);

			// 2) Replace backup prefix -> local prefix in identifiers (header + body)
			$ddl = preg_replace_callback(
				'/`([A-Za-z0-9_]+)`/u',
				function ($m) use ($backup_prefix, $local_prefix) {
					$tok = $m[1];
					return (strpos($tok, $backup_prefix) === 0)
						? '`' . $local_prefix . substr($tok, strlen($backup_prefix)) . '`'
						: $m[0];
				},
				$ddl
			);
			$ddl = preg_replace(
				'/\b' . preg_quote($backup_prefix, '/') . '([A-Za-z0-9_]+)\b/u',
				$local_prefix . '$1',
				$ddl
			);

			// 3) Decide the new view name (swap prefix if the name itself starts with backup_prefix)
			$newName = (strpos($oldName, $backup_prefix) === 0)
				? $local_prefix . substr($oldName, strlen($backup_prefix))
				: $oldName;

			// 4) Ensure the CREATE header uses the new object name
			$ddl = preg_replace(
				'/\bCREATE\s+(?:OR\s+REPLACE\s+)?(?:SQL\s+SECURITY\s+INVOKER\s+)?VIEW\s+`?([A-Za-z0-9_]+)`?/i',
				'CREATE OR REPLACE SQL SECURITY INVOKER VIEW `' . $newName . '`',
				$ddl,
				1
			);

			// 5) Collect referenced objects conservatively from the DDL body
			$deps = [];
			if (preg_match('/\bAS\b(.*)\z/is', $ddl, $mBody)) {
				$body = $mBody[1];
				// FROM/JOIN `db`.`obj` or `obj` or db.obj or obj
				if (preg_match_all('/\b(?:FROM|JOIN)\s+`?([A-Za-z0-9_]+)`?(?:\.`?([A-Za-z0-9_]+)`?)?/i', $body, $mm, PREG_SET_ORDER)) {
					foreach ($mm as $g) {
						$obj = isset($g[2]) && $g[2] ? $g[2] : $g[1];
						if (strpos($obj, $backup_prefix) === 0) {
							$obj = $local_prefix . substr($obj, strlen($backup_prefix));
						}
						$deps[] = $obj;
					}
				}
			}
			$deps = array_values(array_unique($deps));

			$queue[$oldName] = ['new' => $newName, 'ddl' => $ddl, 'deps' => $deps];
		}

		// Retry passes: create views whose deps already exist; loop until no progress
		$maxPasses = 5;
		for ($pass = 1; $pass <= $maxPasses && !empty($queue); $pass++) {
			$progress = 0;

			foreach (array_keys($queue) as $oldName) {
				$newName = $queue[$oldName]['new'];
				$ddl     = $queue[$oldName]['ddl'];
				$deps    = $queue[$oldName]['deps'];

				// if any dependency doesn't exist yet, skip for this pass
				$blocked = false;
				foreach ($deps as $d) {
					if (!$exists($d)) { $blocked = true; break; }
				}

				if ($blocked) {
					$this->getLogController()->logMessage("Waiting to recreate `{$oldName}`; missing deps: " . implode(', ', array_filter($deps, fn($d) => !$exists($d))));
					continue;
				}


				try {
					// be idempotent on target name
					$this->_mysql->query_exec("DROP VIEW IF EXISTS `{$newName}`");
					$this->_mysql->query_exec($ddl);

					// if we changed the name, drop the old name to prevent stale alias
					if ($newName !== $oldName) {
						$this->_mysql->query_exec("DROP VIEW IF EXISTS `{$oldName}`");
					}

					$this->getLogController()->logMessage("Recreated view `{$newName}` (from `{$oldName}`) with updated prefix and INVOKER.");
					unset($queue[$oldName]);
					$progress++;
				} catch (\Throwable $e) {
					$this->getLogController()->logError("Failed to recreate view `{$oldName}` → `{$newName}`: " . $e->getMessage());
					// leave it for next pass
				}
			}

			if ($progress === 0) break; // nothing more we can resolve by ordering
		}

		// Any unresolved views will be surfaced by _validateViewsCompile()
	}


	/**
	 * Header-level normalization for CREATE VIEW:
	 *  - remove DEFINER and ALGORITHM
	 *  - force CREATE OR REPLACE
	 *  - force SQL SECURITY INVOKER
	 */
	public function _normalizeCreateViewSQL(string $sql): string {
		if (!preg_match('/\bCREATE\b.*\bVIEW\b/is', $sql)) return $sql;

		// Split once at first AS (header vs body)
		$parts  = preg_split('/\bAS\b/i', $sql, 2);
		$header = $parts[0] ?? $sql;
		$body   = $parts[1] ?? '';

		// Remove versioned DEFINER blocks like /*!50013 DEFINER=`user`@`host` SQL SECURITY DEFINER */
		$header = preg_replace(
			'/\/\*!\d+\s+DEFINER\s*=\s*[^*]+SQL\s+SECURITY\s+(?:DEFINER|INVOKER)\s*\*\//i',
			' ',
			$header
		);
		// Remove plain DEFINER=...
		$header = preg_replace(
			'/\bDEFINER\s*=\s*(?:`[^`]+`@`[^`]+`|\'[^\']+\'@\'[^\']+\'|[^ \t\n\r\f\)]+)\s*/i',
			' ',
			$header
		);
		// Remove ALGORITHM=...
		$header = preg_replace('/\bALGORITHM\s*=\s*\w+\s*/i', ' ', $header);

		// Force CREATE OR REPLACE
		$header = preg_replace('/\bCREATE\s+(?!OR\s+REPLACE\b)/i', 'CREATE OR REPLACE ', $header, 1);

		// Ensure SQL SECURITY INVOKER
		if (preg_match('/\bSQL\s+SECURITY\s+(?:DEFINER|INVOKER)\b/i', $header)) {
			$header = preg_replace('/\bSQL\s+SECURITY\s+(?:DEFINER|INVOKER)\b/i', 'SQL SECURITY INVOKER', $header, 1);
		} else {
			$header = preg_replace(
				'/\b(CREATE\s+(?:OR\s+REPLACE\s+)?)(VIEW\b)/i',
				'$1SQL SECURITY INVOKER $2',
				$header,
				1
			);
		}

		// Tidy whitespace
		$header = preg_replace('/[ \t]+/', ' ', trim($header));

		if ($body === '') return $header;
		return $header . ' AS' . (preg_match('/^\s/', $body) ? '' : ' ') . $body;
	}


	/**
	 * @throws DBException
	 * @throws RestoreException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function _addToQueue() {
		if($this->_queue_item_restore->getQueueGroupId()) return;

		$this->getLogController()->logMessage('Adding restore to JetBackup Linux queue');

		$snapshot = $this->getSnapshot();

		$files = $items = [];
		$homedir_item_id = $database_item_id = null;

		foreach ($snapshot->getItems() as $item) {
			switch($item->getBackupContains()) {
				case BackupJob::BACKUP_ACCOUNT_CONTAINS_HOMEDIR:
					$homedir_item_id = $item->getUniqueId();
					break;
				case BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE:
					$database_item_id = $item->getUniqueId();
					break;
			}
		}

		$options = $this->_queue_item_restore->getOptions();

		if ($homedir_item_id && ($options & QueueItemRestore::OPTION_RESTORE_FILES_ENTIRE)) {
			$files[$homedir_item_id][Factory::getWPHelper()->getWordPressRelativePublicDir()] = 'Directory';
			$items[] = $homedir_item_id;
		}

		if ($homedir_item_id && ($options & QueueItemRestore::OPTION_RESTORE_FILES_INCLUDE)) {

			$files = [];

			$public_dir = Factory::getWPHelper()->getWordPressRelativePublicDir();

			foreach ($this->_queue_item_restore->getFileManager() as $file) {
				$path = $public_dir . JetBackup::SEP . trim($file['path'], JetBackup::SEP);
				$this->getLogController()->logMessage("Path: $path");
				$files[$homedir_item_id][$path] = $file['type'];
			}

			$items[] = $homedir_item_id;
		}


		if ($database_item_id && ($options & QueueItemRestore::OPTION_RESTORE_DATABASE_ENTIRE)) {
			$items[] = $database_item_id;
		}

		try {
			$response = JetBackupLinux::addQueueItems($items, $files, ['merge' => 1]);
		} catch (Exception $e) {
			throw new RestoreException("Failed adding to JetBackup Linux queue. Error: " . $e->getMessage());
		}

		$this->_queue_item_restore->setQueueGroupId($response['_id']);
		$this->getQueueItem()->save();
	}

	/**
	 * @return Snapshot
	 */
	private function getSnapshot():Snapshot {

		return $this->func(function() {

			if($this->_queue_item_restore->getSnapshotId()) {
				$snapshot = new Snapshot($this->_queue_item_restore->getSnapshotId());
				if($snapshot->getId() && $snapshot->getEngine() == Engine::ENGINE_JB) return $snapshot;
			}

			$workspace = $this->getQueueItem()->getWorkspace();
			$meta_file = sprintf(Snapshot::META_FILEPATH, $workspace);

			if(!file_exists($meta_file)) throw new RestoreException("Backup meta file not exists ($meta_file)");

			$snapshot = new Snapshot();
			try {
				$snapshot->importMeta($meta_file, true);
			} catch(SnapshotMetaException $e) {
				throw new RestoreException("Failed read snapshot meta file. Error: " . $e->getMessage());
			}

			return $snapshot;

		}, [], '_loadSnapshot');
	}

	/**
	 * @return object|null
	 */
	private function _fetchMySQLAuth():object {

		return $this->func(function() {

			try {
				return Factory::getWPHelper()::parseWpConfig();
			} catch (Exception $e) {
				throw new RestoreException($e->getMessage());
			}

		}, [], '_MySQLAuth');
	}

	/**
	 * @param string $dumpPath
	 *
	 * @return bool
	 */
	private static function _isViewDump(string $dumpPath): bool {
		$fh = @fopen($dumpPath, 'rb');
		if (!$fh) return false;
		$chunk = fread($fh, 262144); // read a small chunk
		fclose($fh);
		if ($chunk === false || $chunk === '') return false;

		// normalize & look for CREATE VIEW
		$sql = preg_replace('/\/\*![0-9]+\s*/', ' ', $chunk);
		$sql = str_replace('*/', ' ', $sql);
		$sql = preg_replace('/\s+/', ' ', $sql);

		return (bool) preg_match(
			'/\bCREATE\s+(?:OR\s+REPLACE\s+)?(?:ALGORITHM=\w+\s+)?(?:DEFINER=\S+\s+)?(?:SQL\s+SECURITY\s+(?:DEFINER|INVOKER)\s+)?VIEW\b/i',
			$sql
		);
	}

	/**
	 * @return void
	 * @throws Exception
	 */
	public function _database(): void {
		if (!$this->_queue_item_restore->isRestoreDatabase()) return;

		$this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_DATABASE);
		$this->getQueueItem()->updateProgress('Restoring database');
		$this->getLogController()->logMessage('Importing all database tables');

		$workspace     = $this->getQueueItem()->getWorkspace();
		$database_path = $workspace . JetBackup::SEP . Snapshot::SKELETON_DATABASE_DIRNAME;

		$visited = []; // per-run dedupe of imported dumps

		// Relax constraints for faster/safer bulk import
		$this->_mysql->query_exec('SET FOREIGN_KEY_CHECKS=0');
		$this->_mysql->query_exec('SET UNIQUE_CHECKS=0');
		$this->_mysql->query_exec('SET SQL_NOTES=0');

		try {
			// Phase 1: Import all non-view dumps
			$this->foreachCallable([$this, '_listDatabaseTables'], [], function ($i, $table_dump) use ($database_path, &$visited) {
				// Normalize dump base name -> table
				$base = $table_dump;
				if (str_ends_with($base, '.gz'))  $base = substr($base, 0, -3);
				if (str_ends_with($base, '.sql')) $base = substr($base, 0, -4);
				$table = $base;

				$dumpPath = self::_dumpPathFor($database_path, $table);
				if (!$dumpPath) return;

				// Skip views in phase 1
				if (self::_isViewDump($dumpPath)) return;

				$this->_importWithDeps($database_path, $table, $visited);

				// Progress: count only real imports
				$p = $this->getQueueItem()->getProgress();
				$p->increaseCurrentSubItem();
				$this->getQueueItem()->save();
			}, 'database_tables_phase1');

			// Phase 2: Import views
			$this->foreachCallable([$this, '_listDatabaseTables'], [], function ($i, $table_dump) use ($database_path, &$visited) {
				// Normalize dump base name -> table
				$base = $table_dump;
				if (str_ends_with($base, '.gz'))  $base = substr($base, 0, -3);
				if (str_ends_with($base, '.sql')) $base = substr($base, 0, -4);
				$table = $base;

				$dumpPath = self::_dumpPathFor($database_path, $table);
				if (!$dumpPath) return;

				// Only views in phase 2
				if (!self::_isViewDump($dumpPath)) return;

				$this->_importWithDeps($database_path, $table, $visited);

				// Progress: count only real imports
				$p = $this->getQueueItem()->getProgress();
				$p->increaseCurrentSubItem();
				$this->getQueueItem()->save();
			}, 'database_tables_phase2');

		} finally {
			// Always restore original settings
			$this->_mysql->query_exec('SET SQL_NOTES=1');
			$this->_mysql->query_exec('SET UNIQUE_CHECKS=1');
			$this->_mysql->query_exec('SET FOREIGN_KEY_CHECKS=1');
		}
	}


	/** Return ALL referenced objects for a view dump; empty for base-table dumps */
	private static function _viewReferencedObjectsFromDump(string $path): array {
		$fh = @fopen($path, 'rb');
		if (!$fh) return [];
		$chunk = fread($fh, 262144);
		fclose($fh);
		if ($chunk === false || $chunk === '') return [];

		// Strip versioned comments and normalize spaces
		$sql = preg_replace('/\/\*![0-9]+\s*/', ' ', $chunk);
		$sql = str_replace('*/', ' ', $sql);
		$sql = preg_replace('/\s+/', ' ', $sql);

		// Only for CREATE VIEW...
		if (!preg_match('/\bCREATE\s+(?:OR\s+REPLACE\s+)?(?:ALGORITHM=\w+\s+)?(?:DEFINER=\S+\s+)?(?:SQL\s+SECURITY\s+(?:DEFINER|INVOKER)\s+)?VIEW\b/i', $sql)) {
			return [];
		}

		$bases = [];

		// Handle: FROM `db`.`table`  or JOIN  `db`.`table`
		if (preg_match_all('/\b(?:FROM|JOIN)\s+`([^`]+)`\.`([^`]+)`/i', $sql, $m)) {
			foreach ($m[2] as $t) $bases[] = $t;
		}
		// Handle: FROM `table`  or JOIN `table`
		if (preg_match_all('/\b(?:FROM|JOIN)\s+`([^`]+)`(?!\.)/i', $sql, $m)) {
			foreach ($m[1] as $t) $bases[] = $t;
		}
		// Handle: FROM db.table  or JOIN db.table  (unquoted)
		if (preg_match_all('/\b(?:FROM|JOIN)\s+([A-Za-z0-9_]+)\.([A-Za-z0-9_]+)/i', $sql, $m)) {
			foreach ($m[2] as $t) $bases[] = $t;
		}
		// Handle: FROM table  or JOIN table  (unquoted)
		if (preg_match_all('/\b(?:FROM|JOIN)\s+([A-Za-z0-9_]+)\b(?!\.)/i', $sql, $m)) {
			foreach ($m[1] as $t) $bases[] = $t;
		}

		// De-dupe
		return array_values(array_unique($bases));
	}


	private static function _dumpPathFor(string $database_path, string $table): ?string {
		$p = $database_path . JetBackup::SEP . $table . '.sql';
		return is_file($p) ? $p : (is_file($p . '.gz') ? $p . '.gz' : null);
	}

	/** Normalize id for visited map: foo.sql or foo.sql.gz → foo.sql */
	private static function _dumpId(string $dumpPath): string {
		return basename(preg_replace('/\.gz$/', '', $dumpPath));
	}

	/** Recursively import a dump and all its referenced deps (from snapshot), once */
	private function _importWithDeps(string $database_path, string $table, array &$visited): void {
		$dump = self::_dumpPathFor($database_path, $table);

		// If there is no dump for this dep but it already exists in DB, proceed.
		if (!$dump) {
			if ($this->_mysql->tableExists($table, true)) return;
			throw new RestoreException("Missing dump for dependency `{$table}` required by another view.");
		}

		$id = self::_dumpId($dump);
		if (!empty($visited[$id])) return;

		// Recurse for nested deps first (only for view dumps)
		$deps = self::_viewReferencedObjectsFromDump($dump);
		foreach ($deps as $dep) {
			$this->_importWithDeps($database_path, $dep, $visited);
		}

		$this->getLogController()->logMessage(" - Importing: {$table} (" . basename($dump) . ")");
		$this->_mysql->import($dump);

		$visited[$id] = true;
	}


	/**
	 * @throws Exception
	 */
	public function _preFetchAdminUser():void {
		$tables_to_restore = $this->_listDatabaseTables();
		$matchingUser = false;

		foreach ($tables_to_restore as $table) {
			if (str_starts_with($table, $this->_getDatabasePrefix() . "users") ||
			    str_starts_with($table, $this->_getDatabasePrefix() . "usermeta")) {
				$matchingUser = true;
				break;
			}
		}

		if(!$matchingUser) return;
		$this->getLogController()->logMessage(' Fetching admin users');
		$admin_user = $this->_getAdminUser();
		$admin_user = $admin_user ? serialize($admin_user) : null;
		if(!$admin_user) return;

		$mysql_auth = $this->_fetchMySQLAuth();
		$this->_queue_item_restore->setAdminUser(Crypt::encrypt($admin_user, $mysql_auth->db_password));
		$this->getQueueItem()->save();

	}

	/**
	 * @throws RestoreException
	 */
	public function _postInsertAdminUser():void {
		if(!($admin_user = $this->_queue_item_restore->getAdminUser())) return;
		$this->getLogController()->logMessage(' Inserting admin user');
		$mysql_auth = $this->_fetchMySQLAuth();
		$admin_user = Crypt::decrypt($admin_user, $mysql_auth->db_password);
		$this->_insertAdminUser( (array) unserialize( $admin_user ));
	}


	/**
	 * @param array $user_data
	 *
	 * @return void
	 * @throws RestoreException
	 */
	private function _insertAdminUser(array $user_data): void {
		$mysql_auth = $this->_fetchMySQLAuth();
		$user_table = $mysql_auth->table_prefix . 'users';

		$query = "SHOW COLUMNS FROM `$user_table`";
		$columns = $this->_mysql->query_exec($query);

		if (!$columns || count($columns) === 0) throw new RestoreException("The table $user_table does not exist or has no columns.");
		$existing_columns = array_map(fn($column) => $column->Field, $columns);
		$filtered_user_data = array_intersect_key($user_data, array_flip($existing_columns));
		if (empty($filtered_user_data)) throw new RestoreException("No valid user data found to insert.");

		// Prepare SQL query parts
		$columns_str = implode(", ", array_keys($filtered_user_data));
		$placeholders = implode(", ", array_map(fn($key) => ":$key", array_keys($filtered_user_data)));
		$update_str = implode(", ", array_map(fn($key) => "`$key` = VALUES(`$key`)", array_keys($filtered_user_data)));

		$query = "INSERT INTO `$user_table` ($columns_str) VALUES ($placeholders) 
              ON DUPLICATE KEY UPDATE $update_str";

		$params = array_combine(array_map(fn($key) => ":$key", array_keys($filtered_user_data)), array_values($filtered_user_data));

		try {
			$this->_mysql->query_exec($query, $params);
			$this->getLogController()->logMessage("Admin user successfully inserted or updated in $user_table");

			// Set admin capabilities
			if (isset($user_data['ID'])) {
				$user_id = $user_data['ID'];
				$usermeta_table = $mysql_auth->table_prefix . 'usermeta';

				// Insert or update wp_capabilities
				$capabilities_query = "INSERT INTO `$usermeta_table` (`user_id`, `meta_key`, `meta_value`) 
                                   VALUES (:user_id, :meta_key_cap, :meta_value_cap) 
                                   ON DUPLICATE KEY UPDATE `meta_value` = :meta_value_cap";

				$params_capabilities = [
					':user_id' => $user_id,
					':meta_key_cap' => $mysql_auth->table_prefix . 'capabilities',
					':meta_value_cap' => serialize(['administrator' => true]),
				];

				$this->_mysql->query_exec($capabilities_query, $params_capabilities);
				$this->getLogController()->logMessage("Admin capabilities set for user ID $user_id");

				// Insert or update wp_user_level
				$user_level_query = "INSERT INTO `$usermeta_table` (`user_id`, `meta_key`, `meta_value`) 
                                 VALUES (:user_id, :meta_key_lvl, :meta_value_lvl) 
                                 ON DUPLICATE KEY UPDATE `meta_value` = :meta_value_lvl";

				$params_user_level = [
					':user_id' => $user_id,
					':meta_key_lvl' => $mysql_auth->table_prefix . 'user_level',
					':meta_value_lvl' => '10', // Administrator level
				];

				$this->_mysql->query_exec($user_level_query, $params_user_level);
				$this->getLogController()->logMessage("Admin user level set for user ID $user_id");
			}
		} catch (Exception $e) {
			throw new RestoreException("Failed to insert or update admin user: " . $e->getMessage());
		}
	}


	/**
	 * @return array|null
	 * @throws Exception
	 */
	private function _getAdminUser(): ?array {

		return $this->func(function(){
			$this->getLogController()->logMessage(' - Handling admin user');

			$mysql_auth = $this->_fetchMySQLAuth();
			$table_prefix = $mysql_auth->table_prefix ?? 'wp_';

			// Fetch all users with session tokens
			$query = "SELECT u.ID, u.user_login, um.meta_value AS session_tokens
              FROM `{$table_prefix}usermeta` um
              JOIN `{$table_prefix}users` u ON um.user_id = u.ID
              WHERE um.meta_key = 'session_tokens'";

			$users = $this->_mysql->query_exec($query);

			$latest_user = null;
			$latest_expiration = 0;

			// Loop through each user and find the latest session
			foreach ($users as $user) {
				$session_tokens = unserialize($user->session_tokens);

				if (!is_array($session_tokens)) continue;

				foreach ($session_tokens as $session) {
					if (isset($session['expiration']) && $session['expiration'] > $latest_expiration) {
						$latest_expiration = $session['expiration'];
						$latest_user = $user->user_login;
					}
				}
			}

			if (!$latest_user) return null;

			// Fetch full user details (Same as the original function)
			$query = "SELECT * FROM `{$table_prefix}users` WHERE `user_login` = :username";
			$params = [':username' => $latest_user];
			$user = $this->_mysql->query_exec($query, $params);

			if (!isset($user[0])) return null;
			return (array) $user[0]; // Return the full user details
		}, [], 'getAdminUser');
	}


	/**
	 * @return array
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function _listDatabaseTables():array {

		$output = [];

		$items = $this->getSnapshot()->getItems();

		foreach ($items as $item) {
			// Skip if the item is not a database backup
			if ($item->getBackupContains() !== BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE) continue;

			$dbName = $item->getName();
			$includedDatabases = $this->_queue_item_restore->getIncludedDatabases();
			$excludedDatabases = $this->_queue_item_restore->getExcludedDatabases();

			// Restore only included tables
			if ($includedDatabases && !in_array($dbName, $includedDatabases)) continue;

			// Exclude specific tables
			if ($excludedDatabases && in_array($dbName, $excludedDatabases)) continue;

			// Add the database backup to the output
			$output[] = basename($item->getPath());
		}


		$progress = $this->getQueueItem()->getProgress();
		$progress->setTotalSubItems(sizeof($output));
		$progress->setCurrentSubItem(0);
		$this->getQueueItem()->save();


		$this->getLogController()->logMessage('Total of ' . sizeof($output) . ' database tables found for restore');

		return $output;
	}

	/**
	 * @return void
	 * @throws RestoreException
	 */
	public function _files():void {

		if(!$this->_queue_item_restore->isRestoreHomedir()) return;

		$this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_FILES);
		$this->getQueueItem()->updateProgress('Restoring homedir files');
		$this->getLogController()->logMessage("Restoring homedir files");

		$workspace = $this->getQueueItem()->getWorkspace();

		$source = new File($workspace . JetBackup::SEP . Snapshot::SKELETON_FILES_DIRNAME);
		if(!$source->exists() || !$source->isDir()) throw new RestoreException("Can't find source directory ({$source->path()})");

		$target = new File(Factory::getWPHelper()->getWordPressHomedir());
		if(!$target->exists() || !$target->isDir()) throw new RestoreException("Can't find target directory ({$target->path()})");


		$queue = [$source->dir()];

		while($queue) {
			$dir = array_shift($queue);

			$dir_path = $dir->path;
			//$contains = 0;

			while(($entry = $dir->read()) !== false) {
				if($entry == '.' || $entry == '..') continue;

				$source_file = new File($dir_path . JetBackup::SEP . $entry);
				$target_file = new File($target->path() . JetBackup::SEP . trim(substr($source_file->path(), strlen($source->path())), JetBackup::SEP));

				$this->getLogController()->logDebug("Restoring file {$source_file->path()} to {$target_file->path()}");

				if ($source_file->isDir()) {

					switch (true) {
						case (!$target_file->exists()):
							// Case 1: Target does not exist → Create the directory
							$this->getLogController()->logDebug("[Directory Mode] Target {$target_file->path()} does not exist → Create the directory");
							mkdir($target_file->path(), $source_file->mode());
							break;

						case ($target_file->isLink() && $target_file->isDir($target_file->path())):
							// Case 2: Target is a symlink to a directory → Keep it, do nothing
							$this->getLogController()->logDebug("[Directory Mode] Target {$target_file->path()} is a symlink to a directory → Keep it, do nothing");
							break;

						case (!$target_file->isDir()):
							// Case 3: Target exists but is NOT a directory → Remove it
							$this->getLogController()->logDebug("[Directory Mode] Target {$target_file->path()} exists but is NOT a directory → Remove it");
							unlink($target_file->path());
							mkdir($target_file->path(), $source_file->mode());
							break;

						default:
							// Case 4: Target is an existing directory → Just update permissions
							$this->getLogController()->logDebug("[Directory Mode] Target {$target_file->path()} is an existing directory → Just update permissions");
							chmod($target_file->path(), $source_file->mode());
							break;
					}

					// Add directory to queue for further processing
					$queue[] = $source_file->dir();

				} else {

					switch (true) {
						case (!$target_file->exists()):
							// Case 1: Target does not exist → Move file
							$this->getLogController()->logDebug("[File Mode] Target {$target_file->path()} does not exist → Move file");
							rename($source_file->path(), $target_file->path());
							break;

						case (
							$target_file->mtime() != $source_file->mtime() ||
							$target_file->size() != $source_file->size()
						):
							// Case 2: Target exists but is outdated → Remove and replace
							$this->getLogController()->logDebug("[File Mode] Target {$target_file->path()} exists but is outdated → Remove and replace");

							unlink($target_file->path());
							rename($source_file->path(), $target_file->path());
							break;

						default:
							// Case 3: Target is identical → No need to replace, just remove source
							$this->getLogController()->logDebug("[File Mode] Target {$target_file->path()} is identical → No need to replace, just remove source");

							unlink($source_file->path());
							break;
					}
				}


			}

			$dir->close();

			// TODO we need to find better way to delete empty folders
			// delete the folder if it's empty 
			//if(!$contains) rmdir($dir_path);
		}
	}

	/**
	 * @return null
	 */
	private function _getSiteURL() {

		return $this->func(function() {

			$snapshot = $this->getSnapshot();
			foreach($snapshot->getItems() as $item) {
				if ($item->getBackupContains() != BackupJob::BACKUP_ACCOUNT_CONTAINS_FULL) continue;
				$prefix = $item->getParams()['site_url'] ?? '';
				if($prefix) return $prefix;
			}

			return '';

		}, [], '_fetchSiteURL');

	}

	/**
	 * @return string
	 */
	private function _getDatabasePrefix():string {

		return $this->func(function() {

			$snapshot = $this->getSnapshot();
			foreach($snapshot->getItems() as $item) {
				if ($item->getBackupContains() != BackupJob::BACKUP_ACCOUNT_CONTAINS_DATABASE) continue;
				$prefix = $item->getParams()['db_prefix'] ?? '';
				if($prefix) return $prefix;
			}

			return '';

		}, [], '_fetchDatabasePrefix');
	}

	/**
	 * @return void
	 * @throws RestoreException
	 * @throws Exception
	 */
	public function _postRestoreDBPrefix():void {

		if(!$this->_queue_item_restore->isRestoreDatabase()) return;

		$this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_POST_RESTORE_DB_PREFIX);
		$this->getQueueItem()->updateProgress('Post restore - DB Prefix');

		if(!($backup_prefix = $this->_getDatabasePrefix())) throw new RestoreException("Can't find backup database prefix");

		$mysql_auth = $this->_fetchMySQLAuth();
		$local_prefix = $mysql_auth->table_prefix;

		if($local_prefix == $backup_prefix) return;

		$this->getLogController()->logMessage("Local DB Prefix ($local_prefix) is different from ($backup_prefix)");

		$backup_tables = $this->_fetchBackupTables();

		if(!sizeof($backup_tables)) return;

		$this->getLogController()->logMessage("Searching for orphaned tables");
		$this->foreach($backup_tables, function($i, $table) use ($local_prefix, $backup_prefix) {
			$table_name = $local_prefix . $table;
			$table_name_old = $backup_prefix . $table;

			$this->getLogController()->logMessage("- Dropping database table `$table_name` foreign keys");
			$this->_dropForeignKeys($table_name);

			$this->getLogController()->logMessage("- Dropping database table `$table_name` if exists");
			$this->_mysql->query_exec("DROP TABLE IF EXISTS `$table_name`");

			$this->getLogController()->logMessage("- Renaming database table `$table_name_old` to `$table_name`");
			$this->_mysql->query_exec("RENAME TABLE `$table_name_old` TO `$table_name`");
		}, '_dropTables');


		$usermeta_table = $local_prefix . 'usermeta';
		$options_table = $local_prefix . 'options';

		// Search and replace prefix in the dynamically generated usermeta table
		$this->_replacePrefixInTable($usermeta_table, 'meta_key', 'umeta_id');
		$this->_replacePrefixInTable($usermeta_table, 'meta_value', 'umeta_id');

		// Search and replace prefix in the dynamically generated options table
		$this->_replacePrefixInTable($options_table, 'option_name', 'option_id');
		$this->_replacePrefixInTable($options_table, 'option_value', 'option_id');
	}

	private static function _getParseDomain($url): string {
		$compa = parse_url($url);
		$domain = $compa['host'] ?? '';
		$port = $compa['port'] ?? '';
		$path = $compa['path'] ?? '';

		$domain = preg_replace("/(www|\dww|w\dw|ww\d)\./", "", $domain);

		return $domain . ($port ? ":$port" : '') . $path;
	}

	private static function _buildSiteURL():string {
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
		$host = $_SERVER['HTTP_HOST'] ?? 'localhost'; // Default to localhost if missing
		$script_path = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? $_SERVER['REQUEST_URI'];
		$script_dir = dirname($script_path);

		return rtrim($protocol . "://" . $host . $script_dir, '/');
	}


	public function _postRestoreDomainMigration(): void {
		$this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_POST_RESTORE_DOMAIN_MIGRATION);
		$this->getQueueItem()->updateProgress('Post restore - Domain Migration');

		$backup_domain = self::_getParseDomain($this->_getSiteURL());
		$local_domain = self::_getParseDomain(self::_buildSiteURL());

		$alternate_path = strlen(Factory::getSettingsRestore()->isRestoreAlternatePathEnabled() ? JetBackup::CRON_PUBLIC_URL : '');
		if($alternate_path) $local_domain = substr($local_domain, 0, -$alternate_path);

		if(!$local_domain) {
			$this->getLogController()->logMessage("[Error] Couldn't retrieve local domain, exiting");
			return;
		}

		if ($backup_domain === $local_domain) {
			$this->getLogController()->logMessage("Domain from backup ($backup_domain) matches local domain ($local_domain), no migration needed");
			return;
		}

		$this->foreachCallable([$this, '_fetchTables'], [], function($i, $table_details) use ($backup_domain, $local_domain) {
			$table_name = current(get_object_vars($table_details));

			$this->getLogController()->logMessage("Processing table $table_name");

			$cols = $this->_fetchTableColumns($table_name);
			$primary_key = $this->_getTablePrimaryKey($table_name);

			if (!$primary_key) {
				$this->getLogController()->logMessage("Skipping table $table_name - No primary key found.");
				return;
			}

			$this->foreach($cols, function($i, $column) use ($table_name, $backup_domain, $local_domain, $primary_key) {
				$this->getLogController()->logMessage("Checking column for table $table_name: $column");

				// Select primary key and target column
				$query = "SELECT `$primary_key`, `$column` FROM `$table_name` WHERE `$column` LIKE :old_domain";
				$rows = $this->_mysql->query_exec($query, [':old_domain' => "%$backup_domain%"]);

				if (empty($rows)) return;

				foreach ($rows as $row) {
					$original_data = $row->{$column};
					$replaced_data = $this->_changeColumnsValue($original_data, $backup_domain, $local_domain);

					if ($replaced_data !== $original_data) {
						$update_query = "UPDATE `$table_name` SET `$column` = :new_value WHERE `$primary_key` = :primary_key";
						$result = $this->_mysql->query_exec($update_query, [
							':new_value' => $replaced_data,
							':primary_key' => $row->{$primary_key}
						]);
						if ($result === false) {
							$this->getLogController()->logMessage("SQL GENERAL ERROR");
						}
						$this->getLogController()->logMessage("Updated column: $column in table: $table_name (Row ID: {$row->{$primary_key}})");

					}
				}
			}, '_fixTableColumns_' . $table_name);
		});
	}



	/**
	 * Get primary key of a table
	 */
	private function _getTablePrimaryKey(string $table_name): ?string {

		return $this->func(function($table_name){
			$query = "SHOW KEYS FROM `$table_name` WHERE Key_name = 'PRIMARY'";
			$result = $this->_mysql->query_exec($query);

			return !empty($result) ? $result[0]->Column_name : null;
		}, [$table_name], '_getTablePrimaryKey_' . $table_name);

	}


	/**
	 * @param $data
	 * @param $old_value
	 * @param $new_value
	 *
	 * @return __PHP_Incomplete_Class|array|bool|float|int|mixed|string|string[]|null
	 */
	private function _changeColumnsValue($data, $old_value, $new_value) {

		$this->getLogController()->logDebug("Processing data of type: " . gettype($data));

		switch(true) {
			case is_numeric($data) || is_bool($data) || is_null($data) || $data instanceof \__PHP_Incomplete_Class:
				return $data;

			case is_array($data):
				$this->getLogController()->logDebug("Data is array");
				foreach ($data as $key => $value) $data[$key] = $this->_changeColumnsValue($value, $old_value, $new_value);
				return $data;

			case is_object($data):
				$this->getLogController()->logDebug("Data is object");
				foreach ($data as $key => $value) $data->{$key} = $this->_changeColumnsValue($value, $old_value, $new_value);
				return $data;

			case (($unserialized = @unserialize($data, ['allowed_classes' => false])) !== false || $data == 'b:0;'):
				$this->getLogController()->logDebug("Data is serialized");
				return serialize($this->_changeColumnsValue($unserialized, $old_value, $new_value));

			case is_string($data):
				$this->getLogController()->logDebug("Performing simple replacement from $old_value to $new_value");
				$new_data = str_replace($old_value, $new_value, $data);
				if ($new_data !== $data) $this->getLogController()->logDebug("Replacement successful. New data: " . substr($new_data, 0, 50) . "...");
				else $this->getLogController()->logDebug("No replacement made in this string.");
				return $new_data;
		}

		return $data;
	}


	/**
	 * @return void
	 * Specific plugin actions needed after restore
	 * At this point we assume WordPress is loaded and healthy
	 *
	 */
	public function _postRestoreActions(): void {

		$this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_POST_RESTORE_PLUGIN_ACTIONS);
		$this->getQueueItem()->updateProgress('Post restore - Actions');
		$this->getLogController()->logMessage('Post restore - Actions');
		$actions = Factory::getSettingsIntegrations()->getInegrations();
		if(!$actions) return;

		$this->foreach($actions, function ($key, $action) {
			$method = "JetBackup\\Integrations\\Vendors\\$action";
			if(!class_exists($method)) return;
			$this->getLogController()->logMessage("Doing $action post restore actions");
			$instance = new $method();
			$instance->execute();
		}, '_postRestorePluginActions');

	}

	/**
	 * @return array
	 */
	public function _fetchBackupTables(): array {
		return $this->func(function() {
			$backup_prefix = $this->_getDatabasePrefix();

			// Only return BASE TABLEs that start with the backup prefix.
			$rows = $this->_mysql->query_exec(
				"SELECT TABLE_NAME
         FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_TYPE   = 'BASE TABLE'
          AND TABLE_NAME   LIKE :p",
				[':p' => $backup_prefix . '%']
			) ?? [];

			// Return suffix (strip the prefix) to match your existing caller logic.
			return array_map(function($r) use ($backup_prefix){
				$name = is_object($r) ? $r->TABLE_NAME : (is_array($r) ? $r['TABLE_NAME'] : (string)$r);
				return substr($name, strlen($backup_prefix));
			}, $rows);
		}, [], '_fetchBackupTables');
	}

	/**
	 * @return array
	 */
	public function _fetchTables(): array {
		return $this->func(function() {
			return $this->_mysql->query_exec("SHOW TABLES");
		}, [], '_fetchTables');
	}

	/**
	 * @param $table_name
	 *
	 * @return array
	 */
	public function _fetchTableColumns($table_name): array {
		return $this->func(function() use ($table_name) {
			$output = [];
			$results = $this->_mysql->query_exec("SHOW COLUMNS FROM `$table_name`");
			foreach ($results as $row) $output[] = $row->Field;
			return $output;
		}, [], '_fetchTableColumns_' . $table_name);
	}

	/**
	 * @param $table
	 *
	 * @return void
	 * @throws RestoreException
	 */
	private function _dropForeignKeys($table) {
		try {
			// Drop foreign keys within the table
			$sql = "SELECT CONSTRAINT_NAME 
					FROM information_schema.TABLE_CONSTRAINTS 
					WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
					AND TABLE_SCHEMA = DATABASE() 
					AND TABLE_NAME = :table_name";
			$constraints = $this->_mysql->query_exec($sql, [':table_name' => $table]);

			foreach ($constraints as $constraint) {
				$constraint_name = $constraint->CONSTRAINT_NAME;
				$alter_query = "ALTER TABLE `$table` DROP FOREIGN KEY `$constraint_name`";
				$this->getLogController()->logMessage("Dropping foreign key: $constraint_name from table: $table");
				$this->_mysql->query_exec($alter_query);
			}

			// Drop foreign keys that reference the table from other tables
			$sql = "SELECT TABLE_NAME, CONSTRAINT_NAME 
					FROM information_schema.KEY_COLUMN_USAGE 
					WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
					AND REFERENCED_TABLE_NAME = :table_name";
			$constraints = $this->_mysql->query_exec($sql, [':table_name' => $table]);

			foreach ($constraints as $constraint) {
				$referencing_table = $constraint->TABLE_NAME;
				$constraint_name = $constraint->CONSTRAINT_NAME;
				$alter_query = "ALTER TABLE `$referencing_table` DROP FOREIGN KEY `$constraint_name`";
				$this->getLogController()->logMessage("Dropping foreign key: $constraint_name from table: `$referencing_table` that references `$table`");
				$this->_mysql->query_exec($alter_query);
			}
		} catch ( Exception $e) {
			$this->getLogController()->logError("Error dropping foreign keys for table $table: " . $e->getMessage());
			throw new RestoreException($e->getMessage());
		}
	}

	/**
	 * @param $table_name
	 * @param $column_name
	 * @param $id_column
	 *
	 * @return void
	 * @throws Exception
	 */
	private function _replacePrefixInTable($table_name, $column_name, $id_column) {
		$this->getLogController()->logMessage("Starting prefix replacement in $table_name.$column_name");

		$backup_prefix = $this->_getDatabasePrefix();
		$mysql_auth = $this->_fetchMySQLAuth();

		$select_query = "SELECT `$id_column`, `$column_name` FROM `$table_name` WHERE TRIM(`$column_name`) LIKE :old_prefix";
		$this->getLogController()->logMessage("Running SQL: $select_query with :old_prefix => '$backup_prefix%'");

		$rows = $this->_mysql->query_exec($select_query, [':old_prefix' => $backup_prefix . '%']);

		$this->getLogController()->logMessage("Number of rows found: " . count($rows));

		if (!$rows) {
			$this->getLogController()->logMessage("No rows found needing prefix replacement in $table_name.$column_name");
			return;
		}

		foreach ($rows as $row) {
			$original_data = $row->{$column_name};
			$new_data = str_replace($backup_prefix, $mysql_auth->table_prefix, $original_data);

			// Log the raw data
			$this->getLogController()->logMessage("Row data for $id_column: {$row->{$id_column}} => $original_data");

			// Log the before and after values
			$this->getLogController()->logMessage("Original value in {$table_name}.{$column_name} for {$id_column}: {$row->{$id_column}} is: {$original_data}");
			$this->getLogController()->logMessage("New value after replacement in {$table_name}.{$column_name} for {$id_column}: {$row->{$id_column}} is: {$new_data}");

			if ($new_data !== $original_data) {
				$update_query = "UPDATE `{$table_name}` SET `{$column_name}` = :new_value WHERE `{$id_column}` = :id";
				$this->getLogController()->logMessage("Updating {$table_name}.{$column_name} for {$id_column}: {$row->{$id_column}}");
				$this->getLogController()->logMessage("Executing update query: {$update_query} with new_value: {$new_data}, id: {$row->{$id_column}}");

				$this->_mysql->query_exec($update_query, [
					':new_value' => $new_data,
					':id' => $row->{$id_column},
				]);
				$this->getLogController()->logMessage("Updated {$table_name}.{$column_name} for {$id_column}: {$row->{$id_column}} successfully");
			} else {
				$this->getLogController()->logMessage("No change required for {$id_column}: {$row->{$id_column}}");
			}
		}
	}

	public function _postRestoreHealthCheck():void {

		$this->getQueueItem()->updateStatus(Queue::STATUS_RESTORE_POST_RESTORE_HEALTH_CHECK);
		$this->getQueueItem()->updateProgress('Post restore - Health Check');
		$this->getLogController()->logMessage('Post restore - Health Check');
		$this->func([$this, '_checkWPHealth']);
		$this->func([$this, '_checkDatabaseConnection']);
		$this->func([$this, '_checkConfig']);
		$this->func([$this, '_flushRewriteRules']);
	}


	public function _checkConfig() {

		$this->getLogController()->logMessage('Testing wp-config file');

		$wp_config_file = JetBackup::WP_ROOT_PATH . JetBackup::SEP . 'wp-config.php';

		if (!file_exists($wp_config_file)) {
			$this->getLogController()->logError('wp-config.php is missing');
			return;
		}

		$required_constants = ['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST'];
		foreach ($required_constants as $constant) {
			if (!defined($constant)) $this->getLogController()->logError("Constant $constant is not defined in wp-config.php");
		}

		$this->getLogController()->logMessage('wp-config file OK');
	}

	public function _flushRewriteRules() {
		if (!function_exists('flush_rewrite_rules')) return;
		if (function_exists('wp_cache_flush')) wp_cache_flush();
		$this->getLogController()->logMessage('Executing flush_rewrite_rules immediately');
		flush_rewrite_rules(true);
	}

	public function _checkDatabaseConnection() {
		global $wpdb;
		$this->getLogController()->logMessage('Testing Database Connection');

		try {
			if (!isset($wpdb) || empty($wpdb)) $this->_loadWP();
			$wpdb->get_results("SELECT 1");
			$this->getLogController()->logMessage('Database Connection OK');
		} catch (Exception $e) {
			$this->getLogController()->logError($e->getMessage());
		}
	}

	/**
	 * @return void
	 *
	 * Some hosts use symlinked mu-plugins pointing to shared system directories.
	 * During migration, these links break, causing missing files and errors.
	 *
	 */
	public function _validateMUPlugins() {

		$mu_plugins_path = self::MU_PLUGINS_PATH;

		$this->getLogController()->logMessage("Validating MU Plugins folder: {$mu_plugins_path}");
		if (!is_dir($mu_plugins_path)) {
			$this->getLogController()->logMessage("MU Plugins folder does not exist: {$mu_plugins_path}, nothing to do...");
			return;
		}

		$mu_plugins = glob($mu_plugins_path . JetBackup::SEP . '*.php');
		$broken_links = [];

		if (empty($mu_plugins)) {
			$this->getLogController()->logMessage("No MU Plugins found.");
			return;
		}

		foreach ($mu_plugins as $plugin) {
			if (is_link($plugin) && !file_exists($plugin)) $broken_links[] = $plugin;
		}

		if (empty($broken_links)) {
			$this->getLogController()->logMessage("No Broken MU Plugins found, folder is valid.");
			return;
		}

		// Remove broken links
		foreach ($broken_links as $link) {
			$this->getLogController()->logError("Broken MU Plugin found: {$link}");
			if (@unlink($link)) {
				$this->getLogController()->logMessage("Successfully removed: {$link}");
			} else {
				$this->getLogController()->logError("Failed to remove: {$link}");
			}
		}

		$this->getLogController()->logDebug("Broken links removed: " . json_encode($broken_links));

	}



	public function _checkWPHealth() {

		try {
			$this->_loadWP();

			// Ensure WordPress is fully loaded by checking a key constant or function
			if (!function_exists('wp_get_current_user'))
				throw new Exception('WordPress did not fully load. A fatal error may have occurred.');

		} catch(Exception $e) {
			$this->getLogController()->logError('Failed to load WordPress: ' . $e->getMessage());

			$this->func(function() {

				$this->getLogController()->logMessage('Scanning and disabling all active plugins');

				$plugin_dirs = glob(self::PLUGINS_PATH . JetBackup::SEP . '*', GLOB_ONLYDIR);

				$this->getLogController()->logDebug('Found plugin directories: ' . json_encode($plugin_dirs));

				$this->foreach($plugin_dirs, function($i, $plugin_dir) {
					$this->_disablePlugin(basename($plugin_dir));
				}, '_disableAllPlugins');

			}, [], '_disableAllPlugins');

			$disabled_plugins = glob(self::PLUGINS_PATH . JetBackup::SEP . '*_disabled_' . $this->getQueueItem()->getUniqueId(), GLOB_ONLYDIR);

			$this->foreach($disabled_plugins, function($i, $plugin_dir) {

				$plugin_name = basename($plugin_dir);

				$this->_enablePlugin($plugin_name);

				try {
					$this->_loadWP();
				} catch(Exception $e) {
					$this->getLogController()->logError('Fatal error detected when enabling plugin: ' . $plugin_name . ' - ' . $e->getMessage());
					$this->_disablePlugin($plugin_name);
				}

			}, '_checkPluginsOneByOne');
		}
	}

	public function _enablePlugin($plugin_name):void {

		$plugin_dir = self::PLUGINS_PATH . JetBackup::SEP . $plugin_name;
		$plugin_dir_disabled = $plugin_dir . '_disabled_' . $this->getQueueItem()->getUniqueId();

		if(!file_exists($plugin_dir_disabled)) return;

		$this->getLogController()->logMessage("Enabling plugin: $plugin_name");

		if (!is_dir($plugin_dir_disabled)) {
			$this->getLogController()->logError("Failed to rename plugin directory $plugin_dir_disabled");
			return;
		}

		rename($plugin_dir_disabled, $plugin_dir);
		$this->getLogController()->logMessage("Renamed plugin directory from $plugin_dir_disabled to $plugin_dir");

		if (in_array($plugin_name, self::CACHE_PLUGINS)) {

			$cache_file = JetBackup::WP_ROOT_PATH . JetBackup::SEP . 'wp-content' . JetBackup::SEP . 'object-cache.php';
			if (file_exists($cache_file)) {
				$this->getLogController()->logMessage("Renamed $cache_file to enable it");
				rename($cache_file . '_disabled_' . $this->getQueueItem()->getUniqueId(), $cache_file);
			}
		}
	}

	// disable plugin from the db will not cover some fatal errors, we are forcing it by changing the name
	private function _disablePlugin($plugin_name):void {

		$plugin_dir = self::PLUGINS_PATH . JetBackup::SEP . $plugin_name;
		$plugin_dir_disabled = $plugin_dir . '_disabled_' . $this->getQueueItem()->getUniqueId();
		$plugin_file = $plugin_dir . JetBackup::SEP . $plugin_name . '.php';

		if(!file_exists($plugin_file)) return;

		$this->getLogController()->logMessage("Disabling plugin: $plugin_name");

		if(in_array($plugin_name, self::PROTECTED_PLUGINS)) {
			$this->getLogController()->logMessage("Skipped disabling protected plugin: $plugin_name");
			return;
		}

		if(file_exists($plugin_dir_disabled)) rename($plugin_dir_disabled, $plugin_dir_disabled . '_' . Util::generateRandomString());

		if (!is_dir($plugin_dir)) {
			$this->getLogController()->logError("Failed to rename plugin directory $plugin_dir");
			return;
		}

		rename($plugin_dir, $plugin_dir_disabled);
		$this->getLogController()->logMessage("Renamed plugin directory from $plugin_dir to $plugin_dir_disabled");

		if (in_array($plugin_name, self::CACHE_PLUGINS)) {
			$this->getLogController()->logMessage("Problematic plugin is object cache, disabling 'object-cache.php' file");

			$cache_file = JetBackup::WP_ROOT_PATH . JetBackup::SEP . 'wp-content' . JetBackup::SEP . 'object-cache.php';
			if (file_exists($cache_file)) {
				$this->getLogController()->logMessage("Renamed $cache_file to disable it");
				rename($cache_file, $cache_file . '_disabled_' . $this->getQueueItem()->getUniqueId());
			}
		}

		//$this->_disabled_plugins[] = $plugin_name;
	}


	/**
	 * @return void
	 * @throws Exception
	 */
	private function _loadWP():void {
		ob_start(); // Start output buffering

		$load_file = JetBackup::WP_ROOT_PATH . JetBackup::SEP . 'wp-load.php';

		try {

			if (file_exists($load_file)) {
				require_once($load_file);

				// Temporary disable cron events
				if(!defined('DISABLE_WP_CRON')) define('DISABLE_WP_CRON', true);

				$output = ob_get_clean(); // Get buffered output

				// Log the output, even if it seems normal
				if (!empty($output)) $this->getLogController()->logMessage('Output from WordPress load: ' . $output);
				$this->getLogController()->logMessage('WordPress ecosystem loaded successfully');

			} else {
				throw new Exception('wp-load.php not found in the specified WordPress root.');
			}

		} catch (\Throwable $e) {
			$output = ob_get_clean(); // Clean the buffer in case of an error
			$this->getLogController()->logError('Failed loading WordPress: ' . $e->getMessage() . ' | Buffered Output: ' . $output);
			throw new Exception('Failed loading WordPress: ' . $e->getMessage());
		}
	}
}