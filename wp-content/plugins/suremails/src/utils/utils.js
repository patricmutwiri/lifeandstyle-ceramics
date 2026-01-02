import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import { format as format_date } from 'date-fns';
import { twMerge } from 'tailwind-merge';
import DOMPurify from 'dompurify';

/**
 * Formats a given date string based on the provided options.
 *
 * @param {string}  dateString       - The date string to format.
 * @param {Object}  options          - Formatting options to customize the output.
 * @param {boolean} [options.day]    - Whether to include the day in the output.
 * @param {boolean} [options.month]  - Whether to include the month in the output.
 * @param {boolean} [options.year]   - Whether to include the year in the output.
 * @param {boolean} [options.hour]   - Whether to include the hour in the output.
 * @param {boolean} [options.minute] - Whether to include the minute in the output.
 * @param {boolean} [options.hour12] - Whether to use a 12-hour clock format.
 * @return {string} - The formatted date string or a fallback if the input is invalid.
 */
export const formatDate = ( dateString, options = {} ) => {
	if ( ! dateString || isNaN( new Date( dateString ).getTime() ) ) {
		return __( 'No Date', 'suremails' );
	}

	const optionMap = {
		day: '2-digit',
		month: 'short',
		year: 'numeric',
		hour: '2-digit',
		minute: '2-digit',
		hour12: true, // Note: hour12 is a boolean directly
	};

	const formattingOptions = Object.keys( optionMap ).reduce( ( acc, key ) => {
		if ( options[ key ] === true ) {
			acc[ key ] = optionMap[ key ];
		} else if ( options[ key ] === false ) {
		} else if ( options[ key ] !== undefined ) {
			acc[ key ] = options[ key ];
		}
		return acc;
	}, {} );

	return new Intl.DateTimeFormat( 'en-US', formattingOptions ).format(
		new Date( dateString )
	);
};

/**
 *
 * @return {string} - The formatted date string.
 */

export const getDatePlaceholder = () => {
	const currentDate = new Date();
	const pastDate = new Date();
	pastDate.setDate( currentDate.getDate() - 30 ); // Set to 30 days ago

	const formattedPastDate = format( pastDate, 'MM/dd/yyyy' );
	const formattedCurrentDate = format( currentDate, 'MM/dd/yyyy' );

	return `${ formattedPastDate } - ${ formattedCurrentDate }`;
};

/**
 *
 * @return {string} - The formatted date string.
 */

export const getLastNDays = ( days ) => {
	if ( isNaN( days ) ) {
		return {
			from: null,
			to: null,
		};
	}
	const currentDate = new Date();
	const pastDate = new Date();
	pastDate.setDate( currentDate.getDate() - days ); // Set to 30 days ago

	return {
		from: pastDate,
		to: currentDate,
	};
};

/**
 * Returns selected date in string format.
 *
 * @param {Object} selectedDates - Object containing `from` and `to` Date objects.
 * @return {string} - Formatted string.
 */
export const getSelectedDate = ( selectedDates ) => {
	if ( ! selectedDates.from || isNaN( selectedDates.from.getTime() ) ) {
		return '';
	}
	if ( ! selectedDates.to || isNaN( selectedDates.to.getTime() ) ) {
		return format( selectedDates.from, 'MM/dd/yyyy' );
	}
	return `${ format( selectedDates.from, 'MM/dd/yyyy' ) } - ${ format(
		selectedDates.to,
		'MM/dd/yyyy'
	) }`;
};

/**
 * Utility function to sort an array of objects based on a specified key.
 *
 * @param {Array}  data      - The array of objects to sort.
 * @param {string} key       - The key in the objects to sort by.
 * @param {string} direction - Sort direction: 'asc' for ascending, 'desc' for descending.
 * @return {Array} - The sorted array of objects.
 */
