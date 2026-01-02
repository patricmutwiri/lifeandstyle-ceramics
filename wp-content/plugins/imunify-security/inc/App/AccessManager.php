<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App;

/**
 * Access manager.
 */
class AccessManager {
	/**
	 * Checks if current user has admin capabilities
	 *
	 * @return bool
	 */
	public function isUserAdmin() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Checks if current user can upgrade their Imunify product.
	 *
	 * User is allowed to upgrade if they don't already have Imunify 360 and the number of users
	 * (in the license object) is one.
	 *
	 * @param DataStore $dataStore Data store instance containing scan data.
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 */
	public function canUserUpgrade( DataStore $dataStore ) {

		if ( ! $this->isUserAdmin() ) {
			return false;
		}

		if ( self::isProductType( $dataStore, 'imunify360' ) ) {
			return false;
		}

		$scanData = $dataStore->getScanData();
		if ( ! $scanData ) {
			return false;
		}

		$license = $scanData->getLicense();
		if ( ! $license ) {
			return false;
		}

		if ( ! isset( $license['user_count'] ) || ! is_numeric( $license['user_count'] ) ) {
			return false;
		}

		return ( 1 === $license['user_count'] );
	}

	/**
	 * Checks if the product type matches the given product type.
	 *
	 * @param DataStore $dataStore Data store instance containing scan data.
	 * @param string    $productType Product type to check against, e.g., 'imunify360', 'imunifyav'.
	 *
	 * @return bool True if the product type matches, false otherwise.
	 *
	 * @since 2.0.0
	 */
	public static function isProductType( $dataStore, $productType ) {
		if ( ! $dataStore->isDataAvailable() ) {
			return false;
		}

		$scanData = $dataStore->getScanData();
		if ( ! $scanData ) {
			return false;
		}

		$license = $scanData->getLicense();
		if ( ! $license ) {
			return false;
		}

		return isset( $license['license_type'] ) && strtolower( $license['license_type'] ) === $productType;
	}
}
