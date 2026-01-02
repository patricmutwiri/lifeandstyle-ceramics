import { __, _n, sprintf } from '@wordpress/i18n';
import {
	format as format_date,
	startOfDay,
	startOfToday,
	startOfYesterday,
} from 'date-fns';
import clsx from 'clsx';
import { createRoot } from 'react-dom';
import { twMerge } from 'tailwind-merge';
import { CHECK_TYPES } from '@/global/constants';

export const cleanContent = ( postContent ) => {
	// Get first paragraph. tag will be <p>.
	const content = postContent.match( /<p>(.*?)<\/p>/g );

	// If paragraph is found. then get the content.
	if ( content?.length ) {
		return content[ 0 ].replace( /(<([^>]+)>)/gi, '' );
	}

	// Remove all the tags from the content and return it.
	const removedAllTags = postContent.replace( /(<([^>]+)>)/gi, '' );

	// Remove extra spaces.
	const removedExtraSpaces = removedAllTags.replace( /\s+/g, ' ' );

	return removedExtraSpaces;
};

/**
 * TruncateText function truncates the provided text to the specified length.
 * If the length of the text exceeds the specified length, it is truncated and a suffix is appended.
 *
 * @param {string} text           - The text to truncate.
 * @param {number} maxLength      - The maximum length of the truncated text.
 * @param {string} [suffix="..."] - The suffix to append if the text is truncated. Default is three dots.
 * @return {string} The truncated text.
 */
export const truncateText = ( text, maxLength, suffix = '...' ) => {
	// If maxLength is not provided or is less than 0, return the text as it is.
	if ( ! text?.length || ! maxLength || maxLength < 0 ) {
		return text;
	}

	return text.length <= maxLength
		? text
		: text.slice( 0, maxLength ) + suffix;
};

/**
 * Mounts a React component onto a specified DOM element.
 * If the target element doesn't exist, an error will be logged to the console.
 *
 * @param {string}   selector      - The CSS selector for the target DOM element.
 * @param {Function} Component     - The React component to render.
 * @param {number}   [timeout=100] - The delay in milliseconds before rendering the component.
 * @return {void}
 */
export const mountComponent = ( selector, Component, timeout = 100 ) => {
	// Validate selector parameters
	if ( typeof selector !== 'string' || ! selector.trim() ) {
		// eslint-disable-next-line no-console
		console.error( 'Invalid selector provided.' );
		return;
	}

	// Validate Component parameters this should be react component
	if ( ! isReactComponent( Component ) ) {
		// eslint-disable-next-line no-console
		console.error( 'Invalid React component provided.' );
		return;
	}

	// Check if the target element exists in the DOM
	const targetElement = document.querySelector( selector );

	// Log an error if the target element is not found
	if ( ! targetElement ) {
		// eslint-disable-next-line no-console
		console.error(
			`Target element with selector '${ selector }' not found.`
		);
		return;
	}

	// Render the component after a timeout
	setTimeout( () => {
		const root = createRoot( targetElement );
		root.render( Component );
	}, timeout );
};

// Example of checking if a variable is a React component
export const isReactComponent = ( variable ) => {
	// Check by verifying the presence of the `$$typeof` property Symbol(react.element) in the variable.
	return variable && variable?.$$typeof === Symbol.for( 'react.element' );
};

/**
 * Get settings page name based on the main URL.
 *
 * @return {string} - The settings page name.
 */
export const getSettingsPageName = () => {
	const settingsPages = {
		surerank_general: 'general_settings',
		surerank_social: 'social_settings',
		surerank_advanced: 'advanced_settings',
	};

	const urlParams = new URLSearchParams( window.location.search );
	const page = urlParams.get( 'page' );

	return settingsPages[ page ] || 'general_settings';
};

/**
 * Utility function to merge Tailwind CSS and conditional class names.
 * @param {...any} args
 */
export const cn = ( ...args ) => twMerge( clsx( ...args ) );

/**
 * Parse lexical editor value to get the content as a string.
 *
 * @param {Object} valueObj       - The lexical editor value.
 * @param {string} optionValueKey to get the value from the mention node type.
 * @return {string} - The content as a string.
 */
