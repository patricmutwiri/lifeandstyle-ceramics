<?php

namespace JetBackup\Destination;

use JetBackup\Data\ArrayData;
use JetBackup\Exception\DBException;
use JetBackup\Log\LogController;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

abstract class DestinationWrapper implements \JetBackup\Destination\Integration\Destination {

	private ?string $_destination_name;
	private int $_destination_id;
	private LogController $_log_controller;
	private ArrayData $_options;
	private int $_chunk_size;
	private string $_path;

	public function __construct(int $chunk_size, string $path, ?LogController $logController=null, ?string $name=null, int $id=0) {
		$this->_destination_name = $name;
		$this->_destination_id = $id;
		$this->_log_controller = $logController ?: new LogController();
		$this->_options = new ArrayData();
		$this->_chunk_size = $chunk_size;
		$this->_path = $path;
	}

	/**
	 * @return string
	 */
	public function getName():string { return $this->_destination_name; }

	/**
	 * @return int
	 */
	public function getId():int { return $this->_destination_id; }

	/**
	 * @return string
	 */
	public function getPath():string { return preg_replace("#/+#", '/', $this->_path); }

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function getRealPath(string $path): string { return preg_replace("#/+#", '/', $this->getPath() . '/' . $path); }

	/**
	 * @param string $path
	 *
	 * @return string
	 */
	public function removeRealPath(string $path):string { return preg_replace("#^" . preg_quote($this->getPath()) . "#", "", $path); }

	/**
	 * @return LogController
	 */
	public function getLogController():LogController { return $this->_log_controller; }

	/**
	 * @return int
	 */
	public function getChunkSize():int { return $this->_chunk_size; }

	/**
	 * @return ArrayData
	 */
	public function getOptions():ArrayData { return $this->_options; }

	public function setSerializedData( string $data ) {
		$this->setData((object) json_decode($data));
	}

	public function getSerializedData(): string {
		return json_encode($this->getData());
	}

	/**
	 * @throws InvalidArgumentException
	 * @throws IOException
	 * @throws DBException
	 */
	public function save():void {
		if(!$this->getId()) return;
		$destination = new Destination($this->getId());
		if(!$destination->getId()) return;
		$destination->updateSerializedData($this->getSerializedData());
	}
}