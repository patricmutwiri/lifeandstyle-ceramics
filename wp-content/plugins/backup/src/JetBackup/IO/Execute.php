<?php

namespace JetBackup\IO;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Execute {
	
	public const PROC_OPEN = 'proc_open,proc_get_status';
	public const EXEC = 'exec';
	public const SHELL_EXEC = 'shell_exec';
	
	const EXEC_OPTIONS = [
		self::PROC_OPEN,
		self::EXEC,
		self::SHELL_EXEC,
	];

	/**
	 * 
	 */
	private function __construct() {}

	/**
	 * @param string $cmd
	 * @param array|null $output
	 * @param string|null $error
	 *
	 * @return int
	 */
	public static function run(string $cmd, ?array &$output=null, ?string &$error=null):int {

		foreach(self::getAvailable() as $option) {

			switch ($option) {
				case self::EXEC:
				case self::PROC_OPEN:
					if($option == self::PROC_OPEN) Process::exec($cmd, $o, $code);
					else exec($cmd, $o, $code);
					
					if($code) $error = implode("\n", $o);
					else $output = $o;
					
				return $code;
					
				case self::SHELL_EXEC:
					$o = shell_exec($cmd);
					$output = $o ? explode("\n", $o) : [];
				return $o ? 0 : 1;
			}
		}
		
		$error = "No available execution function found";
		return 1;
	}

	/**
	 * @return array
	 */
	public static function getAvailable(): array {
		
		$output = [];
		foreach(self::EXEC_OPTIONS as $option) {
			$funcs = explode(',', $option);
			foreach ($funcs as $func) if(!function_exists($func)) continue 2;
			$output[] = $option;
		}
		return $output;
	}

}