export const editorValueToString = ( valueObj, optionValueKey = 'value' ) => {
	const value = valueObj?.root?.children[ 0 ]?.children;
	if ( ! value || ! value?.length ) {
		return '';
	}

	let stringContent = '';
	value.forEach( ( child ) => {
		switch ( child.type ) {
			case 'text':
				stringContent += child.text;
				break;
			case 'mention':
				stringContent += child.data[ optionValueKey ];
				break;
			case 'linebreak':
				stringContent += '\n';
				break;
			default:
				break;
		}
	} );

	return stringContent;
};

/**
 * Parse the content from string to the lexical editor value (json object).
 *
 * @param {string}   stringContent          - The content as a string.
 * @param {object[]} options                - The options array to replace the mention value.
 * @param {string}   optionValueKey         - The key to get the value from the mention object.
 * @param {Object}   mentionObjectStructure - The mention object to replace the mention object structure.
 *
 * @return {JSON} - The lexical editor value.
 */
export const stringValueToFormatJSON = (
	stringContent,
	options = [],
	optionValueKey = 'value',
	mentionObjectStructure = {
		type: 'mention',
		version: 1,
		data: {},
		size: 'md',
		by: 'label',
	}
) => {
	const initialValue = {
		root: {
			children: [
				{
					children: [],
					direction: null,
					format: '',
					indent: 0,
					type: 'paragraph',
					version: 1,
					textFormat: 0,
					textStyle: '',
				},
			],
			direction: null,
			format: '',
			indent: 0,
			type: 'root',
			version: 1,
		},
	};

	const value = { ...initialValue };
	const content = ( typeof stringContent === 'string' ? stringContent : '' )
		.trim()
		.split( /(\s+|%[\w\-_.]+%)/ ) // Split on spaces or %mention%
		.filter( Boolean );

	content.forEach( ( item ) => {
		if ( item === '\n' ) {
			value.root.children[ 0 ].children.push( {
				type: 'linebreak',
				version: 1,
			} );
		} else if ( item?.startsWith( '%' ) && item?.endsWith( '%' ) ) {
			const option = options?.find(
				( mentionItem ) => mentionItem[ optionValueKey ] === item.trim()
			);
			if ( option ) {
				value.root.children[ 0 ].children.push( {
					...mentionObjectStructure,
					data: { ...option },
				} );
			} else {
				// If option not found, render as plain text (e.g., %custom_field.field_name%)
				value.root.children[ 0 ].children.push( {
					detail: 0,
					format: 0,
					mode: 'normal',
					style: '',
					text: item,
					type: 'text',
					version: 1,
				} );
			}
		} else {
			value.root.children[ 0 ].children.push( {
				detail: 0,
				format: 0,
				mode: 'normal',
				style: '',
				text: item,
				type: 'text',
				version: 1,
			} );
		}
	} );

	return JSON.stringify( value );
};

/**
 * Converts a given URL into a formatted string with a maximum character limit.
 *
 * @param {string} url     - The URL to be converted.
 * @param {number} maxChar - The maximum number of characters allowed in the converted URL.
 * @return {string} The converted URL string.
 *
 * @example
 * // Convert the URL and limit the converted string to 20 characters
 * const converted = urlToBreadcrumbFormat('https://example.com/some/long/path', 20);
 * console.log(converted);
 * // Output: "https://example.com › some › long › ..."
 */
export function urlToBreadcrumbFormat( url, maxChar = 65 ) {
	const urlParts = url.split( '/' );
	const domain = urlParts.slice( 0, 3 ).join( '/' );
	const path = urlParts.slice( 3 ).filter( Boolean ).join( ' › ' );
	let convertedUrl = `${ domain } › ${ path }`;

	if ( convertedUrl.length > maxChar ) {
		convertedUrl = convertedUrl.substring( 0, maxChar - 3 ) + '...';
	}

	return convertedUrl;
}

