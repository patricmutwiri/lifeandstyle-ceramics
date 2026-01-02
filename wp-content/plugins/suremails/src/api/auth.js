import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

export const get_auth_url = async (
	provider,
	client_id,
	client_secret,
	redirect_url
) => {
	try {
		const response = await apiFetch( {
			path: '/suremails/v1/get-auth-url',
			method: 'POST',
			headers: {
				'X-WP-Nonce': suremails.nonce,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				provider,
				client_id,
				client_secret,
				redirect_url,
			} ),
		} );
		return response;
	} catch ( error ) {
		throw new Error(
			error.message ||
				__( 'There was an issue getting the auth URL.', 'suremails' )
		);
	}
};