export const sortData = ( data, key, direction = 'asc' ) => {
	if ( ! Array.isArray( data ) || ! key ) {
		return data; // Return data as is if invalid
	}

	const sortedData = [ ...data ].sort( ( a, b ) => {
		const valueA =
			new Date( a[ key ] ) instanceof Date &&
			! isNaN( new Date( a[ key ] ).getTime() )
				? new Date( a[ key ] )
				: a[ key ];
		const valueB =
			new Date( b[ key ] ) instanceof Date &&
			! isNaN( new Date( b[ key ] ).getTime() )
				? new Date( b[ key ] )
				: b[ key ];

		if ( valueA < valueB ) {
			return direction === 'asc' ? -1 : 1;
		}
		if ( valueA > valueB ) {
			return direction === 'asc' ? 1 : -1;
		}
		return 0;
	} );

	return sortedData;
};

/**
 * Formats a given date string based on the provided options.
 * If no options are provided, it defaults to 'yyyy-MM-dd' format.
 *
 * @param {string|Date} date                      - The date string or Date object to format.
 * @param {string}      [dateFormat='yyyy-MM-dd'] - The date format string for `date-fns`.
 * @return {string} - The formatted date string or a fallback if the input is invalid.
 */
export const format = ( date, dateFormat = 'yyyy-MM-dd' ) => {
	try {
		if ( ! date || isNaN( new Date( date ).getTime() ) ) {
			throw new Error( __( 'Invalid Date', 'suremails' ) );
		}
		return format_date( new Date( date ), dateFormat );
	} catch ( error ) {
		return __( 'No Date', 'suremails' );
	}
};

/**
 * Parses headers provided either as a raw string or an array of strings into a structured object.
 *
 * Each header line is expected to be in the format "Header-Name: header value".
 * This function handles:
 *   - Numerical prefixes (e.g., "0: From: ...")
 *   - Multiple header values for the same header name.
 *
 * @param {string | string[]} headersInput - The raw headers string or an array of header strings.
 * @return {Object} - An object where the keys are normalized header names and the values are arrays of header values.
 */
export const parseHeaders = ( headersInput ) => {
	const headers = {};

	let headerLines = [];
	if ( Array.isArray( headersInput ) ) {
		headerLines = headersInput;
	} else if ( typeof headersInput === 'string' ) {
		headerLines = headersInput.split( /\r?\n/ );
	} else {
		return headers;
	}

	headerLines.forEach( ( line ) => {
		if ( ! line.trim() ) {
			return;
		}

		// Remove a leading numerical prefix if present (e.g., "0: From: ...").
		const prefixMatch = line.match( /^\d+:\s*(.*)$/ );
		const cleanedLine = prefixMatch ? prefixMatch[ 1 ] : line;

		// Find the first colon (:) that separates the header name from its value.
		const separatorIndex = cleanedLine.indexOf( ':' );
		if ( separatorIndex === -1 ) {
			return;
		}

		// Extract and trim the header name.
		const name = cleanedLine.slice( 0, separatorIndex ).trim();
		if ( ! name ) {
			return;
		}

		const value = cleanedLine.slice( separatorIndex + 1 ).trim();

		const normalizedName = normalizeHeaderName( name );

		// Initialize the header's value array if it doesn't exist.
		if ( ! headers[ normalizedName ] ) {
			headers[ normalizedName ] = [];
		}

		headers[ normalizedName ].push( value );
	} );

	return headers;
};

/**
 * Normalizes header names to a standard format.
 *
 * E.g., 'reply-to' => 'Reply-To'
 *
 * @param {string} name - The header name to normalize.
 * @return {string} - The normalized header name.
 */
const normalizeHeaderName = ( name ) => {
	return name
		.split( '-' )
		.map(
			( word ) =>
				word.charAt( 0 ).toUpperCase() + word.slice( 1 ).toLowerCase()
		)
		.join( '-' );
};

/**
 * Utility function to merge Tailwind CSS and conditional class names.
 *
 * @param {...any} args
 * @return {string} - The concatenated class string.
 */
export const cn = ( ...args ) => twMerge( clsx( ...args ) );