/**
 * Checks if the current page is the specified page.
 * The parameter can be a string or an array of strings.
 *
 * @param {string|string[]} pages - The page(s) to check against.
 * @return {boolean} - True if the current page is term.php and matches the provided page(s), otherwise false.
 */
export const isCurrentPage = ( pages ) => {
	const currentPage = window.location.pathname;
	if ( Array.isArray( pages ) ) {
		return pages.some( ( page ) => currentPage.includes( page ) );
	}
	return currentPage.includes( pages );
};

/**
 * Deep clones an object.
 *
 * @param {Object} obj - The object to clone.
 * @return {Object} The cloned object.
 */
export const deepClone = ( obj ) => {
	if ( obj === null || typeof obj !== 'object' ) {
		return obj;
	}

	if ( Array.isArray( obj ) ) {
		return obj.map( deepClone );
	}

	return Object.fromEntries(
		Object.entries( obj ).map( ( [ key, value ] ) => [
			key,
			deepClone( value ),
		] )
	);
};

/**
 * Scrolls to an element with highlighting.
 *
 * @param {string} elementId          - The ID of the element to scroll to.
 * @param {Object} options            - The options for the scroll to element.
 * @param {number} options.delay      - The delay in milliseconds before scrolling to the element.
 * @param {number} options.retryDelay - The delay in milliseconds between retries.
 * @param {number} options.maxRetries - The maximum number of retries.
 */
export const scrollToElement = ( elementId, options = {} ) => {
	if ( ! elementId ) {
		return;
	}
	const { delay = 1000, retryDelay = 200, maxRetries = 5 } = options;

	// Function to attempt scroll with retry mechanism
	const attemptScrollToElement = ( retryCount = 0 ) => {
		// Find the target element by ID
		const targetElement = document.getElementById( elementId );

		if ( targetElement ) {
			// Element found, scroll it into view
			setTimeout( () => {
				targetElement.scrollIntoView( {
					behavior: 'smooth',
					block: 'center',
					inline: 'nearest',
				} );
			}, delay );
		} else if ( retryCount < maxRetries ) {
			// Element not found yet, retry after a delay
			setTimeout( () => {
				attemptScrollToElement( retryCount + 1 );
			}, retryDelay );
		}
	};

	// Use requestAnimationFrame for the initial attempt
	window.requestAnimationFrame( () => {
		attemptScrollToElement();
	} );
};

/**
 * Checks if the current query parameter matches the specified value.
 *
 * @param {string} queryParam - The query parameter to check.
 * @param {string} value      - The value to compare against.
 * @return {boolean} - True if the query parameter matches the value, otherwise false.
 */
export const isEqualQueryParamValue = ( queryParam, value ) => {
	try {
		const urlObj = new URL( window.location.href );
		return urlObj.searchParams.get( queryParam ) === value;
	} catch ( error ) {
		return false;
	}
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
			throw new Error( __( 'Invalid Date', 'surerank' ) );
		}
		return format_date( new Date( date ), dateFormat );
	} catch ( error ) {
		return __( 'No Date', 'surerank' );
	}
};

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
		return __( 'No Date', 'surerank' );
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
 * Get the last N days date range
 *
 * @param {number}  days                - The number of days to get the date range for.
 * @param {boolean} [includeToday=true] - Whether to include today in the date range.
 * @return {Object} - The date range.
 */
export const getLastNDays = ( days, includeToday = true ) => {
	if ( isNaN( days ) ) {
		return {
			from: '',
			to: '',
		};
	}

	const endDate = includeToday ? startOfToday() : startOfYesterday();
	let startDate = new Date( endDate );

	// When calculating for N days, we need to go back (N-1) days from the end date
	// to include the end date itself in the count
	startDate.setDate( endDate.getDate() - days );
	startDate = startOfDay( startDate );

	return {
		from: startDate,
		to: endDate,
	};
};

/**
 * Returns selected date in string format
 *
 * @param {*} selectedDates
 * @return {string} - Formatted string.
 */
