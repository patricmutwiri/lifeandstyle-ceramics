<?php

namespace JetBackup\Settings;

use JetBackup\Exception\FieldsValidationException;

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

class Updates extends Settings {

	const SECTION = 'updates';
	
	const TIER_RELEASE = 'release';
	const TIER_RC = 'rc';
	const TIER_EDGE = 'edge';
	const TIER_ALPHA = 'alpha';

	const TIERS = [
		self::TIER_RELEASE,
		self::TIER_RC,
		self::TIER_EDGE,
		self::TIER_ALPHA,
	];
	
	const TIER_NAMES = [
		self::TIER_RELEASE  => 'Release',
		self::TIER_RC       => 'Release Candidate',
		self::TIER_EDGE     => 'Edge',
		self::TIER_ALPHA    => 'Alpha',
	];

	const UPDATE_TIER = 'UPDATE_TIER';
	const UPDATE_TIERS_LIST =  'UPDATE_TIERS_LIST';

	/**
	 * @throws \JetBackup\Exception\IOException
	 * @throws \ReflectionException
	 */
	public function __construct() {
		parent::__construct(self::SECTION);
	}

	/**
	 * @return string
	 */
	public function getUpdateTier():string { return $this->get(self::UPDATE_TIER, self::TIER_RELEASE); }

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	public function setUpdateTier(string $value):void { $this->set(self::UPDATE_TIER, $value); }

	/**
	 * @return array
	 */
	public function getDisplay():array {

		return [
			self::UPDATE_TIER                   => $this->getUpdateTier(),
			self::UPDATE_TIERS_LIST => self::TIER_NAMES
		];
	}

	/**
	 * @return string[]
	 */
	public function getDisplayCLI():array {
		return [
			'Update Tier'                   => $this->getUpdateTier(),
		];
	}

	/**
	 * @return void
	 * @throws FieldsValidationException
	 */
	public function validateFields():void {
		if(!in_array($this->getUpdateTier(), self::TIERS)) throw new FieldsValidationException("Invalid tier provided");
	}
}