/**
 * Generates a range of page numbers and ellipses for pagination.
 *
 * @param {number} currentPage  - The current active page.
 * @param {number} totalPages   - The total number of pages.
 * @param {number} siblingCount - Number of pages to show on each side of the current page.
 * @return {Array} An array containing page numbers and 'ellipsis' strings.
 */
export const getPaginationRange = (
	currentPage,
	totalPages,
	siblingCount = 1
) => {
	// Calculate common values
	const siblingFactor = siblingCount * 2; // Sibling count multiplied by 2
	const totalPageNumbers = siblingFactor + 5; // Total numbers including ellipses and edges

	if ( totalPageNumbers >= totalPages ) {
		// If all pages can fit within the range
		return Array.from( { length: totalPages }, ( _, i ) => i + 1 );
	}

	// Calculate indices
	const leftSiblingIndex = Math.max( currentPage - siblingCount, 1 ); // Left sibling index
	const rightSiblingIndex = Math.min(
		currentPage + siblingCount,
		totalPages
	);

	const showLeftEllipsis = leftSiblingIndex > 2;
	const showRightEllipsis = rightSiblingIndex < totalPages - 1;

	// Constants for the first and last pages
	const firstPage = 1;
	const lastPage = totalPages;

	const pages = [];

	if ( ! showLeftEllipsis && showRightEllipsis ) {
		// Calculate range for the left side
		const leftItemCount = 3 + siblingFactor; // Number of items on the left
		const leftRange = Array.from(
			{ length: leftItemCount },
			( _, i ) => i + 1
		);
		pages.push( ...leftRange, 'ellipsis', lastPage );
	} else if ( showLeftEllipsis && ! showRightEllipsis ) {
		// Calculate range for the right side
		const rightItemCount = 3 + siblingFactor; // Number of items on the right
		const rightRange = Array.from(
			{ length: rightItemCount },
			( _, i ) => totalPages - rightItemCount + i + 1
		);
		pages.push( firstPage, 'ellipsis', ...rightRange );
	} else if ( showLeftEllipsis && showRightEllipsis ) {
		// Calculate middle range
		const middleRange = Array.from(
			{ length: siblingFactor + 1 },
			( _, i ) => currentPage - siblingCount + i
		);
		pages.push(
			firstPage,
			'ellipsis',
			...middleRange,
			'ellipsis',
			lastPage
		);
	}

	return pages;
};

/**
 * Get the label for the log status
 *
 * @param {string} status   - The status of the log
 * @param {Array}  response - Array of response objects.
 * @return {string} - The label for the status
 */
export const getStatusLabel = ( status, response ) => {
	const simulated = isResponseSimulated( response );

	if ( simulated ) {
		return __( 'Simulated', 'suremails' );
	}
	switch ( status ) {
		case 'sent':
			return __( 'Successful', 'suremails' );
		case 'failed':
			return __( 'Failed', 'suremails' );
		case 'pending':
			return __( 'In Progress', 'suremails' );
		case 'blocked':
			return __( 'Blocked', 'suremails' );
		default:
			return __( 'Unknown', 'suremails' );
	}
};

/**
 * Get the variant for the log status badge
 *
 * @param {string} status   - The status of the log
 * @param {Array}  response - Array of response objects.
 * @return {string} - The variant for the badge
 */
export const getStatusVariant = ( status, response ) => {
	const simulated = isResponseSimulated( response );

	if ( simulated ) {
		return 'yellow';
	}
	switch ( status ) {
		case 'sent':
			return 'green';
		case 'failed':
			return 'red';
		case 'pending':
			return 'yellow';
		case 'blocked':
			return 'red';
		default:
			return 'gray'; // Fallback color for unknown statuses
	}
};

/**
 * Determines if the response indicates a simulated log.
 *
 * It finds the response element with the highest "retry" value and returns its "simulated" flag.
 *
 * @param {Array} response - Array of response objects.
 * @return {boolean} - True if the element with the highest retry has simulated set to true, false otherwise.
 */