export const getSelectedDate = ( selectedDates ) => {
	if ( ! selectedDates.from ) {
		return '';
	}
	if ( ! selectedDates.to ) {
		return `${ format( selectedDates.from, 'MM/dd/yyyy' ) }`;
	}
	return `${ format( selectedDates.from, 'MM/dd/yyyy' ) } - ${ format(
		selectedDates.to,
		'MM/dd/yyyy'
	) }`;
};

/**
 * Get the date placeholder
 *
 * @param {number}  days                - The number of days to get the date range for.
 * @param {boolean} [includeToday=true] - Whether to include today in the date range.
 * @return {string} - The formatted date string.
 */
export const getDatePlaceholder = ( days = 30, includeToday = true ) => {
	const endDate = includeToday ? startOfToday() : startOfYesterday();
	let startDate = new Date( endDate );

	// When calculating for N days, we need to go back (N-1) days from the end date
	// to include the end date itself in the count
	startDate.setDate( endDate.getDate() - days );
	startDate = startOfDay( startDate );

	const formattedStartDate = formatDate( startDate, 'MM/dd/yyyy' );
	const formattedEndDate = formatDate( endDate, 'MM/dd/yyyy' );

	return `${ formattedStartDate } - ${ formattedEndDate }`;
};

/**
 * Format number to k, m, b, t, etc.
 *
 * @param {number}  number                        - The number to format.
 * @param {Object}  options                       - Formatting options.
 * @param {number}  [options.decimals=1]          - Number of decimal places to show.
 * @param {boolean} [options.forceDecimals=false] - Whether to always show decimals.
 * @return {string} - The formatted number.
 */
export const formatNumber = ( number, options = {} ) => {
	const { decimals = 1, forceDecimals = false } = options;

	// Handle non-numeric inputs
	if ( typeof number !== 'number' || isNaN( number ) ) {
		return '0';
	}

	// Handle negative numbers
	const isNegative = number < 0;
	const absoluteNumber = Math.abs( number );

	// Return small numbers as is
	if ( absoluteNumber < 1000 ) {
		return isNegative ? `-${ absoluteNumber }` : absoluteNumber.toString();
	}

	const suffixes = [
		{ value: 1e3, suffix: 'k' },
		{ value: 1e6, suffix: 'm' },
		{ value: 1e9, suffix: 'b' },
		{ value: 1e12, suffix: 't' },
		{ value: 1e15, suffix: 'p' },
		{ value: 1e18, suffix: 'e' },
		{ value: 1e21, suffix: 'z' },
		{ value: 1e24, suffix: 'y' },
		{ value: 1e27, suffix: 'r' },
		{ value: 1e30, suffix: 'q' },
	];

	// Find the appropriate suffix
	const suffix =
		suffixes.find( ( { value } ) => absoluteNumber < value * 1000 ) ||
		suffixes[ suffixes.length - 1 ];

	// Calculate the formatted number
	const formattedValue = ( absoluteNumber / suffix.value ).toFixed(
		decimals
	);

	// Remove trailing zeros if forceDecimals is false
	const finalValue = forceDecimals
		? formattedValue
		: formattedValue.replace( /\.?0+$/, '' );

	return `${ isNegative ? '-' : '' }${ finalValue }${ suffix.suffix }`;
};

/**
 * Convert dates to ISO strings while preserving the local date
 *
 * @param {string|Date} date - The date to convert.
 * @return {string} - The ISO string.
 */
export const formatToISOPreserveDate = ( date ) => {
	const d = new Date( date );
	// Create ISO string with local timezone offset to preserve the date
	return new Date(
		d.getTime() - d.getTimezoneOffset() * 60000
	).toISOString();
};

/**
 * Format a date based on its position in a date range
 * 1. If the same year is selected across the range, exclude yyyy
 * 2. If date of same month is selected across the range, exclude MMM and yyyy
 *
 * @param {string|Date} date       - The date to format
 * @param {string|Date} from       - The start date of the range
 * @param {string|Date} start      - The end date of the range
 * @param {string}      dateFormat - The format to use for the full date (default: 'MMM dd, yyyy')
 * @return {string} - Formatted date string
 */
