<?php

namespace JetBackup\ResumableTask;

use JetBackup\Cron\Task\Task;
use JetBackup\DirIterator\DirIterator;
use JetBackup\Exception\DirIteratorException;
use JetBackup\Exception\ExecutionTimeException;
use JetBackup\Exception\IOException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use JetBackup\Log\LogController;
use SleekDB\Exceptions\InvalidArgumentException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class   ResumableTask {

	const TYPE_FUNC         = 1;
	const TYPE_FOREACH      = 2;
	const TYPE_SCAN         = 3;
	const TYPE_FILE_READ    = 4;
	const TYPE_FILE_MERGE   = 5;

	const PARAMS_EXECUTION_TIME = 'ExecutionTimeCallback';
	
	private $_swap_filename;
	private LogController $_logController;
	private ?Task $_task = null;
	private $_filename;
	private array $_items=[];

	public function __construct(string $id, $tmp_dir = null) {

		if (!$tmp_dir) $tmp_dir = Factory::getLocations()->getTempDir();
		$this->_filename = $tmp_dir . JetBackup::SEP . $id . '.resume';
		$this->_swap_filename = $this->_filename . '.swp';
		$this->_logController = new LogController();

		if (!file_exists(dirname($this->_filename))) mkdir(dirname($this->_filename), 0700, true);

		// If swap file exists, validate and use it
		if (file_exists($this->_swap_filename)) {
			if (filesize($this->_swap_filename) > 0) {
				$contents = @file_get_contents($this->_swap_filename);
				$data = $contents ? @unserialize($contents) : false;

				if ($data !== false && is_array($data)) {
					$this->_items = $data;
					rename($this->_swap_filename, $this->_filename); // Promote swap file to main file
					$this->_logController->logDebug("Swap file found, using it for $this->_filename");
				} else {
					unlink($this->_swap_filename); // Invalid swap file, discard it
					$this->_logController->logError("Invalid or corrupted swap file: {$this->_swap_filename} discarded.");
				}
			} else {
				unlink($this->_swap_filename); // Zero-sized swap file, discard it
				$this->_logController->logError("Zero-sized swap file: {$this->_swap_filename} discarded.");
			}
		}

		// If main file exists, load data
		if (file_exists($this->_filename)) {
			$this->_logController->logDebug("[ResumeableTask] resume file found, trying to load data from: {$this->_filename}");
			$contents = @file_get_contents($this->_filename);
			$data = $contents ? @unserialize($contents) : false;

			if ($data !== false && is_array($data)) {
				$this->_items = $data;
			} else {
				$this->_items = []; // Start fresh if main file is invalid
				$this->_logController->logError("Invalid or corrupted main file: {$this->_filename}.");
			}
		} else {
			// Create the main file if it doesn't exist
			touch($this->_filename);
		}
	}


	/**
	 * @param LogController $logController
	 *
	 * @return void
	 */
	public function setLogController(LogController $logController) { $this->_logController = $logController; }

	/**
	 * @param Task $task
	 *
	 * @return void
	 */
	public function setTask(Task $task) { $this->_task = $task; }

	/**
	 * @return LogController
	 */
	public function getLogController():LogController { return $this->_logController; }

	public function _getItem($name, $type):ResumableTaskItem {

		$item = $this->_items[$name.'|'.$type] ?? null;
		
		if(!$item) {
			$item = new ResumableTaskItem();
			$this->_items[$name.'|'.$type] = $item;
		}

		return $item;
	}

	/**
	 * @throws IOException
	 */
	private function _update(): void {
		$temp_file = $this->_swap_filename;

		if (file_put_contents($temp_file, serialize($this->_items), LOCK_EX) === false) {
			throw new IOException("Failed to write to temporary file: $temp_file");
		}

		if (!rename($temp_file, $this->_filename)) {
			unlink($temp_file);
			throw new IOException("Failed to rename temporary file: $temp_file to $this->_filename");
		}
	}


	private static function _getCallableName(callable $callable):?string {
		if(is_string($callable)) return $callable;
		if(is_array($callable) && is_object($callable[0])) return get_class($callable[0])  . '->' . $callable[1];
		if(is_array($callable)) return $callable[0]  . '::' . $callable[1];
		return null;
	}


	public function func(callable $func, array $args=[], ?string $name=null) {
		if(!$name && !($name = self::_getCallableName($func))) throw new \Exception("Can't find callable name");

		$item = $this->_getItem($name, self::TYPE_FUNC);
		if($item->isCompleted()) return $item->getResult();

		if($item->getData()) $args = $item->getData();
		else {
			$item->setData($args);
			$this->_update();
		}

		foreach($args as $i => $arg) {
			if($arg != self::PARAMS_EXECUTION_TIME) continue;
			$args[$i] = function() { if($this->_task) $this->_task->checkExecutionTime(); };
		}

		if($this->_task) $this->_task->checkExecutionTime();
		
		$item->setResult(call_user_func_array($func, $args));
		$item->setData([]);
		$item->setCompleted(true);
		$this->_update();

		return $item->getResult();
	}

	public function foreachCallable(callable $data, array $args, callable $func, ?string $name=null) {
		if(!$name) $name = self::_getCallableName($data);

		$item = $this->_getItem($name, self::TYPE_FOREACH);
		if($item->isCompleted()) return;
		$data = $item->getData() ? [] : call_user_func_array($data, $args);
		$this->foreach($data, $func, $name);
	}
	
	public function foreach(array $data, callable $func, ?string $name=null):void {
		if(!$name) $name = sha1(serialize($data));

		$item = $this->_getItem($name, self::TYPE_FOREACH);
		if($item->isCompleted()) return;

		if($item->getData()) $data = $item->getData();
		else {
			$data = ['records' => $data, 'total' => count($data)];
			$item->setData($data);
			$this->_update();
		}

		foreach($data['records'] as $key => $value) {

			if($this->_task) $this->_task->checkExecutionTime();
			
			call_user_func_array($func, [$key, $value, $data['total']]);
			unset($data['records'][$key]);

			$item->setData($data);
			$this->_update();
		}

		$item->setCompleted(true);
		$this->_update();
	}

	public function for(array $data, callable $func, ?string $name=null):void {
		$this->foreach(array_values($data), $func, $name);
	}

	/**
	 * @param string $source
	 * @param callable $func
	 * @param array $excludes
	 * @param string|null $name
	 *
	 * @return void
	 * @throws DirIteratorException
	 * @throws IOException
	 * @throws ExecutionTimeException
	 * @throws \SleekDB\Exceptions\IOException
	 * @throws InvalidArgumentException
	 */
	public function scan(string $source, callable $func, array $excludes=[], ?string $name=null):void {

		$name = sha1($source . ($name ?: ''));

		$item = $this->_getItem($name, self::TYPE_SCAN);
		if($item->isCompleted()) return;
		$tree_file_name = $this->_filename . '.' . $name . '.scan';
		$this->getLogController()->logDebug("[ResumeableTask][scan] Tree file: {$tree_file_name}");
		$scan = new DirIterator($tree_file_name);
		$scan->setSource($source);
		$scan->setLogController($this->getLogController());
		$scan->setCallBack(function ($type, $filename, $fileCount) {

			if(!$this->_task) return;
			if($type == 'error') $this->_task->getQueueItem()->addError();
			if($type != 'file') return;

			$progress = $this->_task->getQueueItem()->getProgress();
			$progress->setMessage("Scanned [$fileCount] files...");
			// if we will not 'zero' the percentage, we will see the status bar of the previous item (if < %100)
			$progress->setTotalSubItems(0);
			$progress->setCurrentSubItem(0);
			$this->_task->getQueueItem()->save();
			$this->_task->checkExecutionTime();
			//$this->getLogController()->logDebug("[ResumableTask] File count: $fileCount");
		});
		if($excludes) $scan->setExcludes($excludes);
		if($item->getData()) {
			$data = $item->getData();
		} else {
			$data = new \stdClass();
			$data->total_size = $data->current_pos = $scan->getTotalFiles();
			$item->setData($data);
			$this->_update();
		}
		
		while($scan->hasNext()) {

			if($this->_task) $this->_task->checkExecutionTime();

			call_user_func_array($func, [$scan, $data]);

			$data->current_pos--;
			$item->setData($data);
			$this->_update();
		}

		$scan->done();
	}


	/**
	 * @throws IOException
	 */
	public function fileMerge(string $source, string $target, ?string $name=null):void {

		$this->getLogController()->logDebug("[fileMerge] Source: $source");
		$this->getLogController()->logDebug("[fileMerge] Target: $target");

		$name = sha1($source . $target . ($name ?: ''));

		$item = $this->_getItem($name, self::TYPE_FILE_MERGE);
		if($item->isCompleted()) return;

		if($item->getData()) {
			$data = $item->getData();
			$this->getLogController()->logDebug("[fileMerge] Retrieved data from item->getData()");
		} else {
			$this->getLogController()->logDebug("[fileMerge] item->getData() is empty, starting fresh data holder");
			$data = new \stdClass();
			$data->read_line = 0;
			$data->write_line = 0;

			$item->setData($data);
			$this->_update();
		}

		$source_fd = fopen($source, 'r');
		if (!$source_fd) throw new IOException("Failed to open file: $source");

		if(!file_exists($target)) touch($target);
		$target_fd = fopen($target, 'a+');
		if (!$target_fd) throw new IOException("Failed to open file: $target");

		// move target pointer to the correct position (by reading line by line and stopping when needed)
		$line = 0;
		while($data->write_line > 0 && fgets($target_fd) !== false) if($line++ >= $data->write_line) break;
		//

		$line = 0;

		while(($buffer = fgets($source_fd)) !== false) {
			if($line++ <= $data->read_line) continue;
			if($this->_task) $this->_task->checkExecutionTime(function() use($source_fd, $target_fd) { fclose($source_fd); fclose($target_fd); });

			fwrite($target_fd, $buffer . PHP_EOL);

			$data->write_line++;
			$data->read_line++;

			$item->setData($data);
			$this->_update();
			$this->getLogController()->logDebug("[fileMerge] Merging '" . basename($source) . "' -> '" . basename($target) . "' | Target write position: " . $data->write_line);

		}

		fclose($source_fd);
		fclose($target_fd);

	}






	/**
	 * @throws IOException
	 */
	public function fileRead(string $file_path, callable $func, ?string $name=null) {
		$name = sha1($file_path . ($name ?: ''));

		$item = $this->_getItem($name, self::TYPE_FILE_READ);
		if($item->isCompleted()) return;

		if($item->getData()) $data = $item->getData();
		else {
			$data = new \stdClass();
			$data->line = 0;

			$item->setData($data);
			$this->_update();
		}

		$fd = fopen($file_path, 'r');
		if (!$fd) throw new IOException("Failed to open file: $file_path");

		$line = 0;
		
		while(($buffer = fgets($fd)) !== false) {
			
			if($data->line > $line) {
				$line++;
				continue;
			}

			if($this->_task) $this->_task->checkExecutionTime(function() use($fd) { fclose($fd); });

			call_user_func_array($func, [$buffer, $data]);

			$line++;
			$data->line++;
			
			$item->setData($data);
			$this->_update();
		}
		
		fclose($fd);
	}
	
	public function delete():void {
		if(file_exists($this->_swap_filename)) unlink($this->_swap_filename);
		if(file_exists($this->_filename)) unlink($this->_filename);
	}
}