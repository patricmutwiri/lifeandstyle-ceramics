<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Model;

/**
 * Feature type enum-like class.
 *
 * This class provides an enum-like functionality for feature types
 * that is compatible with PHP 7.4.
 */
class FeatureType {
	/**
	 * Malware Scanning feature type.
	 *
	 * @var string
	 */
	const MALWARE_SCANNING = 'MALWARE_SCANNING';

	/**
	 * Malware Cleanup feature type.
	 *
	 * @var string
	 */
	const MALWARE_CLEANUP = 'MALWARE_CLEANUP';

	/**
	 * Proactive Defence feature type.
	 *
	 * @var string
	 */
	const PROACTIVE_DEFENCE = 'PROACTIVE_DEFENCE';

	/**
	 * Get all available feature types.
	 *
	 * @return string[]
	 */
	public static function getAll() {
		return array(
			self::MALWARE_SCANNING,
			self::MALWARE_CLEANUP,
			self::PROACTIVE_DEFENCE,
		);
	}

	/**
	 * Check if a feature type is valid.
	 *
	 * @param string $type Feature type to check.
	 *
	 * @return bool True if the feature type is valid, false otherwise.
	 */
	public static function isValid( $type ) {
		return in_array( $type, self::getAll(), true );
	}

	/**
	 * Get the display name for a feature type.
	 *
	 * @param string $type Feature type.
	 *
	 * @return string Display name for the feature type.
	 */
	public static function getDisplayName( $type ) {
		switch ( $type ) {
			case self::MALWARE_SCANNING:
				return esc_html__( 'Malware Scanning', 'imunify-security' );
			case self::MALWARE_CLEANUP:
				return esc_html__( 'Malware Cleanup', 'imunify-security' );
			case self::PROACTIVE_DEFENCE:
				return esc_html__( 'Proactive Defence', 'imunify-security' );
			default:
				return '';
		}
	}

	/**
	 * Get the URL for a feature type.
	 *
	 * @param string $type Feature type.
	 *
	 * @return string URL for the feature type.
	 */
	public static function getUrl( $type ) {
		switch ( $type ) {
			case self::MALWARE_SCANNING:
			case self::MALWARE_CLEANUP:
				return 'https://imunify360.com/imunify-security-wp-plugin/#malware-scanning';
			case self::PROACTIVE_DEFENCE:
				return 'https://imunify360.com/imunify-security-wp-plugin/#proactive-defence';
			default:
				return '';
		}
	}

	/**
	 * Get the status for a feature type.
	 *
	 * @param string $type Feature type.
	 *
	 * @param array  $config Configuration data.
	 *
	 * @return string Status for the feature type.
	 */
	public static function getStatus( $type, $config = array() ) {
		// Always return DISABLED for malware cleanup if product is IMUNIFYAV.
		if ( self::MALWARE_CLEANUP === $type && isset( $config['license']['id'] ) && strtolower( $config['license']['id'] ) === 'imunifyav' ) {
			return FeatureStatus::DISABLED;
		}

		switch ( $type ) {
			case self::MALWARE_SCANNING:
				if ( isset( $config['MALWARE_SCANNING']['enable_scan_cpanel'] ) && $config['MALWARE_SCANNING']['enable_scan_cpanel'] ) {
					return FeatureStatus::ENABLED;
				}
				break;
			case self::MALWARE_CLEANUP:
				if ( isset( $config['MALWARE_SCANNING']['default_action'] ) && 'cleanup' === $config['MALWARE_SCANNING']['default_action'] ) {
					return FeatureStatus::ENABLED;
				}
				break;
			case self::PROACTIVE_DEFENCE:
				if ( isset( $config['PROACTIVE_DEFENCE']['blamer'] ) && $config['PROACTIVE_DEFENCE']['blamer'] ) {
					return FeatureStatus::ENABLED;
				}
				break;
		}

		return FeatureStatus::DISABLED;
	}
}