export const formatDateRange = (
	date,
	from,
	start,
	dateFormat = 'MMM dd, yyyy'
) => {
	if ( ! date ) {
		return '';
	}

	const dateObj = new Date( date );
	const fromObj = from ? new Date( from ) : null;
	const startObj = start ? new Date( start ) : null;

	// Check for invalid date
	if ( isNaN( dateObj.getTime() ) ) {
		return __( 'Invalid Date', 'surerank' );
	}

	// If no range is provided, format with the full date format
	if ( ! fromObj || ! startObj ) {
		return format( dateObj, dateFormat );
	}

	// Same month and year across the range
	if (
		fromObj.getMonth() === startObj.getMonth() &&
		fromObj.getFullYear() === startObj.getFullYear()
	) {
		// Just return day for dates in this range
		return format( dateObj, 'dd' );
	}

	// Same year across the range
	if ( fromObj.getFullYear() === startObj.getFullYear() ) {
		// Return month and day without year
		return format( dateObj, 'MMM dd' );
	}

	// Different years - show full date
	return format( dateObj, dateFormat );
};

/**
 * Debounce a function
 *
 * @param {Function} func    - The function to debounce
 * @param {number}   timeout - The timeout in milliseconds
 * @return {Function} - The debounced function
 */
export const debounce = ( func, timeout = 400 ) => {
	let timer;
	return ( ...args ) => {
		clearTimeout( timer );
		timer = setTimeout( () => {
			func.apply( this, args );
		}, timeout );
	};
};

/**
 * Create a resource promise
 *
 * @param {Promise} promise - The promise to create a resource for
 * @return {Object} - The resource promise
 */
export const createResourcePromise = ( promise ) => {
	let status = 'pending';
	let result;

	const suspender = promise.then(
		( data ) => {
			status = 'success';
			result = data;
		},
		( error ) => {
			status = 'error';
			result = error;
		}
	);

	return {
		read() {
			if ( status === 'pending' ) {
				throw suspender;
			} else if ( status === 'error' ) {
				throw result;
			} else if ( status === 'success' ) {
				return result;
			}
		},
	};
};

/**
 * Decode HTML entities in a string
 *
 * @param {string} text - The text to decode
 * @return {string} - The decoded text
 */

export const decodeHtmlEntities = ( text ) => {
	if ( ! text || typeof text !== 'string' ) {
		return text;
	}
	const parser = new DOMParser();
	const doc = parser.parseFromString( text, 'text/html' );
	return doc.documentElement.textContent ?? text;
};

/**
 * Check if a string is a valid URL
 *
 * @param {string} string - The string to check
 * @return {boolean} - True if the string is a valid URL, otherwise false
 */
