import {
	cn,
	getStatusIndicatorClasses,
	getStatusIndicatorAriaLabel,
} from '@/functions/utils';
import { STORE_NAME } from '@/store/constants';
import { dispatch, select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { calculateCheckStatus } from '@SeoPopup/utils/calculate-check-status';
import { refreshPageChecks } from '@SeoPopup/components/page-seo-checks/analyzer/utils/page-builder';
import { getTooltipText } from '@/apps/seo-popup/utils/page-checks-status-tooltip-text';
import './tooltip.css';
import {
	handleOpenSureRankDrawer,
	sureRankLogoForBuilder,
} from '@SeoPopup/utils/page-builder-functions';
import { ENABLE_PAGE_LEVEL_SEO } from '@/global/constants';

/* global jQuery */

// Custom Material UI style tooltip implementation for Elementor with TailwindCSS
const createSureRankTooltip = ( targetElement, tooltipText ) => {
	if ( ! targetElement || ! tooltipText ) {
		return;
	}

	// Create wrapper with surerank-root class
	const wrapper = document.createElement( 'div' );
	wrapper.className = 'surerank-root';

	// Create tooltip element with TailwindCSS styling
	const tooltip = document.createElement( 'div' );
	tooltip.className = cn(
		'surerank-tooltip',
		'absolute',
		'bg-gray-700',
		'text-white',
		'px-2',
		'py-0.5',
		'rounded',
		'text-[0.6875rem]',
		'font-medium',
		'leading-tight',
		'tracking-wide',
		'invisible',
		'opacity-0',
		'pointer-events-none',
		'origin-top',
		'z-[9999]',
		'top-0',
		'left-0'
	);
	tooltip.textContent = tooltipText;

	// Create arrow element
	const arrow = document.createElement( 'div' );
	arrow.className = cn(
		'absolute',
		'-top-[0.4375rem]',
		'left-1/2',
		'w-0',
		'h-0',
		'border-solid',
		'border-l-[0.375rem]',
		'border-r-[0.375rem]',
		'border-b-[0.375rem]',
		'border-l-transparent',
		'border-r-transparent',
		'border-t-transparent',
		'border-b-gray-700',
		'translate-x-[-50%]',
		'bg-transparent'
	);

	// Append arrow to tooltip
	tooltip.appendChild( arrow );

	// Append tooltip to wrapper
	wrapper.appendChild( tooltip );

	// Append wrapper to body
	document.body.appendChild( wrapper );

	// Position tooltip function
	const positionTooltip = () => {
		const targetRect = targetElement.getBoundingClientRect();

		// Position below the target element (accounting for arrow height)
		const top = targetRect.bottom + 16; // 14px for arrow and spacing
		const centerX = targetRect.left + targetRect.width / 2;

		tooltip.style.top = top + 'px';
		tooltip.style.left = centerX + 'px';
	};

	// Show tooltip
	const showTooltip = () => {
		positionTooltip();
		tooltip.classList.remove(
			'invisible',
			'opacity-0',
			'surerank-tooltip--hidden'
		);
		tooltip.classList.add(
			'visible',
			'opacity-100',
			'surerank-tooltip--visible'
		);
	};

	// Add event listeners
	let showTimeout;
	let hideTimeout;
	let hideAnimationTimeout;

	// Hide tooltip
	const hideTooltip = () => {
		clearTimeout( hideAnimationTimeout );
		tooltip.classList.remove( 'opacity-100' );
		tooltip.classList.add( 'opacity-0' );
		hideAnimationTimeout = setTimeout( () => {
			tooltip.classList.remove( 'visible' );
			tooltip.classList.add( 'invisible', 'surerank-tooltip--hidden' );
		}, 250 );
	};

	const handleMouseEnter = () => {
		clearTimeout( hideTimeout );
		showTimeout = setTimeout( showTooltip, 200 ); // 200ms delay like Material UI
	};

	const handleMouseLeave = () => {
		clearTimeout( showTimeout );
		hideTimeout = setTimeout( hideTooltip, 0 );
	};

	const handleFocus = () => {
		clearTimeout( hideTimeout );
		showTooltip();
	};

	const handleBlur = () => {
		clearTimeout( showTimeout );
		hideTooltip();
	};

	// Attach event listeners
	targetElement.addEventListener( 'mouseenter', handleMouseEnter );
	targetElement.addEventListener( 'mouseleave', handleMouseLeave );
	targetElement.addEventListener( 'focus', handleFocus );
	targetElement.addEventListener( 'blur', handleBlur );

	// Return cleanup function
	return () => {
		clearTimeout( showTimeout );
		clearTimeout( hideTimeout );
		clearTimeout( hideAnimationTimeout );
		targetElement.removeEventListener( 'mouseenter', handleMouseEnter );
		targetElement.removeEventListener( 'mouseleave', handleMouseLeave );
		targetElement.removeEventListener( 'focus', handleFocus );
		targetElement.removeEventListener( 'blur', handleBlur );
		if ( wrapper.parentNode ) {
			wrapper.parentNode.removeChild( wrapper );
		}
	};
};

// Function to get page check status using WordPress data
const getPageCheckStatus = () => {
	try {
		const storeSelectors = select( STORE_NAME );
		if (
			! storeSelectors ||
			typeof storeSelectors.getPageSeoChecks !== 'function'
		) {
			return {
				status: null,
				initializing: true,
				counts: { errorAndWarnings: 0, error: 0, warning: 0 },
			};
		}

		// Trigger ignored list retrieval
		const pageSeoChecks = storeSelectors.getPageSeoChecks() || {};
		const { categorizedChecks = {}, initializing = true } = pageSeoChecks;

		const { status, counts } = calculateCheckStatus( categorizedChecks );

		if ( initializing ) {
			dispatch( STORE_NAME ).setPageSeoCheck( 'initializing', false );
		}

		return {
			status,
			initializing,
			counts,
		};
	} catch ( error ) {
		// Return safe defaults if store is not available
		return {
			status: null,
			initializing: false,
			counts: { errorAndWarnings: 0, error: 0, warning: 0 },
		};
	}
};

// Function to create status indicator element
// eslint-disable-next-line no-shadow
const createStatusIndicator = ( $ ) => {
	const { status, counts } = getPageCheckStatus();

	// Don't show indicator if no status
	if ( ! status || ! ENABLE_PAGE_LEVEL_SEO ) {
		return null;
	}

	// Status indicator colors based on check status
	const statusClasses = getStatusIndicatorClasses( status );

	// Accessibility label for the indicator
	const ariaLabel = getStatusIndicatorAriaLabel( counts.errorAndWarnings );

	const indicator = $( '<div></div>' );
	indicator.addClass(
		cn(
			'absolute top-1.5 right-1.5 size-2 rounded-full z-10 duration-200',
			statusClasses
		)
	);
	indicator.attr( 'aria-label', ariaLabel );
	indicator.attr( 'title', ariaLabel );

	return indicator;
};

// eslint-disable-next-line wrap-iife
( function ( $ ) {
	let tooltipCleanup = null;
	let statusUpdateInterval = null;
	let unsubscribe = null;

	// State variables for refresh functionality
	let brokenLinkState = {
		isChecking: false,
		checkedLinks: new Set(),
		brokenLinks: new Set(),
		allLinks: [],
	};
	let refreshCalled = false;

	const setBrokenLinkState = ( updater ) => {
		if ( typeof updater === 'function' ) {
			brokenLinkState = updater( brokenLinkState );
		} else {
			brokenLinkState = updater;
		}
	};

	const setRefreshCalled = ( value ) => {
		refreshCalled = value;
	};

	// Function to handle refresh with broken links - adapted for Elementor context
	const handleRefreshWithBrokenLinks = async () => {
		const storeDispatch = dispatch( STORE_NAME );
		const storeSelectors = select( STORE_NAME );

		if ( ! storeSelectors || ! storeDispatch || ! ENABLE_PAGE_LEVEL_SEO ) {
			return;
		}

		try {
			setRefreshCalled( true ); // Ensure subsequent calls don't auto-refresh

			// Get current page SEO checks
			const pageSeoChecks = storeSelectors.getPageSeoChecks() || {};

			await refreshPageChecks(
				() => {},
				setBrokenLinkState,
				storeDispatch.setPageSeoCheck,
				select,
				pageSeoChecks,
				brokenLinkState
			);
		} catch ( error ) {
			// Silently ignore errors
		}
	};

	// Function to set up the Elementor integration once store is initialized
	const setupElementorIntegration = () => {
		const topBar = $(
			'#elementor-editor-wrapper-v2 header .MuiGrid-root:nth-child(3) .MuiStack-root'
		);

		// Get the button and svg class name from the topbar last child.
		const lastChild = topBar.last();
		const buttonClassName = lastChild.find( 'button' ).attr( 'class' );
		const svgClassName = lastChild.find( 'svg' ).attr( 'class' );

		// Create surerank-root wrapper for TailwindCSS
		const $sureRankWrapper = $( '<div class="surerank-root"></div>' );

		// Create a wrapper with relative positioning for the status indicator
		const $wrapper = $( '<div class="relative"></div>' );

		// Create the button with click handler
		const $button = $(
			`<button type="button" class="${ buttonClassName }" aria-label="${ __(
				'Open SureRank SEO',
				'surerank'
			) }" tabindex="0">
				${ sureRankLogoForBuilder( svgClassName ) }
			</button>`
		).on( 'click', handleOpenSureRankDrawer );

		// Add button to wrapper
		$wrapper.append( $button );

		// Add relative wrapper to surerank-root wrapper
		$sureRankWrapper.append( $wrapper );

		// Insert surerank-root wrapper after the first child
		topBar.children().first().after( $sureRankWrapper );

		// Function to update the status indicator
		const updateStatusIndicator = () => {
			// Remove existing indicator
			$wrapper.find( '.surerank-status-indicator' ).remove();

			// Create new indicator
			const indicator = createStatusIndicator( $ );
			if ( indicator ) {
				indicator.addClass( 'surerank-status-indicator' );
				$wrapper.append( indicator );
			}
		};

		// Function to update the tooltip
		const updateTooltip = () => {
			if ( tooltipCleanup ) {
				tooltipCleanup();
			}
			const { counts } = getPageCheckStatus();
			tooltipCleanup = createSureRankTooltip(
				$button[ 0 ],
				getTooltipText( counts )
			);
		};

		// Initial status indicator update
		updateStatusIndicator();

		// Refresh page checks on page load if not already called
		if ( ! refreshCalled ) {
			handleRefreshWithBrokenLinks();
		}

		// Subscribe to store changes to update the status and tooltip.
		unsubscribe = wp?.data?.subscribe?.( () => {
			updateStatusIndicator();
			updateTooltip();
		} );

		// Add tooltip to the button and store cleanup function
		const { counts } = getPageCheckStatus();
		tooltipCleanup = createSureRankTooltip(
			$button[ 0 ],
			getTooltipText( counts )
		);
	};

	// Function to wait for store initialization before setting up Elementor integration
	const waitForStoreInit = () => {
		let retryCount = 0;
		let storeUnsubscribe = null;
		let isInitialized = false;
		const maxRetries = 50; // Maximum 5 seconds of retrying (50 * 100ms)

		const cleanup = () => {
			if ( storeUnsubscribe && typeof storeUnsubscribe === 'function' ) {
				storeUnsubscribe();
				storeUnsubscribe = null;
			}
		};

		const checkStoreAndInitialize = () => {
			// Prevent multiple initializations
			if ( isInitialized ) {
				return;
			}

			try {
				const storeSelectors = select( STORE_NAME );

				// Check if store exists and has the required functions
				if (
					! storeSelectors ||
					typeof storeSelectors.getVariables !== 'function'
				) {
					// Store not available yet, retry with limit
					if ( retryCount < maxRetries ) {
						retryCount++;
						setTimeout( checkStoreAndInitialize, 100 );
					}
					return;
				}

				const variables = storeSelectors.getVariables();

				if ( variables ) {
					// Store is initialized, proceed with setup
					isInitialized = true;
					cleanup();
					setupElementorIntegration();
				} else if ( ! storeUnsubscribe ) {
					// Store exists but not initialized, subscribe once
					storeUnsubscribe = wp?.data?.subscribe?.( () => {
						try {
							const currentVariables =
								select( STORE_NAME )?.getVariables();
							if ( currentVariables && ! isInitialized ) {
								isInitialized = true;
								cleanup();
								setupElementorIntegration();
							}
						} catch ( error ) {
							// Silently handle subscription errors
						}
					} );

					// Fallback timeout to prevent infinite waiting
					setTimeout( () => {
						if ( ! isInitialized ) {
							const fallbackVariables =
								select( STORE_NAME )?.getVariables();
							if ( fallbackVariables ) {
								isInitialized = true;
								cleanup();
								setupElementorIntegration();
							}
						}
					}, 3000 );
				}
			} catch ( error ) {
				// Handle errors gracefully with retry limit
				if ( retryCount < maxRetries ) {
					retryCount++;
					setTimeout( checkStoreAndInitialize, 100 );
				}
			}
		};

		// Start the initialization check
		checkStoreAndInitialize();
	};

	$( window ).on( 'load', function () {
		// Wait for store initialization before proceeding
		waitForStoreInit();
	} );

	// Cleanup on page unload
	$( window ).on( 'beforeunload', function () {
		if ( tooltipCleanup ) {
			tooltipCleanup();
			tooltipCleanup = null;
		}
		if ( statusUpdateInterval ) {
			clearInterval( statusUpdateInterval );
			statusUpdateInterval = null;
		}
		if ( unsubscribe && typeof unsubscribe === 'function' ) {
			unsubscribe();
			unsubscribe = null;
		}
	} );
} )( jQuery );
