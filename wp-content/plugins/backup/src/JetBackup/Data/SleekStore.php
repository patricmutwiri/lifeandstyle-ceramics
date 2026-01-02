<?php

namespace JetBackup\Data;

use JetBackup\Exception\DBException;
use JetBackup\Factory;
use JetBackup\JetBackup;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\InvalidConfigurationException;
use SleekDB\Exceptions\IOException;
use SleekDB\Query;
use SleekDB\Store;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class SleekStore extends Store {

	/**
	 * @throws DBException
	 * @throws InvalidArgumentException
	 * @throws IOException
	 */
	public function __construct($collection) {

		try {
			parent::__construct($collection, Factory::getLocations()->getDatabaseDir() . JetBackup::SEP, [
				'auto_cache'            => false,
				'cache_lifetime'        => 0,
				'timeout'               => false, // deprecated! Set it to false!
				'primary_key'           => JetBackup::ID_FIELD,
				'search'                => [
					'min_length'            => 2,
					'mode'                  => 'or',
					'score_key'             => 'scoreKey',
					'algorithm'             => Query::SEARCH_ALGORITHM['hits']
				],
				'folder_permissions'    => 0700
			]);
		} catch(InvalidConfigurationException $e) {
			throw new DBException($e->getMessage());
		}
	}

	public function clearCache() {
		if (!($path = $this->getStorePath())) return;
		
		$folder = $path.'cache';
		if (!is_dir($folder)) return;
		
		$cache_files = glob($folder . JetBackup::SEP . '*.json');
		foreach($cache_files as $file) unlink($file);
	}
}