<?php

namespace JetBackup\Settings;

use JetBackup\Exception\AjaxException;
use JetBackup\Exception\DBException;
use SleekDB\Exceptions\InvalidArgumentException;
use SleekDB\Exceptions\IOException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Integrations extends Settings {

	const SECTION = 'integrations';
	const ELEMENTOR = 'Elementor';
	const SUPERCACHE = 'Supercache';
	const WOOCOMMERCE = 'Woocommerce';
	const AUTOPTIMIZE = 'Autoptimize';
	const W3TOTALCACHE = 'W3TotalCache';

	const INTEGRATIONS = 'integrations';
	const INTEGRATION_DEFAULTS = [
		self::ELEMENTOR,
		self::SUPERCACHE,
		self::WOOCOMMERCE,
		self::W3TOTALCACHE,
		self::AUTOPTIMIZE,
	];
	/**
	 * @throws DBException
	 * @throws IOException
	 * @throws InvalidArgumentException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
	}

	public function getInegrations(): array {
		return $this->get(self::INTEGRATIONS, self::INTEGRATION_DEFAULTS);
	}

	public function setIntegrations(array $values) : void {
		$this->set(self::INTEGRATIONS, $values);
	}
	/**
	 * @return bool[]
	 */
	public function getDisplay():array {
		return  [self::INTEGRATIONS => $this->getInegrations()];
	}

	/**
	 * @return bool[]
	 */
	public function getDisplayCLI():array {
		return  [self::INTEGRATIONS => $this->getInegrations()];
	}

	/**
	 * @return void
	 * @throws AjaxException
	 */
	public function validateFields():void {
		$missing = array_diff($this->getInegrations(), self::INTEGRATION_DEFAULTS);

		if (!empty($missing)) {
			throw new AjaxException("The following integrations are not supported: " . implode(', ', $missing));
		}

	}
}