export const isURL = ( string ) => {
	try {
		const urlPattern =
			/^(https?:\/\/)?((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|localhost|\d{1,3}(\.\d{1,3}){3})(:\d+)?(\/[-a-z\d%_.~+]*)*(\?[;&a-z\d%_.~+=-]*)?(\#[-a-z\d_]*)?(\s.*)?$/i;
		return urlPattern.test( string );
	} catch ( error ) {
		return false;
	}
};

/**
 * Format the bad, fair, and passed checks for the SEO checks.
 *
 * @param {Object} seoScore - The SEO score object containing the checks.
 * @return {Object} - An object containing the formatted checks.
 */

export const formatSeoChecks = ( seoScore ) => {
	if ( ! seoScore ) {
		return [];
	}

	return Object.entries( seoScore ).map( ( [ key, check ] ) => {
		const title = key
			.replace( /_/g, ' ' )
			.replace( /\b\w/g, ( c ) => c.toUpperCase() );
		return {
			...check,
			id: key,
			title: check?.message || title,
			data: check?.description,
			showImages: key === 'image_alt_text',
		};
	} );
};

/**
 * Get the categorized checks.
 *
 * @param {Array} checks      - The checks to categorize.
 * @param {Array} ignoredList - The ignored list.
 * @return {Object} - The categorized checks.
 */
export const getCategorizedChecks = ( checks, ignoredList = [] ) => {
	return checks.filter( Boolean ).reduce(
		( acc, check ) => {
			// Check if the check is in the ignored list
			if ( ignoredList.includes( check.id ) ) {
				check.ignore = true;
				acc.ignoredChecks.push( check );
			} else {
				// set the flag to false to show the check in the UI
				check.ignore = false;

				if ( check.status === 'error' ) {
					acc.badChecks.push( check );
				} else if ( check.status === 'warning' ) {
					acc.fairChecks.push( check );
				} else if ( check.status === 'suggestion' ) {
					acc.suggestionChecks.push( check );
				} else if ( check.status === 'success' ) {
					acc.passedChecks.push( check );
				}
			}
			return acc;
		},
		{
			badChecks: [],
			fairChecks: [],
			suggestionChecks: [],
			passedChecks: [],
			ignoredChecks: [],
		}
	);
};

export const getSeoCheckLabel = ( type, counts ) => {
	if ( type === 'error' ) {
		return sprintf(
			// translators: %1$s is the number of issues detected, %2$s is the word "Issue".
			'%1$s %2$s',
			counts,
			_n( 'Issue', 'Issues', counts, 'surerank' )
		);
	}
	if ( type === 'warning' ) {
		return sprintf(
			// translators: %1$s is the number of warnings detected, %2$s is the word "Warning".
			'%1$s %2$s',
			counts,
			_n( 'Warning', 'Warnings', counts, 'surerank' )
		);
	}
	return __( 'SEO is Optimized', 'surerank' );
};

/**
 * Get status indicator CSS classes based on check status
 *
 * @param {string} status Status of page checks ('error', 'warning', 'suggestion', 'success')
 * @return {string} CSS classes for the status indicator
 */
export const getStatusIndicatorClasses = ( status ) => {
	switch ( status ) {
		case 'error':
			return 'bg-support-error';
		case 'warning':
			return 'bg-support-warning';
		case 'suggestion':
			return 'bg-support-info';
		case 'success':
			return 'bg-support-success';
		default:
			return 'bg-background-secondary';
	}
};

/**
 * Get accessibility label for status indicator
 *
 * @param {number} errorAndWarnings Count of errors and warnings
 * @return {string} Accessibility label text
 */
export const getStatusIndicatorAriaLabel = ( errorAndWarnings ) => {
	if ( errorAndWarnings > 0 ) {
		return sprintf(
			/* translators: %1$d: number of errors and warnings */
			__( '%1$d %2$s need attention.', 'surerank' ),
			errorAndWarnings,
			_n( 'issue', 'issues', errorAndWarnings, 'surerank' )
		);
	}
	return __( 'All SEO checks passed.', 'surerank' );
};

/**
 * Extracts URL parameters from a given URL string and returns them as an object.
 * Handles both absolute and relative URLs by using the current origin as a base for relative ones.
 *
 * @param {string} url   - The URL string to parse.
 * @param {string} [key] - The specific key to retrieve from the URL parameters.
 * @return {Object} An object containing the URL parameters, or an empty object if parsing fails.
 */
export const getURLParams = ( url, key = '' ) => {
	try {
		const fullUrl = new URL( url, window.location.origin );
		const params = fullUrl.searchParams;
		if ( key ) {
			return params.get( key ) || '';
		}
		return Object.fromEntries( params.entries() );
	} catch ( error ) {
		return key ? '' : {};
	}
};

/**
 * Removes specified query parameters from a given URL string and returns the updated URL.
 * Handles both absolute and relative URLs by using the current origin as a base for relative ones.
 * If an array of keys is provided, removes all matching parameters; otherwise, removes the single key.
 *
 * @param {string}          url  - The URL string to modify.
 * @param {string|string[]} keys - The key(s) of the query parameter(s) to remove.
 * @return {string} The updated URL string with the specified parameters removed, or the original URL if parsing fails.
 */
export const removeQueryParams = ( url, keys ) => {
	try {
		const fullUrl = new URL( url, window.location.origin );
		const params = fullUrl.searchParams;
		if ( Array.isArray( keys ) ) {
			keys.forEach( ( key ) => params.delete( key ) );
		} else {
			params.delete( keys );
		}
		return fullUrl.toString();
	} catch ( error ) {
		return url;
	}
};

/**
 * Adds category property to each check item in the response
 *
 * @param {Object} response - The API response object
 * @param {string} category - The category to add to each item
 * @return {Object} The response object with category added to each item
 */
export const addCategoryToSiteSeoChecks = ( response, category ) => {
	if ( response && typeof response === 'object' ) {
		Object.keys( response ).forEach( ( key ) => {
			if ( response[ key ] && typeof response[ key ] === 'object' ) {
				response[ key ].category = category;
			}
		} );
	}
	return response;
};

/**
 * Merges all check types into a single array, updating the specified type with new values.
 *
 * @param {Object} state        - The current state containing pageSeoChecks.
 * @param {string} updatedType  - The type of checks to update (e.g., 'keywordChecks', 'pageChecks').
 * @param {Array}  updatedValue - The new array of checks for the specified type.
 * @return {Array} - The merged array of all checks.
 */
export const mergeAllCheckTypes = ( state, updatedType, updatedValue ) => {
	const allChecks = [];

	// Add checks from all types except the one being updated
	CHECK_TYPES.forEach( ( checkType ) => {
		if ( checkType === updatedType ) {
			// Use the new value for the type being updated
			allChecks.push( ...updatedValue );
		} else {
			const checkTypeKey = getCheckTypeKey( checkType ).type;
			// Use existing value from state for other types
			const existingChecks = state.pageSeoChecks?.[ checkTypeKey ] || [];
			allChecks.push( ...existingChecks );
		}
	} );

	return allChecks;
};

/**
 * Create checkType for state management
 *
 * @param {string} type - The type of check (e.g., 'keyword', 'page').
 * @return {string} - The corresponding state key for the check type.
 */
export const getCheckTypeKey = ( type ) => {
	return {
		type: `${ type }Checks`,
		categorizedType: `categorized${
			type.charAt( 0 ).toUpperCase() + type.slice( 1 )
		}Checks`,
	};
};

/**
 * Prepare URL by ensuring it has the correct protocol and removing unwanted prefixes.
 *
 * @param {string} siteURL
 * @return {string} prepared URL
 */
export const prepareURL = ( siteURL ) => {
	let url = siteURL ?? '';
	if ( url.includes( 'sc-domain:' ) ) {
		url = url.replace( /sc-domain:/, '' );
	}
	if ( ! url.includes( 'https://' ) && ! url.includes( 'http://' ) ) {
		url = `https://${ url }`;
	}
	return url;
};

/**
 * Get style classes for click/impression metrics based on data state
 *
 * @param {Object} item - The metric item containing value, previous, and percentageType
 * @return {Object} Object containing differenceClassName and fallbackClassName
 */
export const getMetricStyles = ( item ) => {
	let differenceClassName = '';
	switch ( item.percentageType ) {
		case 'danger':
			differenceClassName = 'text-support-error [&>*]:text-support-error';
			break;
		case 'success':
			differenceClassName =
				'text-support-success [&>*]:text-support-success';
			break;
		default:
			differenceClassName = '';
	}

	let fallbackClassName = '';
	// Render N/A and null for difference and icon when both value and previous are null.
	if ( item.value === null && item.previous === null ) {
		fallbackClassName = 'text-text-tertiary [&>*]:text-text-tertiary';
	}

	return { differenceClassName, fallbackClassName };
};

/**
 * Get formatted value and difference for click/impression metrics
 *
 * @param {Object} item - The metric item containing value and previous
 * @return {Object} Object containing renderValue and renderDifference
 */
export const getMetricValues = ( item ) => {
	const renderValue =
		item.value === null && item.previous === null
			? 'N/A'
			: formatNumber( item.value );
	const renderDifference =
		item.value === null && item.previous === null
			? 'N/A'
			: formatNumber( Math.abs( item?.value - item?.previous ) );

	return { renderValue, renderDifference };
};
