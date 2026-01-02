import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
// Fetch settings from the API
export const fetchSettings = async () => {
	try {
		const response = await apiFetch( {
			path: '/suremails/v1/get-settings',
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': suremails.nonce,
			},
		} );

		if ( typeof response !== 'object' ) {
			throw new Error( 'Invalid JSON response' );
		}
		return response;
	} catch ( error ) {
		throw new Error( error.message || 'Error fetching settings' );
	}
};

// Save settings to the API
export const saveSettings = async ( updatedSettings ) => {
	try {
		const response = await apiFetch( {
			path: '/suremails/v1/set-settings',
			method: 'POST',
			headers: {
				'X-WP-Nonce': suremails.nonce,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				settings: {
					delete_email_logs_after:
						updatedSettings.settings.delete_email_logs_after,
					log_emails: updatedSettings.settings.log_emails,
					email_simulation: updatedSettings.settings.email_simulation,
					default_connection:
						updatedSettings.settings.default_connection,
					emailSummary: updatedSettings.settings.emailSummary,
					emailSummaryDay: updatedSettings.settings.emailSummaryDay,
					analytics: updatedSettings.settings.analytics,
				},
			} ),
		} );
		return response;
	} catch ( error ) {
		throw new Error( error.message || 'Error saving settings' );
	}
};

/**
 * Authenticate the Content Guard
 *
 * @return {Object} The response
 */
export const activateContentGuard = async () => {
	try {
		return await apiFetch( {
			path: '/suremails/v1/content-guard/activate',
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': suremails.nonce,
			},
		} );
	} catch ( error ) {
		throw new Error(
			error.data.message ||
				__( 'Error activating Reputation Shield', 'suremails' )
		);
	}
};

/**
 * Save user details for Content Guard
 *
 * @param {Object} userDetails - The user details
 * @return {Object} The response
 */
export const saveUserDetails = async ( userDetails ) => {
	try {
		return await apiFetch( {
			path: '/suremails/v1/content-guard/user-details',
			method: 'POST',
			headers: {
				'X-WP-Nonce': suremails.nonce,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( userDetails ),
		} );
	} catch ( error ) {
		throw new Error( error.data.message || 'Error saving user details' );
	}
};
