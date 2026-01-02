// @api/logs.js
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { format } from '@utils/utils';

/**
 * Fetches email logs from the server.
 *
 * @param {Object}    params             - The parameters for fetching logs.
 * @param {number}    params.pageNumber  - The current page number.
 * @param {Date|null} params.startDate   - The start date for filtering.
 * @param {Date|null} params.endDate     - The end date for filtering.
 * @param {string}    params.filter      - The status filter ('sent' or 'failed').
 * @param {string}    params.searchTerm  - The search term.
 * @param {number}    params.logsPerPage - Number of logs per page.
 * @return {Promise<Object>} The response containing logs and total count.
 */
export const fetchLogs = async ( {
	pageNumber = 1,
	startDate = null,
	endDate = null,
	filter = '',
	searchTerm = '',
	logsPerPage = 10,
} ) => {
	try {
		const response = await apiFetch( {
			path: `/suremails/v1/email-logs`,
			method: 'POST',
			headers: {
				'X-WP-Nonce': suremails.nonce,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				page: pageNumber,
				per_page: logsPerPage,
				start_date: startDate
					? format( new Date( startDate ), 'yyyy-MM-dd' )
					: null,
				end_date: endDate
					? format( new Date( endDate ), 'yyyy-MM-dd' )
					: null,
				filter: filter.toLowerCase() || null,
				search: searchTerm || null,
			} ),
		} );

		return response;
	} catch ( error ) {
		throw new Error(
			error.message ||
				__( 'There was an issue fetching logs.', 'suremails' )
		);
	}
};

/**
 * Deletes specified email logs.
 *
 * @param {Array<number>} logIds - Array of log IDs to delete.
 * @return {Promise<Object>} The response from the server.
 */
export const deleteLogs = async ( logIds ) => {
	if ( ! logIds || logIds.length === 0 ) {
		throw new Error( __( 'No logs selected for deletion.', 'suremails' ) );
	}

	try {
		const response = await apiFetch( {
			path: '/suremails/v1/delete-logs',
			method: 'POST',
			headers: {
				'X-WP-Nonce': suremails.nonce,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				log_ids: logIds,
			} ),
		} );

		return response;
	} catch ( error ) {
		throw new Error(
			error.message ||
				__( 'There was an issue deleting logs.', 'suremails' )
		);
	}
};

/**
 * Resends emails for specified log IDs.
 *
 * @param {Array<number>} logIds - Array of log IDs to resend.
 * @return {Promise<Object>} The response from the server.
 */
export const resendEmails = async ( logIds ) => {
	if ( ! logIds || logIds.length === 0 ) {
		throw new Error( __( 'No logs selected for resending.', 'suremails' ) );
	}

	try {
		const response = await apiFetch( {
			path: '/suremails/v1/resend-email',
			method: 'POST',
			headers: {
				'X-WP-Nonce': suremails.nonce,
				'Content-Type': 'application/json',
			},
			body: JSON.stringify( {
				log_ids: logIds,
			} ),
		} );

		return response;
	} catch ( error ) {
		throw new Error(
			error.message ||
				__( 'There was an issue resending the email(s).', 'suremails' )
		);
	}
};
