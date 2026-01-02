<?php

if (!defined( '__JETBACKUP__')) die('Direct access is not allowed');

/**
 * @param $data
 * @param $exit
 *
 * @return void
 */
function po($data, $exit=0) {
	echo "<pre>";
	print_r($data);
	if($exit) exit;
}

/**
 * PHP 7.4 Backward compatability
 * This is taken and provided by WordPress, however during restore procedure we are not inside WordPress ecosystem
 */
if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * @param $haystack
	 * @param $needle
	 *
	 * @return bool
	 */
	function str_starts_with( $haystack, $needle ): bool {
		if ( '' === $needle ) return true;
		return 0 === strpos( $haystack, $needle );
	}
}

/**
 * PHP 7.4 Backward compatability
 * This is taken and provided by WordPress, however during restore procedure we are not inside WordPress ecosystem
 */
if ( ! function_exists( 'str_ends_with' ) ) {
	/**
	 * @param $haystack
	 * @param $needle
	 *
	 * @return bool
	 */
	function str_ends_with( $haystack, $needle ): bool {
		if ( '' === $haystack && '' !== $needle ) return false;
		$len = strlen( $needle );
		return 0 === substr_compare( $haystack, $needle, -$len, $len );
	}
}