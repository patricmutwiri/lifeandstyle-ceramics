// @api/connections.js
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Fetches the provider list from the server.
 *
 * @return {Promise<Object>} The response containing the provider list.
 */
export const getProviders = async () => {
	try {
		const response = await apiFetch( {
			path: '/suremails/v1/providers',
			method: 'GET',
		} );
		if ( typeof response !== 'object' ) {
			throw new Error( 'Invalid JSON response' );
		}
		return response;
	} catch ( error ) {
		throw new Error( error.message || 'Error fetching providers' );
	}
};

/**
 * Fetches connection settings from the server.
 *
 * @return {Promise<Object>} The response containing connection settings.
 */
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

/**
 * Deletes a specific connection.
 *
 * @param {Object} connection            - The connection object to delete.
 * @param {string} connection.id         - The ID of the connection.
 * @param {string} connection.type       - The type of the connection.
 * @param {string} connection.from_email - The from_email of the connection.
 * @return {Promise<Object>} The response from the server.
 */
export const deleteConnection = async ( connection ) => {
	if ( ! connection || ! connection.id ) {
		throw new Error( __( 'Invalid connection data.', 'suremails' ) );
	}

	try {
		const response = await apiFetch( {
			path: '/suremails/v1/delete-connection',
			method: 'DELETE',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': suremails.nonce,
			},
			body: JSON.stringify( {
				type: connection.type,
				from_email: connection.from_email,
				id: connection.id,
			} ),
		} );

		return response;
	} catch ( error ) {
		throw new Error(
			error.message ||
				__( 'There was an issue deleting the connection.', 'suremails' )
		);
	}
};

/**
 * Tests and saves an email connection.
 *
 * @param {Object} payload          - The payload containing connection details.
 * @param {Object} payload.settings - The connection settings.
 * @param {string} payload.provider - The provider type (e.g., 'AWS', 'SMTP').
 * @return {Promise<Object>} The response from the server.
 */
export const testAndSaveEmailConnection = async ( payload ) => {
	if ( ! payload || ! payload.settings || ! payload.provider ) {
		throw new Error(
			__( 'Invalid payload for testing connection.', 'suremails' )
		);
	}

	try {
		const response = await apiFetch( {
			path: '/suremails/v1/test-and-save-email-connection',
			method: 'POST',
			headers: {
				'X-WP-Nonce': suremails.nonce,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( payload ),
		} );

		return response;
	} catch ( error ) {
		throw new Error(
			error.message ||
				__( 'There was an issue testing the connection.', 'suremails' )
		);
	}
};

/**
 * Sends a test email using a specified connection.
 *
 * @param {Object}  payload            - The payload containing email details.
 * @param {string}  payload.from_email - The sender's email address.
 * @param {string}  payload.to_email   - The recipient's email address.
 * @param {string}  payload.type       - The type of connection.
 * @param {boolean} payload.is_html    - Whether to send the email in HTML format.
 * @param {string}  payload.id         - The ID of the connection.
 * @return {Promise<Object>} The response from the server.
 */
export const sendTestEmail = async ( payload ) => {
	if (
		! payload ||
		! payload.from_email ||
		! payload.to_email ||
		! payload.type ||
		! payload.id
	) {
		throw new Error(
			__( 'Incomplete data for sending test email.', 'suremails' )
		);
	}

	try {
		const response = await apiFetch( {
			path: '/suremails/v1/send-test-email',
			method: 'POST',
			headers: {
				'X-WP-Nonce': suremails.nonce,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( payload ),
		} );

		return response;
	} catch ( error ) {
		throw new Error(
			error.message ||
				__( 'There was an issue sending the test email.', 'suremails' )
		);
	}
};
