<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Model;

/**
 * Scan data model.
 */
class ScanData {
	/**
	 * Last scan timestamp (UTC).
	 *
	 * @var int
	 */
	private $lastScanTimestamp;

	/**
	 * Next scan timestamp (UTC).
	 *
	 * @var int
	 */
	private $nextScanTimestamp;

	/**
	 * List of malware items.
	 *
	 * @var MalwareItem[]
	 */
	private $malware;

	/**
	 * Username.
	 *
	 * @var string
	 */
	private $username = '';

	/**
	 * Configuration data.
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * License data.
	 *
	 * @var array
	 */
	private $license = array();

	/**
	 * Create from array
	 *
	 * @param array $data Data.
	 *
	 * @return self
	 */
	public static function fromArray( $data ) {
		$result                    = new self();
		$result->lastScanTimestamp = isset( $data['lastScanTimestamp'] ) ? $data['lastScanTimestamp'] : 0;
		$result->nextScanTimestamp = isset( $data['nextScanTimestamp'] ) ? $data['nextScanTimestamp'] : 0;
		$result->username          = isset( $data['username'] ) ? $data['username'] : '';
		$result->config            = isset( $data['config'] ) ? $data['config'] : array();
		$result->license           = isset( $data['license'] ) ? $data['license'] : array();
		if ( isset( $data['malware'] ) && is_array( $data['malware'] ) ) {
			$result->malware = array_map(
				function ( $item ) {
					return MalwareItem::fromArray( $item );
				},
				$data['malware']
			);

			// Leave only malicious malware.
			$result->malware = array_filter(
				$result->malware,
				function( $item ) {
					return $item->isMalicious();
				}
			);

			// Sort malware items by detection timestamp in descending order.
			usort(
				$result->malware,
				function( $a, $b ) {
					return $b->getDetectedAt() - $a->getDetectedAt();
				}
			);
		} else {
			$result->malware = array();
		}
		return $result;
	}

	/**
	 * Convert to array
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			'lastScanTimestamp' => $this->lastScanTimestamp,
			'nextScanTimestamp' => $this->nextScanTimestamp,
			'username'          => $this->username,
			'config'            => $this->config,
			'license'           => $this->license,
			'malware'           => array_map(
				function ( $item ) {
					return $item->toArray();
				},
				$this->malware
			),
		);
	}

	/**
	 * Get the last scan timestamp.
	 *
	 * @return int
	 */
	public function getLastScanTimestamp() {
		return $this->lastScanTimestamp;
	}

	/**
	 * Get the next scan timestamp.
	 *
	 * @return int
	 */
	public function getNextScanTimestamp() {
		return $this->nextScanTimestamp;
	}

	/**
	 * Get the malware.
	 *
	 * @return MalwareItem[]
	 */
	public function getMalware() {
		return $this->malware;
	}

	/**
	 * Get the username.
	 *
	 * @since 2.0.0
	 *
	 * @return string
	 */
	public function getUsername() {
		return $this->username;
	}

	/**
	 * Get the configuration data.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * Get the license data.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	public function getLicense() {
		return $this->license;
	}
}