const isResponseSimulated = ( response ) => {
	if ( ! Array.isArray( response ) || response.length === 0 ) {
		return false;
	}
	// Find the response object with the maximum "retry" value.
	const maxRetryEntry = response.reduce( ( prev, curr ) =>
		curr.retry >= prev.retry ? curr : prev
	);
	return maxRetryEntry.simulated;
};

/**
 * A utility class to manipulate shadow DOM elements.
 */
export class ShadowDOM {
	element = null;
	shadowRoot = null;
	mode = 'open';

	/**
	 * Constructor for the ShadowDOM class.
	 *
	 * @param {HTMLElement} element - The element to attach the shadow DOM to.
	 * @param {string}      mode    - The mode of the shadow root: 'open' or 'closed'.
	 */
	constructor( element, mode = 'open' ) {
		this.element = element;
		this.mode = mode;
		this.shadowRoot = this.attachShadow( mode );
	}

	/**
	 * Update the element to attach the shadow DOM to.
	 *
	 * @param {HTMLElement} element - The element to attach the shadow DOM to.
	 */
	updateElement( element ) {
		this.element = element;
	}

	/**
	 * Check if the element has a shadow root.
	 *
	 * @return {boolean} - Whether the element has a shadow root.
	 */
	hasShadowRoot() {
		if ( ! this.element ) {
			return false;
		}
		if ( this.mode === 'closed' ) {
			return this.shadowRoot !== null;
		}
		return this.element.shadowRoot !== null;
	}

	/**
	 * Attach a shadow root to the element.
	 *
	 * @param {string} mode - The mode of the shadow root: 'open' or 'closed'.
	 * @return {ShadowRoot} - The shadow root.
	 */
	attachShadow( mode = 'open' ) {
		if ( this.hasShadowRoot() ) {
			return this.element.shadowRoot;
		}
		return this.element.attachShadow( { mode } );
	}

	/**
	 * Append a child to the shadow DOM.
	 *
	 * @param {HTMLElement} child - The child element to append.
	 * @return {HTMLElement} - The appended child element.
	 */
	appendChild( child ) {
		if ( ! this.hasShadowRoot() ) {
			return;
		}
		if ( this.mode === 'closed' ) {
			return this.shadowRoot.appendChild( child );
		}
		return this.element.shadowRoot.appendChild( child );
	}

	/**
	 * Check if the shadow DOM has child nodes.
	 *
	 * @return {boolean} - Whether the shadow DOM has child nodes.
	 */
	hasChildNodes() {
		if ( ! this.hasShadowRoot() ) {
			return false;
		}
		if ( this.mode === 'closed' ) {
			return this.shadowRoot.hasChildNodes();
		}
		return this.element.shadowRoot.hasChildNodes();
	}

	/**
	 * Set the inner HTML of the shadow DOM.
	 *
	 * @param {string} content - The content to set as the inner HTML.
	 */
	innerHTML( content ) {
		if ( ! this.hasShadowRoot() ) {
			return;
		}
		if ( this.mode === 'closed' ) {
			this.shadowRoot.innerHTML = content;
			return;
		}
		this.element.shadowRoot.innerHTML = content;
	}
}

/**
 * Check if the string contains an HTML tag
 *
 * @param {string} str - The string to check
 * @return {boolean} - Whether the string contains an HTML tag
 */
export const containsHtmlTag = ( str ) => {
	return /<[^>]*>/.test( str );
};

/**
 * Converts newlines to <br/> tags
 *
 * @param {string}  str      - The string to convert
 * @param {boolean} is_xhtml - Whether to use XHTML compatible tags
 * @return {string} - The converted string
 */
const nl2br = ( str, is_xhtml = false ) => {
	if ( typeof str === 'undefined' || str === null ) {
		return '';
	}
	const breakTag = is_xhtml ? '<br />' : '<br>';
	return ( str + '' ).replace(
		/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g,
		'$1' + breakTag + '$2'
	);
};

/**
 * Converts plain text to HTML with proper formatting.
 * - Converts newlines to <br/> tags
 * - Converts URLs to clickable links
 * - Sanitizes output to prevent XSS
 *
 * @param {string} text - The plain text to convert
 * @return {string} - Sanitized HTML string
 */
