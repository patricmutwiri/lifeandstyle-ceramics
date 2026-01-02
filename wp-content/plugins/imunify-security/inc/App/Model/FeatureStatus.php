<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Model;

/**
 * Feature status enum-like class.
 */
class FeatureStatus {
	/**
	 * Feature is enabled.
	 *
	 * @var string
	 */
	const ENABLED = 'ENABLED';

	/**
	 * Feature is disabled.
	 *
	 * @var string
	 */
	const DISABLED = 'DISABLED';

	/**
	 * Get all possible status values.
	 *
	 * @return array<string>
	 */
	public static function getAll() {
		return array(
			self::ENABLED,
			self::DISABLED,
		);
	}

	/**
	 * Check if a status value is valid.
	 *
	 * @param string $status Status to check.
	 *
	 * @return bool
	 */
	public static function isValid( $status ) {
		return in_array( $status, self::getAll(), true );
	}

	/**
	 * Get the default status.
	 *
	 * @return string
	 */
	public static function getDefault() {
		return self::DISABLED;
	}

	/**
	 * Get the translated label for a status value.
	 *
	 * @param string $status Status value.
	 *
	 * @return string
	 */
	public static function getLabel( $status ) {
		switch ( $status ) {
			case self::ENABLED:
				return esc_html__( 'Enabled', 'imunify-security' );
			case self::DISABLED:
				return esc_html__( 'Disabled', 'imunify-security' );
			default:
				return '';
		}
	}
}
