<?php

namespace JetBackup\Restore;

use Exception;
use JetBackup\Cron\Task\Task;
use JetBackup\Exception\ExecutionTimeException;
use JetBackup\Exception\RestoreException;
use JetBackup\Factory;
use JetBackup\IO\Lock;
use JetBackup\JetBackup;
use JetBackup\Queue\Queue;
use JetBackup\Queue\QueueItem;
use JetBackup\Wordpress\Wordpress;
use Throwable;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Restore {

	const ACTION_RESTORE = 'restore';
	const ACTION_STATUS = 'status';
	const ACTION_CANCEL = 'cancel';
	const ACTION_COMPLETED = 'completed';

	private QueueItem $_queue;
	private Task $_task;
	
	public function __construct(QueueItem $queueItem) {
		$this->_queue = $queueItem;
		$this->_task = new \JetBackup\Cron\Task\Restore();
		$this->_task->setQueueItem($queueItem);
		$this->_task->setExecutionTimeLimit(30);
		$this->_task->setExecutionTimeDie(false);
	}
	
	public function execute($action) {
		switch ($action) {
			case self::ACTION_CANCEL: $this->_actionCancel();
			case self::ACTION_COMPLETED: $this->_actionCompleted();
			case self::ACTION_STATUS: $this->_actionStatus();
			case self::ACTION_RESTORE: $this->_actionRestore();
		}
	}

	private static function _getRestorePath() : string {
		return Factory::getSettingsRestore()->isRestoreAlternatePathEnabled() ? WP_ROOT . JetBackup::SEP . JetBackup::CRON_PUBLIC_URL : WP_ROOT;
	}

	private static function _getRestoreFileName(): string {
		if (!isset($_SERVER['SCRIPT_FILENAME'])) return '';
		$script = Wordpress::getUnslash($_SERVER['SCRIPT_FILENAME']);
		$script = Wordpress::sanitizeTextField($script);
		return basename($script);
	}


	private static function _getRestoreFile() : string {
		return self::_getRestorePath() . JetBackup::SEP . self::_getRestoreFileName();
	}

	private static function _getLockFile() : string {
		return self::_getRestoreFile() . '.lock';
	}

	private function _actionRestore() {

		if (!Lock::LockFile(self::_getLockFile())) {
			self::_output(true, 'Already running...', [
				'status'     => $this->_queue->getStatus(),
			]);
		}
		
		if($this->_queue->getStatus() < Queue::STATUS_DONE) {
			try {
				$this->_task->execute();
			} catch(ExecutionTimeException $e) {
				self::_output(true, 'Execution time reached', [ 'status' => $this->_queue->getStatus() ]);
			} catch(Exception $e) {
				self::_output(false, "Error: " . $e->getMessage());
			} catch(Throwable $e) {
				self::_output(false, "Fatal Error: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString());
			}
		}

		self::_output(true, 'Completed', [ 
			'status'     => $this->_queue->getStatus(), 
		]);
	}

	private static function _selfDelete() {
		@unlink(self::_getRestoreFile());
		@unlink(self::_getLockFile());
	}
	
	private function _actionCompleted() {
		
		if($this->_queue->getStatus() < Queue::STATUS_DONE)
			self::_output(false, 'Restore haven\'t completed yet');

		self::_selfDelete();

		self::_output(true, 'Completed Successfully');
	}
	
	private function _actionCancel() {
		
		$this->_queue->updateStatus(Queue::STATUS_ABORTED);
		$this->_queue->updateProgress('Restore aborted by the user', QueueItem::PROGRESS_LAST_STEP);

		self::_selfDelete();

		self::_output(true, 'Cancelled Successfully');
	}

	// In _actionStatus()

	private function _actionStatus() {
		$log_entries = [];
		$log_file = $this->_task->getLogFile();

		$chunk = '';
		$cursor_in = isset($_POST['cursor']) ? (int)$_POST['cursor'] : -1; // -1 = first call (tail last N lines)
		$next_cursor = 0;
		$reset = false;

		if (is_readable($log_file)) {
			$size = filesize($log_file);
			$next_cursor = $size;

			// rotation/shrink detection
			if ($cursor_in > $size) {
				$cursor_in = -1;
				$reset = true;
			}

			$fh = fopen($log_file, 'rb');
			if ($fh) {
				if ($cursor_in < 0) {
					// first call: tail last ~4KB or last 300 lines (whichever is smaller)
					$tailBytes = 4096;
					if ($size > $tailBytes) fseek($fh, -$tailBytes, SEEK_END);
					$raw = stream_get_contents($fh);
					// keep only last 300 lines to avoid blasting the client
					$lines = explode("\n", $raw);
					if (count($lines) > 300) {
						$lines = array_slice($lines, -300);
					}
					$chunk = implode("\n", $lines);
				} else {
					// incremental read
					fseek($fh, $cursor_in, SEEK_SET);
					// clamp max chunk (e.g., 64KB) to keep responses light
					$chunk = stream_get_contents($fh, 64 * 1024);
				}
				fclose($fh);
			}
		}

		$progress = $this->_queue->getProgress();

		self::_output(true, '', [
			'status'        => $this->_queue->getStatus(),
			'progress'      => $progress->getDisplay(),
			'log_chunk'     => $chunk,        // string (may be empty)
			'cursor'        => $next_cursor,  // new cursor for next request
			'reset'         => $reset,        // client should clear if true
		]);
	}


	private static function _output($success, $message, $data=[]) {
		die(json_encode([
			'success'       => $success ? 1 : 0,
			'message'       => $message,
			'data'          => $data,
		]));
	}

}