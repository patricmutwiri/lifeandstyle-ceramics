import { __ } from '@wordpress/i18n';
import { createCheck } from './content-checks';
import apiFetch from '@wordpress/api-fetch';

const cacheBrokenLinksResults = new Map();

/**
 * Get broken links from cache.
 * @param {string[]} links
 * @return {string[]} Array of broken links
 */
const getCacheBrokenLinks = ( links ) => {
	if ( ! links?.length ) {
		return [];
	}

	const result = [];
	for ( const url of links ) {
		const cachedResult = cacheBrokenLinksResults.get( url );
		if ( cachedResult?.broken ) {
			result.push( {
				url,
				...cachedResult,
			} );
		}
	}
	return result;
};

const updateCache = ( links ) => {
	if ( ! links?.length ) {
		return cacheBrokenLinksResults.clear();
	}
	// Remove links from cache if they are not in the links array.
	Array.from( cacheBrokenLinksResults.keys() ).forEach( ( url ) => {
		if ( ! links.includes( url ) ) {
			cacheBrokenLinksResults.delete( url );
		}
	} );
};

/**
 * Determine whether a URL should be skipped. We use an inclusive check:
 * - Allow absolute http/https URLs
 * - Allow relative URLs (no scheme)
 * - Skip everything else (mailto:, tel:, sms:, geo:, javascript:, data:, etc.)
 *
 * @param {string} href Raw href value
 * @return {boolean} True if the URL should be skipped
 */
const shouldSkipUrl = ( href ) => {
	if ( ! href ) {
		return true;
	}

	const trimmed = href.trim();
	if ( trimmed === '' ) {
		return true;
	}

	// Anchors
	if ( trimmed.startsWith( '#' ) ) {
		return true;
	}

	try {
		// If it's an absolute URL, the constructor will succeed. If it has a scheme
		// like mailto:, URL will still be constructed but protocol will reflect that.
		const url = new URL( trimmed, surerank_globals.site_url );
		const protocol = ( url.protocol || '' )
			.replace( ':', '' )
			.toLowerCase();
		return ! ( protocol === 'http' || protocol === 'https' );
	} catch ( error ) {
		// If URL parsing fails, treat as relative (allow) if it doesn't contain a colon
		// early on (which would indicate a scheme). Otherwise skip.
		return trimmed.includes( ':' );
	}
};

/**
 * Get all unique links (href/src) from <a> and <img> tags in the document.
 * @param {Document} document
 * @return {string[]} Array of unique URLs
 */
export const getAllLinks = ( document ) => {
	if ( ! document ) {
		return [];
	}

	const linkElements = Array.from( document.querySelectorAll( 'a[href]' ) );
	const urls = linkElements
		.map( ( element ) => {
			const href = element.getAttribute( 'href' );
			if ( ! href ) {
				return null;
			}

			const trimmed = href.trim();
			// Skip anchors, javascript and various schemes we don't want to check.
			if ( shouldSkipUrl( trimmed ) ) {
				return null;
			}

			// Append base URL if the URL is relative
			if ( ! trimmed.startsWith( 'http' ) ) {
				const baseUrl =
					( trimmed.startsWith( '/' ) ? '' : '/' ) + trimmed;
				return `${ surerank_globals.site_url }${ baseUrl }`;
			}

			return trimmed;
		} )
		.filter( Boolean ); // Remove null/undefined values

	// Return unique URLs
	return [ ...new Set( urls ) ];
};

/**
 * Check links for broken status via API and save results.
 * @param {Object}   params
 * @param {string[]} params.links
 * @param {number}   params.postId
 * @param {string}   params.userAgent
 * @param {Function} params.onProgress
 * @return {Promise<string[]>} Array of broken links
 */
export const checkLinks = async ( {
	links,
	postId,
	userAgent,
	onProgress,
} ) => {
	// Update cache before checking links.
	updateCache( links );

	if ( ! links.length ) {
		return [];
	}

	const uniqueLinks = links.filter(
		( url ) => ! cacheBrokenLinksResults.has( url )
	);
	const resultFromCache = getCacheBrokenLinks( links );

	if ( uniqueLinks.length === 0 ) {
		return resultFromCache;
	}

	const total = links.length;
	let current = links.length - uniqueLinks.length;

	if ( typeof onProgress === 'function' ) {
		onProgress( 'isCheckingLinks', true );
		onProgress( 'linkCheckProgress', {
			current,
			total,
		} );
	}

	// Process links sequentially to avoid overwhelming servers
	for ( const url of uniqueLinks ) {
		try {
			const result = await fetchBrokenLinkStatus( {
				postId,
				userAgent,
				url,
				allLinks: links,
			} );
			const { success, ...rest } = result;
			cacheBrokenLinksResults.set( url, {
				broken: ! success,
				...rest,
			} );
		} catch ( error ) {
			// If API fails, consider as broken
			cacheBrokenLinksResults.set( url, {
				broken: true,
				status: error?.data?.status ?? error?.code ?? 'error',
				details: error.message,
				message: __( 'Failed to check link', 'surerank' ),
			} );
		}
		current++;
		if ( typeof onProgress === 'function' ) {
			onProgress( 'linkCheckProgress', {
				current,
				total,
			} );
		}

		// Add small delay between requests to prevent rate limiting
		if ( current < total ) {
			await new Promise( ( resolve ) => setTimeout( resolve, 100 ) );
		}
	}
	onProgress( 'isCheckingLinks', false );

	return getCacheBrokenLinks( links );
};

/**
 * Check for broken links in the document and report results.
 * @param {Document} document
 * @param {number}   postId
 * @param {string}   userAgent
 * @param {Function} onProgress
 * @return {Promise<Object>} createCheck result
 */
export const checkBrokenLinks = async (
	document,
	postId,
	userAgent = window.navigator.userAgent,
	onProgress
) => {
	if ( ! document || ! postId ) {
		return;
	}

	const links = getAllLinks( document );
	if ( ! links.length ) {
		return;
	}

	const brokenLinks = await checkLinks( {
		links,
		postId,
		userAgent,
		onProgress,
	} );

	if ( brokenLinks.length ) {
		return createCheck( {
			id: 'broken_links',
			title: __(
				'One or more broken links found on the page.',
				'surerank'
			),
			status: 'error',
			data: brokenLinks,
			type: 'page',
		} );
	}

	return createCheck( {
		id: 'broken_links',
		title: __( 'No broken links found on the page.', 'surerank' ),
		status: 'success',
		description: [],
		type: 'page',
	} );
};

export const checkCanonicalUrl = ( canonical ) => {
	if ( ! canonical ) {
		return createCheck( {
			id: 'canonical_url',
			title: __(
				'Canonical tag is not present on the page.',
				'surerank'
			),
			status: 'warning',
			type: 'page',
		} );
	}

	return createCheck( {
		id: 'canonical_url',
		title: __( 'Canonical tag is present on the page.', 'surerank' ),
		status: 'success',
		type: 'page',
	} );
};

/**
 * Fetch broken link status from the API.
 *
 * @param {Object}   params
 * @param {number}   params.postId
 * @param {string}   params.userAgent
 * @param {string}   params.url
 * @param {string[]} params.allLinks
 * @return {Promise<Object>} API response
 */
export const fetchBrokenLinkStatus = async ( {
	postId,
	userAgent,
	url,
	allLinks,
} ) => {
	return await apiFetch( {
		path: '/surerank/v1/checks/broken-link',
		method: 'POST',
		data: {
			post_id: postId,
			user_agent: userAgent,
			url,
			urls: allLinks,
		},
	} );
};