export const stringToHtml = ( text ) => {
	if ( ! text ) {
		return '';
	}
	let parsedText = text;

	// Check if string contains an HTML tag
	const hasHtmlTag = containsHtmlTag( text );

	// If the text/string is not HTML, convert it to HTML
	if ( ! hasHtmlTag ) {
		// Convert URLs to clickable links
		const urlRegex = /(https?:\/\/[^\s]+)/g;
		parsedText = parsedText.replace(
			urlRegex,
			'<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
		);

		// Convert newlines to <br/> tags
		parsedText = nl2br( parsedText.trim() );
	}

	// For security, override target and rel attributes to links
	DOMPurify.addHook( 'afterSanitizeAttributes', function ( node ) {
		// set all elements owning target to target=_blank and rel=noopener noreferrer
		if ( 'target' in node ) {
			node.setAttribute( 'target', '_blank' );
			node.setAttribute( 'rel', 'noopener noreferrer' );
		}
	} );

	// Sanitize the final HTML
	return DOMPurify.sanitize( parsedText );
};

/**
 * Get the query params from the URL
 *
 * @return {Object} - The query params
 */
export const getQueryParams = () => {
	try {
		const url = new URL( window.location.href );
		return url.searchParams;
	} catch ( error ) {
		return null;
	}
};

/**
 * Check if the query param is in the URL
 *
 * @param {string} param - The query param to check
 * @return {boolean} - Whether the query param is in the URL
 */
export const hasInQueryParams = ( param ) => {
	try {
		const queryParams = getQueryParams();
		return queryParams?.has( param );
	} catch ( error ) {
		return false;
	}
};

/**
 * Remove the query param from the URL and replace the URL
 *
 * @param {string} param - The query param to remove
 * @return {boolean} - Whether the query param was removed
 */
export const removeQueryParam = ( param ) => {
	try {
		const url = new URL( window.location.href );
		const queryParams = url.searchParams;
		queryParams.delete( param );

		// Construct new URL with original path and hash
		const newUrl = `${ url.origin }${ url.pathname }`;
		const searchString = queryParams.toString();
		const finalUrl = searchString
			? `${ newUrl }?${ searchString }`
			: newUrl;

		// Append hash if it exists
		const urlWithHash = url.hash ? `${ finalUrl }${ url.hash }` : finalUrl;

		window.history.replaceState( null, '', urlWithHash );
	} catch ( error ) {
		return false;
	}
};

/**
 * Convert a Connection string from UTC to the browser's local time.
 * Expects a string in the format: "Used {connection_title}, at {utcTimestamp}"
 *
 * @param {string} connectionStr
 */
export const convertUTCConnection = ( connectionStr ) => {
	const lastAtIndex = connectionStr.lastIndexOf( ', at ' );
	if ( lastAtIndex === -1 ) {
		return connectionStr;
	}

	const utcTimestamp = connectionStr.substring( lastAtIndex + 5 ).trim();
	const utcDate = new Date( utcTimestamp + ' UTC' );

	if ( isNaN( utcDate.getTime() ) ) {
		return connectionStr;
	}

	const localTimestamp = utcDate.toLocaleString( 'en-US', {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
		hour: 'numeric',
		minute: '2-digit',
		hour12: true,
	} );

	return (
		connectionStr.substring( 0, lastAtIndex ) + `, at ${ localTimestamp }`
	);
};

export const get_connection_message = ( connection_title, timeStamp ) => {
	const formattedTimeStamp = formatDate( timeStamp, {
		day: true,
		month: true,
		year: true,
		hour: true,
		minute: true,
		hour12: true,
	} );
	return `Used ${ connection_title }, at ${ formattedTimeStamp }`;
};

/**
 * Check if the status is pending or not
 *
 * @param {string} status - The status of the log
 * @return {boolean} - Whether the status is pending
 */
export const get_pending_status = ( status ) => {
	if ( status && status === 'pending' ) {
		return true;
	}
	return false;
};
