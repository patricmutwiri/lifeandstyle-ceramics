<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Helpers;

/**
 * Helper class for formatting file paths.
 */
class PathFormatter {
	/**
	 * Maximum length of path before removing leading slash.
	 */
	const MAX_PATH_LENGTH = 60;

	/**
	 * WordPress home path (cached).
	 *
	 * @var string|null
	 */
	public static $home_path = null;

	/**
	 * Get the WordPress home path, with caching.
	 *
	 * @return string The WordPress home path.
	 */
	private static function getHomePath() {
		if ( null === self::$home_path ) {
			self::$home_path = function_exists( 'get_home_path' ) ? get_home_path() : '';
		}
		return self::$home_path;
	}

	/**
	 * Format a long file path.
	 *
	 * For paths longer than MAX_PATH_LENGTH characters, removes the leading slash if present.
	 *
	 * @param string $path The file path to format.
	 *
	 * @return string The formatted path.
	 */
	public static function formatLongPath( $path ) {
		if ( empty( $path ) ) {
			return '';
		}

		// Remove the path to WordPress home directory if it is present at the beginning of the path.
		$home_path = self::getHomePath();
		if ( ! empty( $home_path ) && 0 === strpos( $path, $home_path ) ) {
			$path = substr( $path, strlen( $home_path ) );
		}

		// Remove leading slash if the path is longer than MAX_PATH_LENGTH.
		if ( strlen( $path ) > self::MAX_PATH_LENGTH && 0 === strpos( $path, '/' ) ) {
			return substr( $path, 1 );
		}

		return $path;
	}
}
