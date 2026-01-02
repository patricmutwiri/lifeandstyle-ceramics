import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { TERM_SEO_DATA_URL, POST_SEO_DATA_URL } from '@Global/constants/api';
import { isCurrentPage } from '@/functions/utils';

const API_BASE_URL = '/surerank/v1';

export const fetchMetaSettings = async () => {
	const queryParams = new URLSearchParams();
	if ( isCurrentPage( 'term.php' ) ) {
		queryParams.append( 'term_id', surerank_seo_popup?.term_id );
	} else {
		queryParams.append( 'post_id', surerank_seo_popup?.post_id );
	}
	queryParams.append( 'post_type', surerank_seo_popup?.post_type );
	queryParams.append( 'is_taxonomy', surerank_seo_popup?.is_taxonomy );

	try {
		const response = await apiFetch( {
			path: `${
				isCurrentPage( 'term.php' )
					? TERM_SEO_DATA_URL
					: POST_SEO_DATA_URL
			}?${ queryParams.toString() }`,
			method: 'GET',
		} );
		if ( ! response.success ) {
			throw new Error( response.message );
		}
		return response;
	} catch ( error ) {
		throw new Error( error.message );
	}
};

/**
 * Fetch image data by URL.
 *
 * @param {string} imageUrl The URL of the image to fetch.
 * @return {Promise} A promise that resolves to the image data.
 */
export const fetchImageDataByUrl = async ( imageUrl ) => {
	if ( ! imageUrl ) {
		return null;
	}

	// Extract filename variations for better search
	const url = new URL( imageUrl );
	const pathname = url.pathname;
	const filename = pathname.split( '/' ).pop().split( '?' )[ 0 ];

	// Remove common optimization suffixes (webp, scaled, etc.)
	const baseFilename = filename
		.replace( /-\d+x\d+\.(jpg|jpeg|png|gif|webp)$/i, '' ) // Remove dimension suffixes
		.replace( /-scaled\.(jpg|jpeg|png|gif|webp)$/i, '' ) // Remove -scaled suffix
		.replace( /\.(webp)$/i, '' ) // Remove .webp extension
		.replace( /(-optimized|-compressed)/i, '' ); // Remove optimization keywords

	// Try multiple search strategies
	const searchStrategies = [
		// 1. Search by exact filename
		{ search: filename },
		// 2. Search by base filename without optimization suffixes
		{ search: baseFilename },
		// 3. Search by filename without extension
		{ search: filename.replace( /\.[^/.]+$/, '' ) },
		// 4. Search by base filename without extension
		{ search: baseFilename.replace( /\.[^/.]+$/, '' ) },
	];

	for ( const strategy of searchStrategies ) {
		try {
			const response = await apiFetch( {
				path: addQueryArgs( '/wp/v2/media', {
					search: strategy.search,
					media_type: 'image',
					slug: strategy.search, // Use slug for better matching
					per_page: 20, // Increase results to find better matches
				} ),
				method: 'GET',
			} );

			if ( response && response.length > 0 ) {
				// Try to find exact match first
				const exactMatch = response.find( ( media ) => {
					const mediaUrl = media.source_url || media.url;
					const mediaFilename = mediaUrl
						.split( '/' )
						.pop()
						.split( '?' )[ 0 ];
					return (
						mediaFilename === filename ||
						mediaUrl.includes( baseFilename ) ||
						mediaFilename.includes( baseFilename )
					);
				} );

				if ( exactMatch ) {
					// eslint-disable-next-line no-console
					console.log(
						`Search strategy: ${ strategy.search }`,
						response
					);
					return exactMatch;
				}

				// Return first result if no exact match
				return response[ 0 ];
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.warn(
				`Search strategy failed for: ${ strategy.search }`,
				error
			);
			continue;
		}
	}

	// If all strategies fail, try a broader search using just the base name
	try {
		const broadSearch = baseFilename.split( '-' )[ 0 ]; // Get first part before any dashes
		const response = await apiFetch( {
			path: addQueryArgs( '/wp/v2/media', {
				search: broadSearch,
				media_type: 'image',
				per_page: 50,
			} ),
			method: 'GET',
		} );

		if ( response && response.length > 0 ) {
			return response[ 0 ];
		}
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.warn( 'Broad search failed', error );
	}

	return null;
};

/**
 * Get migrated data for onboarding steps after migration done successfully.
 *
 * @return {Promise<Object>} A promise that resolves to the migrated data.
 */
export const getMigratedData = () => {
	return apiFetch( {
		path: `${ API_BASE_URL }/migration/migrated-data`,
		method: 'GET',
	} );
};

/**
 * Fetch AI authentication status
 * @return {Promise<Object>} The authentication status
 */
export const getAuth = () => {
	return apiFetch( { path: `${ API_BASE_URL }/ai/auth` } );
};

/**
 * Save AI access token
 * @param {string} accessKey The access token
 * @return {Promise<Object>} The response from the API
 */
export const saveAuthAccessToken = ( accessKey ) => {
	return apiFetch( {
		path: `${ API_BASE_URL }/ai/auth`,
		method: 'POST',
		data: { accessKey },
	} );
};

/**
 * Generate content
 * @param {string} type         - The type of content to generate.
 * @param {string} [postId]     - The optional post ID.
 * @param {string} [isTaxonomy] - The optional taxonomy flag.
 */
export const generateContent = ( type, postId, isTaxonomy ) => {
	const data = { type };
	if ( postId ) {
		data.post_id = postId;
	}
	if ( isTaxonomy ) {
		data.is_taxonomy = isTaxonomy;
	}
	return apiFetch( {
		path: `${ API_BASE_URL }/generate-content`,
		method: 'POST',
		data,
	} );
};

/**
 * Save email reports settings
 * @param {Object}  settings                - The email reports settings to save.
 * @param {boolean} settings.enabled        - Whether email reports are enabled.
 * @param {string}  settings.recipientEmail - The recipient email address.
 * @param {string}  settings.dayOfWeek      - The day of the week for reports.
 */
export const saveEmailReportsSettings = ( settings ) => {
	return apiFetch( {
		path: `${ API_BASE_URL }/email-reports/settings`,
		method: 'POST',
		data: settings,
	} );
};

/**
 * Get email reports settings
 * @return {Promise<Object>} The email reports settings.
 */
export const getEmailReportsSettings = () => {
	return apiFetch( {
		path: `${ API_BASE_URL }/email-reports/settings`,
		method: 'GET',
	} );
};

/**
 * Send test email report
 * @param {string} recipientEmail - The recipient email address.
 * @return {Promise<Object>} The response from the API.
 */
export const sendTestEmailReport = ( recipientEmail ) => {
	return apiFetch( {
		path: `${ API_BASE_URL }/email-reports/send-test`,
		method: 'POST',
		data: { recipientEmail },
	} );
};
