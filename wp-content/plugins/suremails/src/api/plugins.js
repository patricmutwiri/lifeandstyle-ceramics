// @api/plugins.js
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Fetches the list of installed and active plugins.
 *
 * @return {Promise<Object>} The object containing installed and active plugins.
 */
export const fetchInstalledPluginsData = async () => {
	try {
		const response = await apiFetch( {
			path: '/suremails/v1/installed-plugins',
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': suremails.nonce,
			},
		} );

		if (
			response?.success &&
			response?.plugins?.installed &&
			response?.plugins?.active
		) {
			return {
				installed: response.plugins.installed,
				active: response.plugins.active,
			};
		}

		throw new Error(
			__( 'Invalid data received from server.', 'suremails' )
		);
	} catch ( error ) {
		throw new Error(
			error.message ||
				__( 'Failed to fetch installed plugins.', 'suremails' )
		);
	}
};
