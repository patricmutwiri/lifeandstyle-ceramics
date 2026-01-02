<?php

namespace JetBackup\Data;

use JetBackup\Exception\IOException;
use JetBackup\IO\Lock;
use ReflectionClass;
use ReflectionException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class ReflectionObject extends ArrayData {
	
	private string $_file;
	private string $_className;
	private array $_diff;

	/**
	 * @param string $file
	 * @param string $className
	 *
	 * @throws ReflectionException
	 * @throws IOException
	 */
	public function __construct(string $file, string $className) {
		$this->_file = $file;
		$this->_className = $className;
		$this->_diff = [];

		// Create the class if not exists
		if(!file_exists($this->_file)) {
			$this->loadFromDatabase();
			$this->save();
		}
		
		chmod($this->_file, 0600);

		require_once($this->_file);
		$this->setData((new ReflectionClass($this->_className))->getConstants());
	}

	function loadFromDatabase():void {}
	
	public function getDiff():array { return $this->_diff; }
	
	public function set($key, $value) {
		$this->_diff[$key] = $value;
		parent::set($key, $value);
	}
	
	/**
	 * @return void
	 * @throws IOException
	 */
	public function save():void {
		if (!($f = fopen($this->_file, 'w'))) {
			Lock::UnlockFile($this->_file . '.lock');
			throw new IOException("Error creating config file " . $this->_file);
		}
		fwrite($f, "<?php" . PHP_EOL);
		fwrite($f, "if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');" . PHP_EOL);
		fwrite($f, "class $this->_className {" . PHP_EOL);
		foreach ($this->getData() as $key => $value) {
			switch (true) {
				case is_array($value):
				case is_object($value):
					$value = ''; // Unsupported types default to an empty string
					break;
				case is_bool($value):
					$value = $value ? 'true' : 'false'; // Convert booleans to 'true' or 'false'
					break;
				case is_null($value):
					$value = 'null'; // set null values to 'null'
					break;
				case !is_int($value) && !is_float($value):
					$value = "'" . preg_replace("/([\\\'])/", "\\\\$1", $value) . "'"; // Wrap non-numeric strings in single quotes
					break;
			}
			fwrite($f, "\tconst $key = $value;" . PHP_EOL);
		}
		fwrite($f, "}");
		fclose($f);
	}
}