<?php

namespace JetBackup\Ajax\Calls;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

use JetBackup\Ajax\aAjax;
use JetBackup\Destination\Destination;
use JetBackup\Exception\AjaxException;
use JetBackup\Exception\ConnectionException;
use JetBackup\Exception\DBException;
use JetBackup\Exception\DestinationException;
use JetBackup\Exception\RegistrationException;
use JetBackup\JetBackup;
use JetBackup\License\License;
use JetBackup\UserInput\UserInput;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;
use Throwable;

class ManageDestination extends aAjax {

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getId():int { return $this->getUserInput(JetBackup::ID_FIELD, 0, UserInput::UINT); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getName():string { return ($this->getUserInput(Destination::NAME, '', UserInput::STRING)); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getPath():string { return ($this->getUserInput(Destination::PATH, '', UserInput::STRING)); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getType():string { return ($this->getUserInput(Destination::TYPE, '', UserInput::STRING)); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getChunkSize():int { return ($this->getUserInput(Destination::CHUNK_SIZE, 1, UserInput::UINT)); }

	/**
	 * @return string
	 * @throws AjaxException
	 */
	private function _getNotes():string { return ($this->getUserInput(Destination::NOTES, '', UserInput::STRING)); }

	/**
	 * @return bool
	 * @throws AjaxException
	 */
	private function _getReadOnly():bool { return ($this->getUserInput(Destination::READ_ONLY, false, UserInput::BOOL)); }

	/**
	 * @return object|array|bool|float|int|mixed
	 * @throws AjaxException
	 */
	private function _getOptions():object { return ($this->getUserInput(Destination::OPTIONS, new \stdClass(), UserInput::OBJECT, UserInput::MIXED)); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getFreeDisk():int { return $this->getUserInput(Destination::FREE_DISK, 0, UserInput::UINT); }

	/**
	 * @return int
	 * @throws AjaxException
	 */
	private function _getExportConfig():int { return $this->getUserInput(Destination::EXPORT_CONFIG, 0, UserInput::UINT); }


	/**
	 * @throws IOException
	 * @throws AjaxException
	 * @throws InvalidArgumentException
	 * @throws DestinationException|DBException
	 */
	public function execute(): void {

		if($this->_getId()) {
			$destination = new Destination($this->_getId());
			if(!$destination->getId()) throw new AjaxException("Invalid destination id \"%s\" provided", [$this->_getId()]);
		} else {
			$destination = new Destination();
		}

		if($this->isset(Destination::TYPE)) $destination->setType($this->_getType());
		if($this->isset(Destination::NAME)) $destination->setName($this->_getName());
		if($this->isset(Destination::NOTES)) $destination->setNotes($this->_getNotes());
		if($this->isset(Destination::READ_ONLY)) $destination->setReadOnly($this->_getReadOnly());
		if($this->isset(Destination::CHUNK_SIZE)) $destination->setChunkSize($this->_getChunkSize());
		if($this->isset(Destination::PATH)) $destination->setPath($this->_getPath());
		if($this->isset(Destination::OPTIONS)) $destination->setOptions($this->_getOptions());
		if($this->isset(Destination::FREE_DISK)) $destination->setFreeDisk($this->_getFreeDisk());
		$destination->setExportConfig($this->_getExportConfig());
		$destination->setDefault($destination->isDefault());

		if(!License::isValid() && !in_array($destination->getType(), Destination::LICENSE_EXCLUDED))
			throw new AjaxException("You can't create/modify %s destination without a license", [$destination->getType()]);
		
		$destination->validateFields();

		if($this->isset(Destination::OPTIONS)) {

			$oldDestination = null;
			if($this->_getId()) {
				$oldDestination = new Destination($this->_getId());
				$oldDestination->unregister();
			}

			if($oldDestination) {
				$options_new = $destination->getOptions();
				$options_old = $oldDestination->getOptions();
				foreach($destination->protectedFields() as $field) {
					if(isset($options_new->{$field}) && !preg_match("/^" . preg_quote(Destination::PROTECTED_FIELD) . "$/", $options_new->{$field})) continue;
					if(isset($options_old->{$field})) $destination->setOptions((object) [$field => $options_old->{$field}]);
				}
			}

			try {
				$destination->register();
				$destination->connect();
			} catch(RegistrationException|ConnectionException $e) {
				$destination->unregister();
				self::_revert($e, $oldDestination);
			}
		}

		$destination->save();

		if($this->isset(Destination::OPTIONS)) $destination->addToQueue();

		$this->setResponseMessage('Success');
		$this->setResponseData($this->isCLI() ? $destination->getDisplayCLI() : $destination->getDisplay());

	}

	/**
	 * @param Throwable $e
	 * @param Destination|null $destination
	 *
	 * @return void
	 * @throws AjaxException
	 * @throws DestinationException
	 */
	private static function _revert( Throwable $e, ?Destination $destination=null):void {

		$message = "Registering Destination... Failed - Error: " . $e->getMessage();

		if(!$destination) throw new AjaxException($message, [], $e->getCode(), $e);

		try {
			$destination->register();
		} catch(RegistrationException $error) {
			$message .= "\n\nRegistering Destination with previous settings... Failed - Error: " . $error->getMessage() . "\n\nReverting to previous settings failed; Destination has been DISABLED";
			$destination->setEnabled(false);
			$destination->save();
			throw new AjaxException($message, [], $error->getCode(), $error);
		}
	}
}